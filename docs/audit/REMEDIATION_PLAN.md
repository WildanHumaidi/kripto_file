# REMEDIATION PLAN & CHANGE CONTROL
**Project:** kripto_file (Aplikasi Kriptografi File Berbasis AES)  
**Date:** 2026-10-22  
**Auditor / Agent:** Hermes Agent (Software Architect, Senior Engineer, QA & DevSecOps Reviewer)  
**Playbook Phase:** FASE 3 — REMEDIATION PLAN & CHANGE CONTROL  
**Status Gate:** APPROVED FOR CONTROLLED IMPLEMENTATION  

---

## 1. Lingkup Remediasi & Prioritas Temuan

### A. Temuan yang Akan Diperbaiki (In-Scope Remediation):
1. **[P0] SEC-01 & BUG-06 (Execution After Redirect):** Tambahkan `exit();` setelah seluruh redirect `header("location: ...")` pada `auth.php`, `dashboard/index.php`, `dashboard/enkripsi.php`, `dashboard/dekripsi.php`, `dashboard/decrypt-file.php`, dan `dashboard/logout.php`.
2. **[P0] SEC-02 (Authentication Guard):** Tambahkan validasi sesi `$_SESSION['username']` di awal pemrosesan `dashboard/encrypt-process.php`, `dashboard/decrypt-process.php`, dan `dashboard/download.php`.
3. **[P0] SEC-03 (SQL Injection):** Validasi dan amankan parameter input (casting integer `(int)$_GET['id_file']`, escaping query, atau prepared statement) pada `dashboard/decrypt-file.php` dan `dashboard/decrypt-process.php`.
4. **[P0] BUG-02 (Race Condition `file_url`):** Masukkan `$file_url` langsung ke dalam satu pernyataan query `INSERT INTO file` atomik pada `dashboard/encrypt-process.php`, menghapus query blind `UPDATE` terpisah.
5. **[P0] BUG-01 (File Integrity & Padding Preservation):** Simpan ukuran byte asli (`$size`) ke dalam record database dan gunakan pemotongan file (`ftruncate`) tepat pada ukuran byte asli saat penulisan plaintext dekripsi.
6. **[P1] BUG-03 (Broken Redirect URLs 404):** Ubah target redirect kegagalan validasi dari `encrypt.php` dan `home.php?encrypt` menjadi `enkripsi.php`.
7. **[P1] BUG-04 (Inkonsistensi Ekstensi vs Pesan UI):** Izinkan ekstensi dokumen standar sesuai pesan UI (`txt`, `docx`, `pptx`, `pdf`, `doc`, `xls`, `xlsx`, `ppt`) atau selaraskan teks pesan secara akurat.
8. **[P1] SEC-05 (Hardcoded Credentials Displayed):** Hapus teks kredensial admin plaintext `<p>User: admin Pass: 1</p>` dari `index.php`. Sediakan file konfigurasi template `.env.example`.
9. **[P1] SEC-06 (Cross-Site Scripting):** Terapkan sanitasi `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` pada output nama user, nama file, dan deskripsi pada antarmuka dashboard.
10. **[P1] SEC-08 (Path Traversal):** Terapkan `basename()` dan sanitasi nama file saat upload dan penulisan cache dekripsi, serta verifikasi bahwa file download berada di direktori yang sah.
11. **[P2] BUG-07 & SEC-11 (Deprecated Database Error Call):** Ganti `mysql_error()` pada `config.php` dengan `mysqli_connect_error()`.
12. **[P2] BUG-08 (Weak Random Filename Prefix):** Perkuat keunikan nama file menggunakan kombinasi timestamp dan string acak yang lebih aman.
13. **[P2] SEC-09 (DoS via Unlimited Resource):** Hapus alokasi tanpa batas `ini_set('memory_limit', -1)` dan gunakan batasan timeout/memori yang rasional.
14. **[P3] BUG-10 (Hotlinked External Avatar):** Ganti hotlink Google Avatar dengan asset avatar lokal proyek.
15. **[P3] BUG-11 (Animation Class Mismatch):** Tambahkan class CSS `.input` pada form login `index.php` agar skrip `main1.js` berfungsi.

### B. Temuan yang Ditunda / Memerlukan Keputusan Owner:
1. **[P1] SEC-04 (Arsitektur Kunci AES & MD5 Password Hashing):**
   - *Alasan Penundaan Re-Hashing:* Mengubah skema hashing user password dari MD5 ke `password_hash()` (Bcrypt/Argon2) atau mengubah algoritma enkripsi AES ECB kustom ke OpenSSL AES-CBC/GCM akan membuat file `.rda` yang sudah dienkripsi sebelumnya tidak dapat didekripsi lagi (breaking change).
   - *Rekomendasi:* Pertahankan kompatibilitas algoritma kustom AES saat ini untuk dekripsi file legacy, dan tawarkan jalur migrasi bertahap ke pemilik proyek.
2. **[P3] SEC-12 (Pembersihan File `assets/decrypt*.php`):**
   - *Status:* Ditandai sebagai kandidat pembersihan. Tidak dihapus secara destruktif tanpa konfirmasi.

---

## 2. Urutan Dependensi & Rencana Eksekusi

1. **Langkah 1: Perbaikan Konfigurasi & Error Handling (`config.php`)**
   - Memperbaiki `mysql_error()` -> `mysqli_connect_error()`.
   - Menyiapkan template lingkungan (`.env.example`) dan skema database kanonis (`db_kkp.sql`).
2. **Langkah 2: Perbaikan Autentikasi & Penghentian Eksekusi (`auth.php`, `index.php`, `dashboard/logout.php`)**
   - Menambahkan `exit();` pasca redirect.
   - Menghapus teks plaintext kredensial default pada UI login.
   - Memperbaiki class input form login.
3. **Langkah 3: Perbaikan Guard Akses Halaman (`dashboard/*.php`)**
   - Memasang `exit();` setelah `header("location:../index.php");` di seluruh halaman dashboard.
   - Memasang sanitasi XSS `htmlspecialchars()` pada output data dinamis.
   - Mengganti external avatar hotlink dengan avatar lokal.
4. **Langkah 4: Perbaikan Alur Enkripsi (`dashboard/encrypt-process.php`)**
   - Memasang session auth guard di baris teratas.
   - Memperbaiki path sanitasi nama file (`basename()`).
   - Memasukkan `$file_url` langsung ke query `INSERT` (menghapus race condition blind update).
   - Menyimpan ukuran byte asli (`$size`).
   - Memperbaiki redirect validasi ke `enkripsi.php`.
5. **Langkah 5: Perbaikan Alur Dekripsi & Download (`dashboard/decrypt-file.php`, `dashboard/decrypt-process.php`, `dashboard/download.php`)**
   - Memasang session auth guard.
   - Memperbaiki celah SQL Injection dengan casting `(int)$_GET['id_file']` dan parameterized escaping.
   - Memasang pemotongan byte eksak (`ftruncate`) pasca dekripsi blok cipher untuk memulihkan integritas 100% bit-for-bit.
   - Memvalidasi path file pada `download.php` agar strictly berada di direktori `hasil_dekripsi/`.
6. **Langkah 6: Verifikasi & Test Regresi End-to-End**
   - Eksekusi automated integration test lokal menguji siklus enkripsi → dekripsi pada `test_txt.txt`, `test_doc.docx`, `test_ppt.pptx`.

---

## 3. Strategi Pengujian Regresi & Kriteria Penerimaan

| Kriteria Penerimaan | Metode Verifikasi | Target Hasil |
|---|---|---|
| Sintaks Seluruh File PHP | `php -l <file>` | 0 Syntax Error |
| Script Termination (EAR) | Unit/Simulasi HTTP tanpa sesi | Respon redirect terhenti, 0 byte sisa eksekusi kode |
| Pencegahan SQL Injection | Test query dengan payload SQL `' OR '1'='1` | Input divalidasi sebagai integer atau escaped, tidak membocorkan data |
| Integritas Dekripsi File | Checksum SHA-256 file asli vs hasil dekripsi | `sha256(orig) === sha256(decrypted)` (100% Match) |
| Pencegahan Path Traversal | Test nama file `../../evil.txt` | File tersanitasi ke `evil.txt` di dalam folder tujuan |
| Broken Redirect Resolution | Simulasi gagal validasi ukuran/ekstensi | Redirect mendarat di `enkripsi.php` (bukan 404) |

---

## 4. Keputusan Gate Fase 3
Rencana kerja telah mendefinisikan perubahan terarah, urutan dependensi, mitigasi risiko regresi, dan batasan scope yang aman tanpa merusak data atau riwayat pengguna.  
Status Gate: `PASS` untuk melanjutkan ke **FASE 4 — CONTROLLED REFACTORING & BUG FIXING**.
