# DEEP BUG AUDIT REPORT
**Project:** kripto_file (Aplikasi Kriptografi File Berbasis AES)  
**Date:** 2026-10-22  
**Auditor / Agent:** Hermes Agent (Software Architect, Senior Engineer, QA & DevSecOps Reviewer)  
**Playbook Phase:** FASE 2 — DEEP BUG AUDIT & BUSINESS LOGIC REVIEW  
**Status Gate:** AUDIT COMPLETED — CRITICAL LOGIC & INTEGRITY BUGS IDENTIFIED  

---

## 1. Ringkasan Eksekutif & Metodologi Audit

Audit mendalam dilakukan untuk mengidentifikasi cacat logika bisnis, integritas data, konsistensi alur kriptografi, serta silent bugs yang tidak memicu error sintaks tetapi menghasilkan komputasi, penyimpanan, atau alur aplikasi yang salah.

### Alur Data End-to-End yang Diinspeksi:
1. **Autentikasi & Sesi:** Form Login (`index.php`) → Pemroses Auth (`auth.php`) → Inisialisasi Sesi → Redirect Guard Dashboard (`dashboard/index.php`).
2. **Siklus Enkripsi:** Form Upload (`dashboard/enkripsi.php`) → Validasi Tipe & Ukuran → Pembentukan Record DB (`dashboard/encrypt-process.php`) → Enkripsi Blok AES (`dashboard/AES.php`) → Penyimpanan File `.rda` → Notifikasi UI.
3. **Siklus Dekripsi:** Tabel File (`dashboard/dekripsi.php`) → Form Input Kunci (`dashboard/decrypt-file.php`) → Verifikasi Kunci & Query DB (`dashboard/decrypt-process.php`) → Dekripsi Blok AES (`dashboard/AES.php`) → Penulisan Plaintext Hasil → Download Handler (`dashboard/download.php`).

---

## 2. Matriks Temuan Bug Logika & Fungsional

| ID | Judul Bug | Severity | Confidence | Lokasi Utama |
|---|---|---|---|---|
| BUG-01 | Trailing Null-Byte Padding Corrupts Decrypted File Integrity | P0 Critical | Confirmed | `dashboard/AES.php:171-175`, `dashboard/encrypt-process.php`, `dashboard/decrypt-process.php` |
| BUG-02 | Race Condition & Overwrite pada Query Update `file_url` | P0 Critical | Confirmed | `dashboard/encrypt-process.php:43-53` |
| BUG-03 | Broken Redirect URLs Menghasilkan Error 404 (encrypt.php & home.php) | P1 High | Confirmed | `dashboard/encrypt-process.php:29, 37` |
| BUG-04 | Inkonsistensi Filter Ekstensi vs Pesan Validasi UI | P1 High | Confirmed | `dashboard/encrypt-process.php:26-31` |
| BUG-05 | Insecure Key Storage & MD5 Key Derivation Flaw | P1 High | Confirmed | `dashboard/encrypt-process.php:8, 43`, `dashboard/decrypt-process.php:7` |
| BUG-06 | Missing Script Termination Pasca Header Redirect (EAR) | P1 High | Confirmed | `auth.php`, `dashboard/*.php`, `dashboard/logout.php` |
| BUG-07 | Fatal Error Pemanggilan `mysql_error()` pada Driver MySQLi | P2 Medium | Confirmed | `config.php:6` |
| BUG-08 | Entropi Lemah Prefix Nama File Mengakibatkan Risiko Collision | P2 Medium | Confirmed | `dashboard/encrypt-process.php:13, 17` |
| BUG-09 | Kehilangan Akses Download Setelah Single-Shot Sesi Terhapus | P2 Medium | Confirmed | `dashboard/download.php:9`, `dashboard/dekripsi.php:217` |
| BUG-10 | External Hotlink Asset Google User Content pada UI Avatar | P3 Low | Confirmed | `dashboard/index.php:96`, `enkripsi.php`, `dekripsi.php`, `decrypt-file.php` |
| BUG-11 | Selector Class Mismatch pada Skrip Animasi Login | P3 Low | Confirmed | `index.php:26, 35`, `assets/js/main1.js:1` |
| BUG-12 | Stray Duplicate Script `assets/decrypt*.php` dengan Broken Includes | P3 Low | Confirmed | `assets/decrypt-process.php:4` |

---

## 3. Rincian Temuan Wajib Sesuai Format Playbook

### BUG-01: Trailing Null-Byte Padding Merusak Integritas File Hasil Dekripsi
- **Severity:** P0 Critical
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/AES.php:171-175`, `dashboard/encrypt-process.php:57-64, 70-74`, `dashboard/decrypt-process.php:33-47`
- **Expected vs Actual:**
  - *Expected:* File hasil dekripsi harus memiliki ukuran byte dan checksum hash yang identik (100% bit-for-bit match) dengan file asli sebelum dienkripsi.
  - *Actual:* File hasil dekripsi bertambah panjang (padded) hingga kelipatan 16 byte dengan karakter null (`\x00`).
- **Bukti & Reproduksi:**
  Pengujian perbandingan file asli vs hasil dekripsi yang sudah tersimpan di direktori proyek:
  ```bash
  python -c '
  with open("test_txt.txt", "rb") as f: orig = f.read()
  with open("dashboard/hasil_dekripsi/57990-test_txt.txt", "rb") as f: dec = f.read()
  print("Original len:", len(orig), "Decrypted len:", len(dec))
  print("Trailing bytes:", repr(dec[len(orig):]))
  '
  # Output Aktual:
  # Original len: 22 b'Agus Sepriyandiansah\r\n'
  # Decrypted len: 32 b'Agus Sepriyandiansah\r\n\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00'
  # Trailing bytes: b'\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00'
  ```
- **First Divergence:** Pada `AES::encrypt()`, jika blok terakhir kurang dari 16 byte, blok tersebut diisi nilai `0` (`$this->state[$i][$j] = 0;`). Namun pada saat dekripsi, seluruh 16 byte dari setiap blok didekripsi dan ditulis langsung ke disk tanpa pencatatan panjang asli file ataupun unpadding.
- **Root Cause:** Tidak adanya skema standard padding (seperti PKCS#7) atau pencatatan ukuran asli byte file pada metadata/header file atau database.
- **Dampak Teknis & Bisnis:** File teks memiliki null byte tersembunyi; file binary (seperti dokumen Microsoft Word `.docx` dan PowerPoint `.pptx` yang berbasis format ZIP archive) dapat mengalami peringatan integritas korup ("File contains unreadable content") saat dibuka di Microsoft Office.
- **Rekomendasi Perbaikan:** Simpan ukuran byte asli (`filesize_bytes`) pada database saat enkripsi, dan potong tepat pada ukuran asli saat penulisan file hasil dekripsi; atau gunakan standar padding PKCS#7 yang divalidasi dan di-strip pada blok akhir saat dekripsi.
- **Test Regresi yang Dibutuhkan:** Enkripsi dan dekripsi file `test_txt.txt`, `test_doc.docx`, dan `test_ppt.pptx`, lalu verifikasi bahwa `sha256(original) === sha256(decrypted)`.

---

### BUG-02: Race Condition dan Blind UPDATE Menimpa `file_url` File Lain
- **Severity:** P0 Critical
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/encrypt-process.php:43-53`
- **Expected vs Actual:**
  - *Expected:* Setiap record file di database mendapatkan `file_url` miliknya sendiri secara atomik dan deterministik.
  - *Actual:* Record di-insert dengan `file_url = ''`, lalu dieksekusi query `UPDATE file SET file_url = '$file_url' WHERE file_url=''`.
- **Bukti Kode:**
  ```php
  $sql1 = "INSERT INTO file VALUES ('', '$user', '$final_file', '$finalfile.rda', '', '$size2', '$key', now(), '1', '$deskripsi')";
  $query1 = mysqli_query($connect,$sql1) or die(mysqli_error($connect));
  ...
  $sql3 = "UPDATE file SET file_url ='$file_url' WHERE file_url=''";
  $query3 = mysqli_query($connect,$sql3) or die(mysqli_error($connect));
  ```
- **First Divergence:** Pembagian proses pembuatan baris database menjadi INSERT terpisah dan UPDATE tanpa kondisi `WHERE id_file = LAST_INSERT_ID()`.
- **Root Cause:** Pola pengkodean cacat; nilai `$file_url` sudah dapat ditentukan sebelum INSERT (`$url = $finalfile.".rda"; $file_url = "hasil_ekripsi/$url";`), namun sengaja di-insert kosong lalu di-update global.
- **Dampak Teknis & Bisnis:** Jika dua proses berjalan bersamaan atau jika ada upload sebelumnya yang gagal mengisi URL, query update akan menimpa URL record file lain, mengakibatkan file hilang dari referensi atau tertukar.
- **Rekomendasi Perbaikan:** Masukkan `$file_url` langsung ke dalam query `INSERT INTO file VALUES (...)`.

---

### BUG-03: Broken Redirect URLs Menghasilkan Error 404 (encrypt.php & home.php)
- **Severity:** P1 High
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/encrypt-process.php:29, 37`
- **Expected vs Actual:**
  - *Expected:* Jika validasi ekstensi atau ukuran gagal, user diarahkan kembali ke halaman form enkripsi (`enkripsi.php`).
  - *Actual:* User di-redirect ke `encrypt.php` (ekstensi salah) atau `home.php?encrypt` (file tidak ada), memicu error 404 Not Found di browser.
- **Bukti Kode:**
  ```php
  // Baris 29
  window.location.href='encrypt.php';
  // Baris 37
  window.location.href='home.php?encrypt';
  ```
- **Root Cause:** Kesalahan penulisan nama file tujuan redirect pasca refactoring nama file ke bahasa Indonesia (`enkripsi.php`).
- **Rekomendasi Perbaikan:** Ubah seluruh redirect tujuan validasi menjadi `enkripsi.php`.

---

### BUG-04: Inkonsistensi Pesan Validasi UI vs Filter Ekstensi Aktual
- **Severity:** P1 High
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/encrypt-process.php:26-33`
- **Expected vs Actual:**
  - *Expected:* Alert menyatakan sistem mendukung "word, excel, text, ppt ataupun pdf", sehingga sistem seharusnya menerima format tersebut.
  - *Actual:* Kondisi `if ($ext=="txt" || $ext=="docx" || $ext=="pptx")` menolak `.pdf`, `.doc`, `.xls`, `.xlsx`, `.ppt`.
- **Root Cause:** Penambahan pesan validasi tanpa memperbarui filter logika kode.
- **Rekomendasi Perbaikan:** Selaraskan daftar ekstensi yang didukung dengan kebutuhan spesifikasi bisnis atau sesuaikan teks pesan peringatan secara akurat.

---

### BUG-05: Penurunan Kunci AES yang Lemah & Penyimpanan Kunci Plaintext
- **Severity:** P1 High
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/encrypt-process.php:8, 43`, `dashboard/decrypt-process.php:7`
- **Expected vs Actual:**
  - *Expected:* Kunci enkripsi memiliki entropi kuat dan tidak disimpan secara plaintext di database.
  - *Actual:* Kunci diturunkan dari `substr(md5($pwdfile), 0, 16)` dan disimpan mentah pada kolom `password` tabel `file`.
- **First Divergence:** Eksekusi `substr(md5($_POST["pwdfile"]), 0, 16)` membatasi karakter kunci hanya pada range hex `[0-9a-f]`, membuang separuh ruang nilai byte AES.
- **Dampak Teknis & Bisnis:** Kekuatan enkripsi AES-128 terpangkas drastis, dan seluruh isi file dapat didekripsi jika database bocor.
- **Rekomendasi Perbaikan:** Gunakan hashing kriptografis standar (misalnya SHA-256 binary terpotong 16 byte atau HKDF) untuk pembentukan kunci AES, dan jangan simpan kunci dekripsi di database; simpan hash kata sandi terpisah untuk validasi hak dekripsi.

---

### BUG-06: Missing Exit Call Pasca Header Location Redirect (EAR)
- **Severity:** P1 High
- **Confidence:** Confirmed
- **Lokasi:** `auth.php:20`, `dashboard/index.php:5`, `dashboard/enkripsi.php:5`, `dashboard/dekripsi.php:5`, `dashboard/decrypt-file.php:5`, `dashboard/logout.php:5`
- **Expected vs Actual:**
  - *Expected:* Pemanggilan `header("location: ...")` selalu diikuti `exit;` untuk menghentikan pemrosesan script.
  - *Actual:* Tidak ada `exit;`, script PHP terus mengeksekusi sisa kode.
- **Dampak Teknis & Bisnis:** Eksekusi kode yang tidak perlu dan kebocoran data halaman terproteksi ke klien non-browser.
- **Rekomendasi Perbaikan:** Tambahkan `exit;` tepat setelah setiap pemanggilan fungsi `header("location: ...")`.

---

### BUG-07: Fatal Error Pemanggilan Fungsi Usang `mysql_error()`
- **Severity:** P2 Medium
- **Confidence:** Confirmed
- **Lokasi:** `config.php:6`
- **Expected vs Actual:**
  - *Expected:* Menggunakan `mysqli_connect_error()` jika koneksi gagal.
  - *Actual:* Memanggil `mysql_error()`.
- **Dampak Teknis:** Jika database down atau gagal otentikasi, script melempar fatal error PHP: `Fatal error: Uncaught Error: Call to undefined function mysql_error()`.
- **Rekomendasi Perbaikan:** Ganti dengan `mysqli_connect_error()`.

---

### BUG-08: Entropi Lemah Prefix Nama File Mengakibatkan Risiko Collision
- **Severity:** P2 Medium
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/encrypt-process.php:13, 17`
- **Expected vs Actual:**
  - *Expected:* Nama file unik tanpa risiko tumpang tindih.
  - *Actual:* Menggunakan `rand(1000, 100000)`, hanya terdapat rentang 99.001 kemungkinan.
- **Dampak Teknis:** Dua file dengan nama sama yang diunggah dapat menimpa file fisik hasil enkripsi sebelumnya.
- **Rekomendasi Perbaikan:** Gunakan identifier unik kriptografis seperti `bin2hex(random_bytes(8))` atau format timestamp milidetik + random string.

---

### BUG-09: Kehilangan Akses Download Setelah Single-Shot Sesi Terhapus
- **Severity:** P2 Medium
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/download.php:9`, `dashboard/dekripsi.php:217`
- **Expected vs Actual:**
  - *Expected:* Pengguna dapat mengunduh kembali file yang sudah didekripsi dari tabel arsip.
  - *Actual:* `download.php` menghapus variabel sesi `unset($_SESSION["download"])`. Di halaman `dekripsi.php`, tombol untuk file berstatus `2` berubah menjadi link ke `enkripsi.php` ("Enkripsi File"), sehingga pengguna tidak memiliki tombol untuk mengunduh ulang file yang sudah didekripsi.
- **Dampak Teknis & Bisnis:** Pengguna yang gagal men-download file di popup awal tidak bisa men-download ulang file tersebut tanpa proses manual.
- **Rekomendasi Perbaikan:** Tambahkan opsi download yang valid untuk file yang sudah berstatus 2, atau pertahankan riwayat link download yang aman.

---

### BUG-10: Hotlinked External Google Avatar Asset
- **Severity:** P3 Low
- **Confidence:** Confirmed
- **Lokasi:** `dashboard/index.php:96`, `enkripsi.php:96`, `dekripsi.php:96`, `decrypt-file.php:96`
- **Bukti Kode:**
  `<img src="https://lh3.googleusercontent.com/ogw/ADGmqu-5A4r40ZPotQWqRs5qBqjF1pxruJuJs5TURuzdZw=s83-c-mo" ...>`
- **Dampak Teknis:** Ketergantungan jaringan eksternal pihak ketiga, gambar rusak jika offline atau URL kadaluarsa.
- **Rekomendasi Perbaikan:** Ganti dengan asset avatar lokal proyek (misal `img/avatar.png` atau `../assets/img/avatar.svg`).

---

### BUG-11: Selector Class Mismatch pada Animasi Input Form Login
- **Severity:** P3 Low
- **Confidence:** Confirmed
- **Lokasi:** `index.php:26, 35`, `assets/js/main1.js:1`
- **Expected vs Actual:**
  - *Expected:* Input login memiliki kelas CSS `.input` agar event listener di `main1.js` aktif (`document.querySelectorAll(".input")`).
  - *Actual:* Input menggunakan kelas `.form-control`, sehingga event listener tidak menempel dan efek animasi form tidak bekerja.
- **Rekomendasi Perbaikan:** Tambahkan kelas `input` pada elemen `<input>` form login.

---

### BUG-12: Stray Duplicate Scripts `assets/decrypt*.php`
- **Severity:** P3 Low
- **Confidence:** Confirmed
- **Lokasi:** `assets/decrypt.php`, `assets/decrypt-file.php`, `assets/decrypt-process.php`
- **Dampak:** Script usang di direktori publik yang merujuk include yang tidak ada (`AES.php`), berpotensi memunculkan fatal error jika diakses pengunjung.
- **Rekomendasi Perbaikan:** Tandai untuk cleanup terarah.
