# Kripto File — Aplikasi Kriptografi File Berbasis AES-128

Aplikasi web untuk enkripsi dan dekripsi berkas dokumen digital secara aman menggunakan algoritma simetris **Advanced Encryption Standard (AES-128)** berbasis Rijndael block cipher murni (Pure PHP).

---

## 📌 Ringkasan Proyek & Fitur Utama

- **Enkripsi Dokumen:** Mengamankan file dokumen (`.txt`, `.docx`, `.pptx`, `.pdf`, `.doc`, `.xls`, `.xlsx`, `.ppt`) menjadi berkas cipher biner berekstensi `.rda`.
- **Dekripsi Bit-for-Bit Identik:** Memulihkan kembali file cipher ke bentuk aslinya dengan integritas data eksak 100% (panjang byte dan hash SHA-256 identik).
- **Pengunduhan Fleksibel (.RDA & File Asli):** Pengguna dapat mengunduh berkas cipher `.rda` kapan saja dan mengunduh ulang berkas asli hasil dekripsi.
- **Penghapusan Berkas Aman:** Penghapusan berkas fisik (`.rda` dan dekripsi) beserta rekaman database dengan konfirmasi aman.
- **Audit Trail & Log Aktivitas:** Pencatatan otomatis seluruh riwayat aktivitas sistem (login, enkripsi, dekripsi, unduhan, hapus) lengkap dengan IP address dan timestamp.
- **Pengaturan Profil & Ubah Password:** Antarmuka bagi pengguna untuk memperbarui nama lengkap, jabatan, dan kata sandi akun secara mandiri.
- **Privasi Berkas (Multi-User Isolation):** Pengguna non-admin hanya dapat melihat dan mengelola berkas miliknya sendiri.
- **Transparansi Checksum SHA-256:** Menampilkan nilai hash SHA-256 dari berkas `.rda` untuk pembuktian integritas matematis.
- **Keamanan Terverifikasi:** Dilengkapi proteksi Execution After Redirect (EAR), Parameterized Prepared Statements (anti-SQL Injection), sanitasi XSS (`htmlspecialchars`), dan perlindungan Path Traversal (`basename`).

---

## 🛠️ Tech Stack

- **Backend:** PHP Native (kompatibel penuh PHP 7.4 s.d. PHP 8.2+)
- **Database:** MySQL / MariaDB (via ekstensi `mysqli`)
- **Cryptography Engine:** Custom Rijndael AES-128 Engine (`dashboard/AES.php`)
- **Frontend:** Bootstrap, HTML5, CSS3, jQuery 2.1.4, Nalika Admin Dashboard Template
- **Testing:** Automated test suite mandiri (`tests/test_cryptography.php`, `tests/test_security_access.php`)

---

## 📁 Struktur Direktori

```text
kripto_file/
├── assets/                  # Asset statis frontend login (CSS, JS, Fonts, Gambar)
├── dashboard/               # Modul terotentikasi aplikasi
│   ├── css/, js/, img/     # Asset template dashboard Nalika
│   ├── hasil_dekripsi/      # Direktori output berkas hasil dekripsi
│   ├── hasil_ekripsi/       # Direktori penyimpanan berkas cipher (.rda)
│   ├── AES.php              # Implementasi mesin algoritma AES-128
│   ├── audit_helper.php     # Helper pencatatan riwayat audit trail
│   ├── decrypt-file.php     # Halaman formulir input password dekripsi
│   ├── decrypt-process.php  # Pemroses dekripsi blok AES & pemulihan file
│   ├── dekripsi.php         # Tabel repositori file terenkripsi
│   ├── delete-file.php      # Handler penghapusan berkas fisik & record
│   ├── download.php         # Handler streaming unduhan berkas instan
│   ├── download-file.php    # Handler unduhan berkas .rda dan hasil dekripsi
│   ├── encrypt-process.php  # Pemroses enkripsi dokumen & pembentukan .rda
│   ├── enkripsi.php         # Formulir unggah & enkripsi dokumen
│   ├── index.php            # Halaman beranda utama dashboard
│   ├── logout.php           # Handler penghancuran sesi & logout
│   ├── logs.php             # Halaman riwayat audit trail sistem
│   └── profile.php          # Halaman pengaturan profil & ganti password
├── docs/                    # Dokumentasi teknis & arsitektur
│   ├── audit/               # Laporan baseline, bug audit, security, risiko
│   ├── ARCHITECTURE.md      # Desain arsitektur & sequence diagram
│   ├── ERD.md               # Entity Relationship Diagram & Kamus Data
│   ├── PRD.md               # Product Requirements Document (Draft Rekonstruksi)
│   ├── SECURITY.md          # Kebijakan keamanan & pelaporan kerentanan
│   ├── SETUP.md             # Panduan instalasi dan deployment
│   ├── SPECIFICATION.md     # Spesifikasi endpoint & API contract
│   └── TESTING.md           # Panduan dan matriks hasil pengujian
├── tests/                   # Suite pengujian otomatis mandiri
│   ├── test_cryptography.php    # Uji integritas round-trip AES & padding
│   ├── test_security_access.php # Uji EAR guard & mitigasi SQL Injection
│   └── test_new_features.php    # Uji download, delete, profile, logs, privasi
├── .env.example             # Template konfigurasi environment
├── .gitignore               # Konfigurasi pengabaian file Git
├── auth.php                 # Handler autentikasi kredensial login
├── config.php               # Konfigurasi koneksi database
├── db_kkp.sql               # Skema DDL dan data awal database resmi
├── index.php                # Halaman muka formulir login
└── PLAYBOOK.md              # Pedoman operasional audit & standardisasi
```

---

## 🚀 Panduan Instalasi & Menjalankan

### 1. Prasyarat
- Web Server Apache (XAMPP / Laragon / LAMP)
- PHP 7.4+ atau PHP 8.0 - 8.2+
- MariaDB / MySQL Server

### 2. Impor Database
Jalankan file SQL skema resmi ke server database Anda:
```bash
mysql -u root -p < db_kkp.sql
```

### 3. Konfigurasi Lingkungan
Salin file `.env.example` ke `.env` (atau sesuaikan pada `config.php`):
```bash
cp .env.example .env
```

### 4. Buka Aplikasi di Browser
Akses: `http://localhost/kripto_file/`
- **Username Default:** `admin`
- **Password Default:** `1`

---

## 🧪 Pengujian Otomatis (Testing)

Jalankan test suite dari terminal root proyek:

```bash
# 1. Pengecekan sintaks seluruh file PHP
for f in auth.php config.php index.php dashboard/*.php assets/*.php; do [ -f "$f" ] && php -l "$f"; done

# 2. Uji integritas kriptografi & pemulihan eksak padding (TXT, DOCX, PPTX)
php tests/test_cryptography.php

# 3. Uji proteksi kontrol akses (EAR Guard) dan mitigasi SQL Injection
php tests/test_security_access.php
```

---

## 📄 Lisensi
Didistribusikan di bawah lisensi MIT. Lihat file [LICENSE](LICENSE) untuk informasi lebih lanjut.
