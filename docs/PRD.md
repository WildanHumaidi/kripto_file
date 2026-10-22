# PRODUCT REQUIREMENTS DOCUMENT (PRD)
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Status:** DRAFT — REQUIRES OWNER VALIDATION  
**Version:** 1.0.0  
**Date:** 2026-10-22  
**Reconstructed By:** Hermes Agent  

---

> **Catatan Validasi:** Dokumen ini merupakan hasil rekonstruksi komprehensif berdasarkan kode sumber aktual, basis data, dan perilaku sistem `kripto_file`. Dokumen ini memerlukan tinjauan dan konfirmasi dari Product Owner / Pemilik Proyek terkait kebutuhan bisnis resmi.

---

## 1. Ringkasan Eksekutif & Tujuan Produk

Aplikasi `kripto_file` adalah platform web monolitik yang dirancang untuk mengamankan dokumen digital sensitif menggunakan algoritma kriptografi simetris Advanced Encryption Standard (AES) 128-bit. Sistem memungkinkan pengguna terotentikasi untuk mengunggah file dokumen, mengenkripsinya menjadi cipher berkas (`.rda`), mengunduh berkas terenkripsi, serta mendekripsinya kembali ke format asli menggunakan kata sandi yang sesuai.

### Target Audiens / Persona:
1. **Security Officer / Administrator:** Mengelola akun pengguna, memantau aktivitas sistem, dan mengawasi berkas terenkripsi.
2. **Staff / Dokumen Handler:** Mengunggah dokumen rahasia (laporan, bahan presentasi, naskah) untuk dienkripsi sebelum disimpan atau dikirimkan.

---

## 2. Ruang Lingkup Produk

### In-Scope (Fitur yang Terimplementasi):
- Autentikasi pengguna berbasis sesi dengan username dan password.
- Dashboard metrik (total user terdaftar, total file terenkripsi, total file terdekripsi).
- Form enkripsi berkas dengan kunci kata sandi kustom dan deskripsi opsional.
- Transformasi berkas menjadi cipher `.rda` menggunakan algoritma AES-128 (pure PHP).
- Tabel repositori berkas dengan status enkripsi/dekripsi.
- Form dekripsi berkas dengan verifikasi kata sandi per file.
- Pemulihan berkas asli dan pengunduhan instan via browser.
- Pencatatan aktivitas terakhir pengguna (`last_activity`).

### Non-Goals / Out-of-Scope:
- Multi-tenancy ketat (isolasi total antar departemen) belum diimplementasikan di versi saat ini.
- Enkripsi asimetris (RSA) atau digital signature.
- Integrasi penyimpanan cloud (AWS S3, Google Drive); seluruh file disimpan lokal pada server.

---

## 3. Aturan Bisnis & Alur Kerja (Business Rules)

1. **BR-01 (Akses Terotentikasi):** Seluruh fitur dashboard, enkripsi, dekripsi, dan pengunduhan hanya dapat diakses oleh pengguna yang memiliki sesi aktif.
2. **BR-02 (Batasan Format Berkas):** Sistem mendukung format berkas dokumen: `.txt`, `.docx`, `.pptx`, `.pdf`, `.doc`, `.xls`, `.xlsx`, `.ppt`. Format file eksekusi atau biner berbahaya di luar daftar tersebut ditolak.
3. **BR-03 (Batasan Ukuran Berkas):** Ukuran berkas maksimum yang diizinkan untuk enkripsi adalah 3 Megabytes (3.145.728 byte).
4. **BR-04 (Integritas Bit-for-Bit):** File hasil dekripsi wajib memiliki ukuran dan hash checksum yang identik 100% dengan file asli sebelum dienkripsi.
5. **BR-05 (Satu Kunci per File):** Setiap operasi enkripsi menghasilkan file cipher independen dengan kunci yang diturunkan dari kata sandi file tersebut.

---

## 4. Functional Requirements (FR)

| ID | Modul | Deskripsi Kebutuhan | Acceptance Criteria |
|---|---|---|---|
| FR-001 | Autentikasi | Login sistem menggunakan username dan password | Kredensial valid mengarahkan ke dashboard; kredensial invalid menampilkan alert penolakan |
| FR-002 | Autentikasi | Logout sistem mengakhiri sesi pengguna | Sesi dan cookie sesi dihancurkan, pengguna dialihkan ke halaman login publik |
| FR-003 | Dashboard | Menampilkan ringkasan statistik dan daftar file | Menampilkan jumlah user, file terenkripsi (status 1), dan file terdekripsi (status 2) |
| FR-004 | Enkripsi | Mengunggah dan mengenkripsi berkas menggunakan AES | File diproses per blok 16-byte, menghasilkan berkas `.rda` di `hasil_ekripsi/` dan record di database |
| FR-005 | Dekripsi | Memverifikasi password dan mendekripsi berkas `.rda` | Password cocok memicu dekripsi blok AES, memotong padding, dan menghasilkan file di `hasil_dekripsi/` |
| FR-006 | Download | Mengunduh file hasil dekripsi | Header binary transfer dikirim ke browser dengan nama file asli, session download dibersihkan |

---

## 5. Non-Functional Requirements (NFR)

1. **NFR-01 (Security - Access Control):** Tidak ada kebocoran halaman terproteksi melalui Execution After Redirect (EAR). Semua endpoint wajib memverifikasi sesi aktif.
2. **NFR-02 (Security - Injection Defense):** Seluruh interaksi database menggunakan Parameterized Prepared Statements (`mysqli_prepare`).
3. **NFR-03 (Security - File Traversal Defense):** Nama file masukan dan keluaran disanitasi menggunakan `basename()`, mencegah traversal direktori (`../`).
4. **NFR-04 (Performance):** Proses enkripsi dan dekripsi berkas berukuran hingga 3MB selesai dalam waktu kurang dari 5 detik pada runtime lokal.
5. **NFR-05 (Compatibility):** Kompatibel penuh dengan PHP 7.4 hingga PHP 8.2+ dan MariaDB 10.4+.

---

## 6. PRD-to-Implementation Alignment

| Kebutuhan Terkonfirmasi | Status Implementasi | Catatan / Keputusan Diperlukan |
|---|---|---|
| Enkripsi AES Dokumen | Terimplementasi & Terverifikasi | Algoritma AES-128 kustom kompatibel PHP 8 |
| Integritas File Dekripsi | Terimplementasi & Terverifikasi | Truncation exact bytes memulihkan docx/pptx |
| Keamanan Akses Sesi | Terimplementasi & Terverifikasi | EAR guard & session check terpasang |
| Manajemen Multi-Role | Parsial (Hanya field `job_title`) | Belum ada perbedaan wewenang admin vs user biasa |
| Standar Hash Password Akun | MD5 (Perlu Keputusan Owner) | Disarankan migrasi bertahap ke `password_hash()` |
