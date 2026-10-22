# SECURITY POLICY & VULNERABILITY REPORTING
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Date:** 2026-10-22  

---

## 1. Kebijakan Keamanan Sistem

Pengembang berkomitmen menjaga keamanan dan kerahasiaan data pengguna. Sistem telah diaudit dan diperkuat terhadap:
- **Broken Access Control & EAR:** Semua halaman terlindung dengan penghentian eksekusi ketat jika sesi tidak terotentikasi.
- **SQL Injection:** Seluruh query sensitif menggunakan parameterized prepared statements.
- **Cross-Site Scripting (XSS):** Semua data dinamis dari database disaring melalui `htmlspecialchars()`.
- **Path Traversal:** Nama file masukan dan keluaran disanitasi menggunakan fungsi kanonis `basename()` dan verifikasi `realpath()`.

---

## 2. Praktik Operasional yang Disarankan

1. **Rotasi Password:** Selalu ganti password default admin (`1`) setelah instalasi awal basis data.
2. **Kerahasiaan Berkas:** Jangan publikasikan direktori `dashboard/hasil_ekripsi/` dan `dashboard/hasil_dekripsi/` ke akses web publik langsung; batasi melalui konfigurasi web server (.htaccess / Nginx location block).
3. **Environment:** Gunakan variabel lingkungan melalui `.env` untuk menghindari penyimpanan kredensial database di file source code.

---

## 3. Pelaporan Kerentanan (Reporting a Vulnerability)

Jika Anda menemukan celah keamanan atau kerentanan baru pada aplikasi ini:
1. Jangan mempublikasikan detail celah keamanan secara publik (issue tracker publik).
2. Kirimkan laporan rinci yang memuat deskripsi celah, langkah reproduksi aman, serta potensi dampak kepada tim keamanan atau pemilik repository melalui saluran privat.
3. Tim pengembang akan meninjau dan merilis perbaikan secepat mungkin.
