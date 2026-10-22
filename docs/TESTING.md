# TESTING & QUALITY VERIFICATION GUIDE
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Date:** 2026-10-22  

---

## 1. Ikhtisar Rangkaian Pengujian (Test Suites)

Proyek ini dilengkapi dengan suite pengujian otomatis mandiri yang menguji integritas kriptografi, kepatuhan padding, mitigasi celah keamanan (SQL Injection), dan kontrol akses sesi (EAR Guard).

---

## 2. Cara Menjalankan Pengujian

### 2.1 Pemeriksaan Sintaks PHP (PHP-Lint)
Memeriksa integritas sintaks pada seluruh file PHP:
```bash
for f in auth.php config.php index.php dashboard/*.php assets/*.php; do [ -f "$f" ] && php -l "$f"; done
```
*Kriteria Sukses:* Seluruh 15 file menghasilkan output `No syntax errors detected`.

### 2.2 Uji Integritas Kriptografi (Cryptography & Padding Test)
Menguji proses round-trip enkripsi → dekripsi pada dokumen teks, dokumen Microsoft Word (.docx), dan presentasi PowerPoint (.pptx):
```bash
php tests/test_cryptography.php
```
*Kriteria Sukses:* Checksum SHA-256 dan ukuran byte file hasil dekripsi 100% identik dengan file asli (`100% Bit-for-bit match`).

### 2.3 Uji Kontrol Akses & Keamanan (Security Access Test)
Menguji proteksi Execution After Redirect (EAR) pada seluruh halaman dashboard dan pencegahan SQL Injection:
```bash
php tests/test_security_access.php
```
*Kriteria Sukses:* Seluruh 5 halaman dashboard memutus eksekusi segera saat tanpa sesi (`[PASS] EAR Guard active`), dan payload SQL Injection diblokir secara aman.

---

## 3. Matriks Hasil Pengujian Aktual

| Kategori Uji | File Runner | Status | Evidence Aktual |
|---|---|---|---|
| Syntax Check (15 Files) | PHP CLI Built-in | `PASS` | 0 Syntax Error |
| Plain Text AES Round-trip | `tests/test_cryptography.php` | `PASS` | SHA-256 match, 22 bytes |
| Word DOCX AES Round-trip | `tests/test_cryptography.php` | `PASS` | SHA-256 match, 11449 bytes |
| PPTX AES Round-trip | `tests/test_cryptography.php` | `PASS` | SHA-256 match, 30874 bytes |
| EAR Guard Protection | `tests/test_security_access.php` | `PASS` | Output truncated immediately |
| SQL Injection Defense | `tests/test_security_access.php` | `PASS` | Evaluated strictly as integer / prepared statement |
| Database Initialization | `db_kkp.sql` | `PASS` | Schema & table creation verified on MariaDB |
