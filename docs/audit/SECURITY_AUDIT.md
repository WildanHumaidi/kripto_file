# SECURITY AUDIT REPORT
**Project:** kripto_file (Aplikasi Kriptografi File Berbasis AES)  
**Date:** 2026-10-22  
**Auditor / Agent:** Hermes Agent (Software Architect, Senior Engineer, QA & DevSecOps Reviewer)  
**Playbook Phase:** FASE 1 — RECONNAISSANCE & SECURITY AUDIT  
**Status Gate:** AUDIT COMPLETED — MULTIPLE CRITICAL VULNERABILITIES IDENTIFIED  

---

## 1. Executive Summary & Threat Model

Audit keamanan dilakukan secara komprehensif terhadap seluruh source code PHP native, konfigurasi, penanganan file, dan mekanisme kriptografi aplikasi `kripto_file`.
Aplikasi berfungsi sebagai sistem penyimpanan dan pemrosesan kriptografi file berbasis AES untuk pengguna terotentikasi.

### Threat Model:
1. **Unauthenticated External Attacker:** Dapat mencoba memintas autentikasi, mengeksekusi SQL Injection, memicu eksekusi kode pasca redirect (Execution After Redirect), mengeksploitasi path traversal saat upload/download, dan memicu DoS.
2. **Authenticated Malicious User:** Dapat mengakses file milik pengguna lain (IDOR), melakukan Cross-Site Scripting (XSS), atau memanipulasi file download.
3. **Internal / Database Compromise:** Kunci enkripsi tersimpan dalam basis data secara plaintext, meruntuhkan confidentiality seluruh file terenkripsi.

---

## 2. Daftar Temuan Keamanan (Vulnerability Matrix)

| ID | Kategori | Severity | Status | Lokasi Utama |
|---|---|---|---|---|
| SEC-01 | Broken Access Control / EAR | P0 Critical | Confirmed | `dashboard/index.php`, `enkripsi.php`, `dekripsi.php`, `decrypt-file.php`, `logout.php` |
| SEC-02 | Broken Authentication | P0 Critical | Confirmed | `dashboard/encrypt-process.php`, `decrypt-process.php`, `download.php` |
| SEC-03 | SQL Injection | P0 Critical | Confirmed | `dashboard/decrypt-file.php`, `assets/decrypt-file.php` |
| SEC-04 | Cryptographic Vulnerability | P0 Critical | Confirmed | `dashboard/encrypt-process.php`, `decrypt-process.php`, `AES.php` |
| SEC-05 | Hardcoded Credentials & Secret Exposure | P1 High | Confirmed | `index.php`, `config.php` |
| SEC-06 | Cross-Site Scripting (Stored & Reflected) | P1 High | Confirmed | `dashboard/index.php`, `enkripsi.php`, `dekripsi.php`, `decrypt-file.php` |
| SEC-07 | Insecure Direct Object Reference (IDOR) | P1 High | Confirmed | `dashboard/decrypt-file.php`, `decrypt-process.php`, `dekripsi.php` |
| SEC-08 | Path Traversal & Unvalidated File Handling | P1 High | Confirmed | `dashboard/encrypt-process.php`, `decrypt-process.php`, `download.php` |
| SEC-09 | Denial of Service (Resource Limit Bypass) | P2 Medium | Confirmed | `dashboard/encrypt-process.php`, `decrypt-process.php` |
| SEC-10 | Missing CSRF Protection | P2 Medium | Confirmed | `index.php`, `dashboard/enkripsi.php`, `decrypt-file.php`, `logout.php` |
| SEC-11 | Information Disclosure & Deprecated API | P2 Medium | Confirmed | `config.php`, `dashboard/encrypt-process.php` |
| SEC-12 | Stray Public Files | P3 Low | Confirmed | `assets/decrypt*.php` |

---

## 3. Rincian Temuan Keamanan

### SEC-01: Execution After Redirect (EAR) pada Guard Halaman Dashboard
- **Severity:** P0 Critical
- **Lokasi:** `dashboard/index.php:4-6`, `dashboard/enkripsi.php:4-6`, `dashboard/dekripsi.php:4-6`, `dashboard/decrypt-file.php:4-6`
- **Bukti Kode:**
  ```php
  if(empty($_SESSION['username'])){
      header("location:../index.php");
  }
  $last = $_SESSION['username'];
  $sqlupdate = "UPDATE users SET last_activity=now() WHERE username='$last'";
  ```
- **Dampak:** Fungsi `header("location:...")` di PHP tidak menghentikan eksekusi script tanpa pemanggilan `exit;` atau `die()`. Klien HTTP non-browser (cURL, script, atau scanner) dapat membaca seluruh konten halaman terproteksi dan memicu query database di bawahnya tanpa perlu login.
- **Rekomendasi:** Tambahkan `exit();` segera setelah setiap pemanggilan redirect:
  ```php
  if(empty($_SESSION['username'])){
      header("location:../index.php");
      exit();
  }
  ```

---

### SEC-02: Tidak Ada Pemeriksaan Sesi pada Endpoint Pemrosesan (Encrypt, Decrypt, Download)
- **Severity:** P0 Critical
- **Lokasi:**
  - `dashboard/encrypt-process.php:1-10`
  - `dashboard/decrypt-process.php:1-10`
  - `dashboard/download.php:1-10`
- **Bukti Kode:**
  Pada `decrypt-process.php`, script hanya menjalankan `session_start();` lalu langsung memproses input `$_POST['fileid']` dan `$_POST['pwdfile']` tanpa memvalidasi apakah `$_SESSION['username']` ada.
  Pada `download.php`, file langsung dibaca via `readfile($_SESSION["download"])` tanpa validasi login.
- **Dampak:** Penyerang unauthenticated dapat memicu dekripsi file dan pengunduhan file secara langsung via HTTP POST / GET.
- **Rekomendasi:** Terapkan pemeriksaan autentikasi terpusat pada seluruh endpoint pemrosesan.

---

### SEC-03: SQL Injection Kritis via Parameter GET
- **Severity:** P0 Critical
- **Lokasi:** `dashboard/decrypt-file.php:184-185`, `assets/decrypt-file.php:76-77`
- **Bukti Kode:**
  ```php
  $id_file = $_GET['id_file'];
  $query = mysqli_query($connect,"SELECT * FROM file WHERE id_file='$id_file'");
  ```
- **Dampak:** Parameter `id_file` langsung disambungkan ke dalam query string tanpa sanitasi atau prepared statement. Penyerang dapat menyuntikkan payload SQL untuk membaca seluruh tabel `users`, mengekstraksi hash kata sandi, atau merusak data.
- **Rekomendasi:** Gunakan parameterized prepared statements (`mysqli_prepare` / `bind_param`) atau minimal validasi tipe data `(int)$_GET['id_file']`.

---

### SEC-04: Kerentanan Arsitektur Kriptografi (Plaintext Key Storage, ECB Mode, & MD5 Key Derivation)
- **Severity:** P0 Critical
- **Lokasi:** `dashboard/encrypt-process.php:8, 43`, `dashboard/decrypt-process.php:6-8`, `dashboard/AES.php`
- **Bukti Kode:**
  1. Plaintext Key Storage:
     ```php
     $key = mysqli_escape_string($connect,substr(md5($_POST["pwdfile"]), 0,16));
     ...
     $sql1 = "INSERT INTO file VALUES ('', '$user', '$final_file', '$finalfile.rda', '', '$size2', '$key', now(), '1', '$deskripsi')";
     ```
     Kunci AES yang diturunkan disimpan telanjang di kolom `password` pada database `file`. Siapa pun yang memiliki akses baca DB dapat mendekripsi semua file tanpa mengetahui password asli.
  2. Weak Key Derivation:
     Kunci hanya mengambil 16 karakter pertama dari MD5 hex string (`substr(md5(...), 0, 16)`), menghasilkan entropi efektif yang sangat rendah (~64-bit karakter ASCII hex) dan rentan dictionary attack.
  3. ECB-Like Operation Mode:
     Setiap blok 16-byte dienkripsi secara independen tanpa Initialization Vector (IV).
- **Dampak:** Kompromi kerahasiaan data total. Kebocoran database menyebabkan kebocoran seluruh isi file terenkripsi. Pola ciphertext bocor karena ECB mode.
- **Rekomendasi:** Pisahkan verifikasi password (menggunakan bcrypt/Argon2 hash untuk verifikasi hak akses) dari enkripsi data, atau gunakan envelope encryption dengan salt/IV unik per file.

---

### SEC-05: Hardcoded Default Credentials & Pemaparan Kredensial
- **Severity:** P1 High
- **Lokasi:** `index.php:40`, `config.php:2-4`
- **Bukti Kode:**
  - `index.php:40`: `<p>User: admin Pass: 1</p>` terpampang langsung pada UI login publik.
  - `config.php:2-4`: Kredensial default database (`localhost`, `root`, password kosong `''`) disimpan hardcoded di file publik repositori tanpa opsi `.env`.
- **Dampak:** Mempermudah unauthorized login bagi pihak luar saat dideploy.
- **Rekomendasi:** Hapus teks kredensial default dari `index.php`. Sediakan mekanisme konfigurasi berbasis environment atau file template `.env.example`.

---

### SEC-06: Cross-Site Scripting (XSS) Stored & Reflected
- **Severity:** P1 High
- **Lokasi:**
  - `dashboard/index.php:97, 261-265`
  - `dashboard/decrypt-file.php:188, 195, 198, 205, 210`
  - `dashboard/dekripsi.php:201-203`
- **Bukti Kode:**
  ```php
  <h2><?php echo $data['fullname']; ?> ... <?php echo $data['job_title']; ?></h2>
  ...
  <td><?php echo $data['file_name_source']; ?></td>
  ```
- **Dampak:** Nilai kolom dari database (`fullname`, `job_title`, `file_name_source`, `keterangan`) diekspos langsung ke HTML tanpa `htmlspecialchars()`. Jika pengguna mengunggah file dengan nama mengandung tag HTML/JavaScript, skrip jahat akan dieksekusi di browser pengguna/admin lain saat membuka halaman dashboard atau dekripsi.
- **Rekomendasi:** Terapkan `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` pada seluruh output data dinamis.

---

### SEC-07: Insecure Direct Object Reference (IDOR) & Broken Multi-Tenancy
- **Severity:** P1 High
- **Lokasi:** `dashboard/decrypt-file.php:185`, `dashboard/decrypt-process.php:8`, `dashboard/dekripsi.php:197`, `dashboard/index.php:257`
- **Bukti Kode:**
  - Query menampilkan seluruh data file milik semua user: `SELECT * FROM file`
  - Query dekripsi tidak membatasi pemilik: `SELECT * FROM file WHERE id_file='$id_file'`
- **Dampak:** Tidak ada isolasi data antar pengguna. Semua pengguna dapat melihat daftar file milik pengguna lain, dan dapat mencoba mendekripsi file milik pengguna lain jika mengetahui password file.
- **Rekomendasi:** Batasi query dengan klausul `WHERE username = '...'` sesuai session pengguna aktif, atau definisikan role admin vs user secara jelas.

---

### SEC-08: Path Traversal & Unvalidated File Handling
- **Severity:** P1 High
- **Lokasi:**
  - `dashboard/encrypt-process.php:13-19`
  - `dashboard/decrypt-process.php:17, 30`
  - `dashboard/download.php:3, 8`
- **Bukti Kode:**
  ```php
  $file = rand(1000,100000)."-".$_FILES['file']['name'];
  ...
  $file_name = $data["file_name_source"];
  $cache = "hasil_dekripsi/$file_name";
  $fopen2 = fopen($cache, "wb");
  ```
  `$_FILES['file']['name']` hanya diproses dengan `str_replace(' ', '-', ...)` tanpa menggunakan `basename()`.
- **Dampak:** Jika nama file berisi `../../`, file hasil dekripsi dapat ditulis ke direktori arbitrer di luar `hasil_dekripsi/`. Pada `download.php`, `readfile($_SESSION['download'])` membaca file tanpa memvalidasi apakah path berada di dalam direktori yang diizinkan.
- **Rekomendasi:** Sanitasi semua nama file dengan `basename()`, buang karakter non-alfanumerik berbahaya, dan validasi `realpath()` sebelum operasi baca/tulis.

---

### SEC-09: Denial of Service (Resource Limit Bypass)
- **Severity:** P2 Medium
- **Lokasi:** `dashboard/encrypt-process.php:66-67`, `dashboard/decrypt-process.php:40-41`
- **Bukti Kode:**
  ```php
  ini_set('max_execution_time', -1);
  ini_set('memory_limit', -1);
  ```
- **Dampak:** Menonaktifkan batas waktu eksekusi dan memori PHP secara global per request. Permintaan berulang terhadap pemrosesan file besar dapat menghabiskan RAM server dan menyebabkan Denial of Service (DoS).
- **Rekomendasi:** Tetapkan batas rasional (misal timeout 60s, limit memori 128MB) dan perketat ukuran maksimum upload file.

---

### SEC-10: Ketiadaan Proteksi Cross-Site Request Forgery (CSRF)
- **Severity:** P2 Medium
- **Lokasi:** Seluruh form POST (`index.php`, `enkripsi.php`, `decrypt-file.php`, `logout.php`)
- **Dampak:** Penyerang dapat memicu submit form atas nama pengguna terotentikasi melalui situs pihak ketiga.
- **Rekomendasi:** Tambahkan token anti-CSRF berbasis session pada form dan validasi sebelum pemrosesan.

---

### SEC-11: Keterbukaan Pesan Kesalahan & Fungsi Database Usang
- **Severity:** P2 Medium
- **Lokasi:** `config.php:6`, `dashboard/encrypt-process.php:44, 47, 53`
- **Bukti Kode:**
  `mysqli_connect(...) or die(mysql_error());`
  `mysqli_query(...) or die(mysqli_error($connect));`
- **Dampak:** Mengeluarkan detail internal database (nama tabel, sintaks query) langsung ke pengguna publik saat terjadi kegagalan. `mysql_error()` juga menyebabkan PHP Fatal Error pada PHP 7+.
- **Rekomendasi:** Gunakan error handling yang aman dan catat error ke log internal tanpa menampilkan detail sensitif ke pengguna.

---

### SEC-12: File Duplikat Tak Terpakai pada Folder Publik Assets
- **Severity:** P3 Low
- **Lokasi:** `assets/decrypt.php`, `assets/decrypt-file.php`, `assets/decrypt-process.php`
- **Dampak:** File skrip PHP diletakkan di direktori aset statis publik `assets/`. Skrip tersebut memiliki broken dependency (`include 'AES.php'` gagal) dan memperluas attack surface yang tidak perlu.
- **Rekomendasi:** Evaluasi pembersihan file pada fase refactoring setelah konfirmasi tidak ada aset yang bergantung padanya.

---

## 4. Evaluasi Secret Exposure pada Riwayat & Workspace

- **Potential Exposure in Working Tree:**
  - `index.php:40`: Kredensial default admin ditampilkan di frontend.
  - `config.php:2-4`: Konfigurasi database lokal hardcoded.
- **Git History Scan:**
  - `N/A` (Workspace saat ini belum merupakan Git repository).
- **Staged Diff:**
  - `N/A` (Belum ada file yang di-stage).

---

## 5. Keterbatasan Audit & Rekomendasi Mitigasi
Audit ini mencakup seluruh source code statis pada workspace lokal. Pemeriksaan dinamik langsung pada server produksi atau integrasi multi-server non-lokal berada di luar cakupan.
Proyek **TIDAK DAPAT** dinyatakan aman (not security-clean) sebelum seluruh temuan P0 dan P1 di atas dimitigasi secara terstruktur.
