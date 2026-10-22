# RISK REGISTER & BUSINESS IMPACT ANALYSIS
**Project:** kripto_file (Aplikasi Kriptografi File Berbasis AES)  
**Date:** 2026-10-22  
**Auditor / Agent:** Hermes Agent (Software Architect, Senior Engineer, QA & DevSecOps Reviewer)  
**Playbook Phase:** FASE 2 — DEEP BUG AUDIT & BUSINESS LOGIC REVIEW  
**Status Gate:** COMPLETED — RISKS CATEGORIZED & PRIORITIZED  

---

## 1. Definisi Matriks Risiko

- **Likelihood:**
  - High (Pasti atau sangat mudah terjadi dalam operasional normal).
  - Medium (Terjadi pada kondisi tertentu atau membutuhkan sedikit manipulasi).
  - Low (Jarang terjadi atau membutuhkan kombinasi kondisi kompleks).
- **Impact:**
  - Critical (Kehilangan data total, kebocoran data rahasia, atau integritas sistem runtuh).
  - Major (Kerusakan fungsi bisnis utama, korupsi file pengguna, kegagalan otentikasi).
  - Moderate (Kegagalan UX/UI, fitur sekunder terganggu, link mati).
  - Minor (Masalah kosmetik atau degradasi performa non-kritis).

---

## 2. Register Risiko Bisnis & Operasional

### RSK-01: Korupsi Integritas Dokumen Pasca Dekripsi (Padding Null Bytes)
- **Skenario:** Pengguna mengunggah dokumen biner terkompresi seperti `.docx` atau `.pptx` berukuran bukan kelipatan 16 byte. Setelah dienkripsi dan didekripsi, file bertambah byte null (`\0`), menyebabkan aplikasi pembaca dokumen (Microsoft Office / LibreOffice) memperingatkan bahwa file rusak/korup saat dibuka.
- **Pihak Terdampak:** Pengguna akhir, pemilik dokumen rahasia.
- **Likelihood:** High (hampir semua file nyata bukan kelipatan pas 16 byte).
- **Impact:** Critical (kehilangan integritas dokumen bisnis penting).
- **Mitigasi Teknis:** Rekam ukuran byte asli pada metadata database/file dan lakukan truncating pasca dekripsi tepat pada panjang asli file.
- **Owner yang Disarankan:** Backend Engineer / Cryptography Lead.
- **Residual Risk:** File terenkripsi lama yang dienkripsi sebelum perbaikan mungkin memerlukan penanganan backward compatibility khusus.

---

### RSK-02: Kebocoran Data Rahasia Melalui Akses Database Langsung / SQLi
- **Skenario:** Penyerang memanfaatkan SQL Injection pada `decrypt-file.php?id_file=...` atau membaca database dump. Karena kunci turunan disimpan telanjang pada kolom `password`, seluruh file terenkripsi di folder `hasil_ekripsi/` dapat didekripsi seketika oleh penyerang.
- **Pihak Terdampak:** Seluruh organisasi / pengguna aplikasi.
- **Likelihood:** High (SQL injection langsung tanpa otentikasi ketat).
- **Impact:** Critical (kerahasiaan seluruh file hilang total).
- **Mitigasi Teknis:** Terapkan parameterized query / prepared statements untuk seluruh query, dan jangan simpan kunci dekripsi plaintext pada database.
- **Owner yang Disarankan:** DevSecOps / Database Administrator.
- **Residual Risk:** Kunci dekripsi harus tetap dapat divalidasi kecocokannya saat pengguna memasukkan password tanpa menyimpan kuncinya secara mentah.

---

### RSK-03: Akses Tanpa Izin ke Dashboard via Klien Non-Browser (EAR Vulnerability)
- **Skenario:** Penyerang mengirim permintaan HTTP GET ke `dashboard/index.php`, `enkripsi.php`, atau `dekripsi.php` menggunakan script Python atau cURL tanpa cookie sesi. Karena tidak ada pemanggilan `exit;` setelah `header("location:...")`, server merespons dengan konten lengkap dashboard dan mengeksekusi query database.
- **Pihak Terdampak:** Semua pengguna sistem, integritas log aktivitas.
- **Likelihood:** High (sifat bawaan PHP jika `exit;` dihilangkan).
- **Impact:** Major (kebocoran informasi internal, nama pengguna, daftar file terdaftar).
- **Mitigasi Teknis:** Tambahkan `exit();` tepat setelah setiap `header("location:...");`.
- **Owner yang Disarankan:** Backend Engineer.
- **Residual Risk:** Sangat rendah setelah perbaikan terverifikasi.

---

### RSK-04: Tertimpanya URL File Akibat Race Condition Saat Enkripsi Bersamaan
- **Skenario:** Dua pengguna melakukan enkripsi file secara simultan. Kode menjalankan `INSERT INTO file` dengan `file_url=''`, lalu menjalankan `UPDATE file SET file_url='$file_url' WHERE file_url=''`. File kedua akan menimpa URL file pertama pada database, sehingga file pertama tidak bisa ditemukan lagi.
- **Pihak Terdampak:** Pengguna yang mengunggah file bersamaan.
- **Likelihood:** Medium hingga High pada traffic concurrent.
- **Impact:** Major (kehilangan pointer file fisik di database).
- **Mitigasi Teknis:** Gabungkan pembentukan `file_url` langsung ke dalam satu perintah `INSERT` atomik.
- **Owner yang Disarankan:** Backend Engineer.
- **Residual Risk:** Tidak ada jika di-insert atomik.

---

### RSK-05: Penulisan File Arbitrer (Path Traversal) pada Direktori Server
- **Skenario:** Pengguna jahat mengunggah file dengan manipulasi nama berkas (misal `../../shell.php`). Saat proses dekripsi menulis ke `hasil_dekripsi/$file_name`, file dapat terlempar keluar dari direktori target.
- **Pihak Terdampak:** Server hosting, administrator sistem.
- **Likelihood:** Medium.
- **Impact:** Critical (potensi Remote Code Execution / file overwriting).
- **Mitigasi Teknis:** Gunakan `basename()` dan sanitasi ketat karakter nama file.
- **Owner yang Disarankan:** DevSecOps.
- **Residual Risk:** Rendah setelah validasi `basename()` diterapkan.

---

### RSK-06: Kerusakan Layanan Akibat Alokasi Memori dan Timeout Tanpa Batas (DoS)
- **Skenario:** Penyerang mengunggah berkas besar berulang kali. Script memanggil `ini_set('max_execution_time', -1)` dan `ini_set('memory_limit', -1)`, menghabiskan memori fisik server hingga Apache/PHP crash.
- **Pihak Terdampak:** Ketersediaan seluruh aplikasi (Availability).
- **Likelihood:** Medium.
- **Impact:** Major (server downtime).
- **Mitigasi Teknis:** Batasi memori dan batas eksekusi secara wajar serta pertahankan validasi ukuran file maksimal (3MB).
- **Owner yang Disarankan:** Infrastructure Engineer.
- **Residual Risk:** Minimal jika batasan diterapkan di level aplikasi dan web server.

---

## 3. Kesimpulan Gate Fase 2
Semua risiko bisnis dan teknis telah dipetakan dengan skenario, dampak, mitigasi, dan pemilik yang jelas.  
Temuan P0 (`BUG-01`, `BUG-02`, `SEC-01`, `SEC-02`, `SEC-03`, `SEC-04`) diklasifikasikan sebagai **BLOCKER** untuk kesiapan rilis.  
Status Gate: `PASS` untuk melanjutkan ke **FASE 3 — REMEDIATION PLAN & CHANGE CONTROL**.
