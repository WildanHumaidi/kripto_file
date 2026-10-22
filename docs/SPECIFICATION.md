# TECHNICAL SPECIFICATION & ROUTING CONTRACT
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Date:** 2026-10-22  
**Status:** Complete  

---

## 1. Daftar Endpoint & Routing Aplikasi

| Metode | URL / Path | Akses | Tanggung Jawab |
|---|---|---|---|
| `GET` | `/index.php` | Publik | Halaman login sistem |
| `POST` | `/auth.php` | Publik | Handler otentikasi login pengguna |
| `GET` | `/dashboard/index.php` | Terotentikasi | Halaman beranda dashboard & tabel arsip |
| `GET` | `/dashboard/enkripsi.php` | Terotentikasi | Halaman formulir unggah & enkripsi |
| `POST` | `/dashboard/encrypt-process.php` | Terotentikasi | Handler pemrosesan enkripsi berkas |
| `GET` | `/dashboard/dekripsi.php` | Terotentikasi | Halaman tabel berkas untuk didekripsi |
| `GET` | `/dashboard/decrypt-file.php` | Terotentikasi | Halaman input password berkas target |
| `POST` | `/dashboard/decrypt-process.php` | Terotentikasi | Handler pemrosesan dekripsi berkas |
| `GET` | `/dashboard/download.php` | Terotentikasi | Handler streaming pengunduhan berkas |
| `GET` | `/dashboard/logout.php` | Terotentikasi | Handler penghancuran sesi & logout |

---

## 2. Spesifikasi Endpoint Rinci

### 2.1 POST `/auth.php`
Memproses kredensial login pengguna.

- **Header:** `Content-Type: application/x-www-form-urlencoded`
- **Payload:**
  - `username` (string, required): Username akun pengguna.
  - `password` (string, required): Password akun pengguna.
  - `login` (string, required): Flag submit form.
- **Validasi:** Field tidak boleh kosong.
- **Respon Sukses:** HTTP 302 Redirect ke `dashboard/index.php`, session `$_SESSION['username']` diinisialisasi dengan ID sesi baru (`session_regenerate_id`).
- **Respon Gagal:** JavaScript alert (`Username atau Password Salah!`) dan redirect ke `index.php`.

---

### 2.2 POST `/dashboard/encrypt-process.php`
Menerima file dokumen, mengenkripsi per blok 16-byte, menyimpan cipher `.rda`, dan mencatat metadata ke database.

- **Autentikasi:** Wajib sesi aktif (`$_SESSION['username']`). Jika tidak ada, redirect ke `../index.php` dengan terminasi eksekusi.
- **Header:** `Content-Type: multipart/form-data`
- **Payload:**
  - `file` (file binary, required): File dokumen yang akan dienkripsi.
  - `pwdfile` (string, required): Password/kunci enkripsi file.
  - `desc` (string, optional): Keterangan atau catatan file.
  - `encrypt_now` (string, required): Flag submit form.
- **Validasi:**
  - Ekstensi file wajib salah satu dari: `txt`, `docx`, `pptx`, `pdf`, `doc`, `xls`, `xlsx`, `ppt`.
  - Ukuran file maksimal: 3.145.728 byte (3 MB).
- **Proses Kriptografi:**
  - Kunci diturunkan dari `substr(md5(pwdfile), 0, 16)`.
  - Blok 16-byte dienkripsi via `AES::encrypt()`.
- **Respon Sukses:** JavaScript alert (`Enkripsi Berhasil..`) dan redirect ke `enkripsi.php`.
- **Respon Gagal:** JavaScript alert dengan pesan kegagalan spesifik dan redirect aman ke `enkripsi.php`.

---

### 2.3 POST `/dashboard/decrypt-process.php`
Memverifikasi kunci berkas dan mendekripsi cipher `.rda` kembali menjadi dokumen asli.

- **Autentikasi:** Wajib sesi aktif (`$_SESSION['username']`).
- **Header:** `Content-Type: application/x-www-form-urlencoded`
- **Payload:**
  - `fileid` (integer, required): ID record berkas (`id_file`).
  - `pwdfile` (string, required): Password pembuka berkas.
  - `decrypt_now` (string, required): Flag submit form.
- **Validasi:**
  - `fileid` divalidasi sebagai integer.
  - Kunci turunan dicocokkan dengan kolom `password` pada tabel `file` via prepared statement.
- **Proses Kriptografi:**
  - File `.rda` dibaca dan didekripsi per blok 16-byte via `AES::decrypt()`.
  - File dipotong tepat pada ukuran byte asli (`ftruncate`) jika metadata ukuran byte asli tersedia.
  - Status record diperbarui menjadi `'2'` (Sudah Didekripsi).
  - Path hasil didelegasikan ke `$_SESSION['download']`.
- **Respon Sukses:** JavaScript membuka tab baru `/dashboard/download.php`, memberikan alert (`Berhasil mendekripsi file.`), dan mengarahkan halaman aktif ke `dekripsi.php`.
- **Respon Gagal:** JavaScript alert (`Maaf, Password tidak sesuai.`) dan redirect kembali ke `decrypt-file.php?id_file=<id>`.

---

### 2.4 GET `/dashboard/download.php`
Mengalirkan (streaming) berkas hasil dekripsi langsung sebagai attachment binary browser.

- **Autentikasi:** Wajib sesi aktif (`$_SESSION['username']`).
- **Parameter Sesi:** `$_SESSION['download']` memuat path relatif berkas hasil dekripsi.
- **Validasi Keamanan:**
  - Memverifikasi keberadaan variabel sesi.
  - Memeriksa resolusi path kanonis (`realpath()`) untuk memastikan berkas strictly berada di dalam folder `hasil_dekripsi/` (Path Traversal Protection).
- **Header Respon:**
  - `Content-Type: application/octet-stream`
  - `Content-Disposition: attachment; filename="<nama_file_asli>"`
  - `Content-Length: <filesize_in_bytes>`
- **Pembersihan Pasca Kirim:** Variabel sesi `$_SESSION['download']` di-unset segera setelah transfer berkas dimulai untuk mencegah pengunduhan ganda yang tidak disengaja.
