# SETUP & INSTALLATION GUIDE
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Date:** 2026-10-22  

---

## 1. Prasyarat Sistem

- **Web Server:** Apache 2.4+ (misal via XAMPP / Laragon / Nginx + PHP-FPM).
- **PHP:** Versi 7.4 hingga PHP 8.2+ (disarankan PHP 8.1 / 8.2).
- **Ekstensi PHP Wajib:** `mysqli`, `session`, `mbstring`.
- **Database:** MySQL 5.7+ atau MariaDB 10.3+.

---

## 2. Langkah Instalasi

### 2.1 Menyiapkan Berkas Proyek
Tempatkan folder `kripto_file` ke dalam direktori root web server Anda:
- Pada XAMPP Windows: `C:\xampp\htdocs\kripto_file`
- Pada Linux: `/var/www/html/kripto_file`

### 2.2 Membuat & Mengimpor Basis Data
1. Buka terminal MySQL atau phpMyAdmin.
2. Impor skema resmi yang disediakan di file `db_kkp.sql`:
   ```bash
   mysql -u root -p < db_kkp.sql
   ```
   *Skrip ini akan otomatis membuat database `db_kkp`, tabel `users`, tabel `file`, serta 1 akun pengguna default.*

### 2.3 Konfigurasi Lingkungan (.env)
Aplikasi membaca variabel lingkungan atau menggunakan konfigurasi default di `config.php`.
Salin file template `.env.example`:
```bash
cp .env.example .env
```
Sesuaikan parameter koneksi database jika diperlukan:
- `DB_HOST`: Host database (default: `localhost`)
- `DB_USER`: Pengguna database (default: `root`)
- `DB_PASS`: Password database (default: kosong `""`)
- `DB_NAME`: Nama database (default: `db_kkp`)

### 2.4 Hak Akses Folder Penyimpanan Berkas
Pastikan direktori penyimpanan berkas memiliki izin tulis (writeable):
- `dashboard/hasil_ekripsi/`
- `dashboard/hasil_dekripsi/`

---

## 3. Menjalankan Aplikasi

Akses aplikasi melalui browser:
`http://localhost/kripto_file/`

### Kredensial Awal Sistem:
- **Username:** `admin`
- **Password:** `1` *(Disarankan segera diperbarui di database pada environment non-lokal)*
