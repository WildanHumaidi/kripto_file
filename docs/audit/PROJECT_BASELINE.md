# PROJECT BASELINE REPORT
**Project:** kripto_file (Aplikasi Kriptografi File Berbasis AES)  
**Date:** 2026-10-22  
**Auditor / Agent:** Hermes Agent (Software Architect, Senior Engineer, QA & DevSecOps Reviewer)  
**Playbook Phase:** FASE 0 — PROJECT DISCOVERY, BASELINE & GIT SAFETY  
**Status Gate:** PASS (Lanjut ke Fase 1)  

---

## 1. Identifikasi Workspace & Struktur Proyek

- **Root Directory:** `E:\Projects\kripto_file`
- **Tipe Proyek:** Web Application (Monolithic Server-Rendered PHP Native)
- **Tech Stack:**
  - **Language / Runtime:** PHP Native (terdeteksi PHP 8.2.12 CLI pada host `C:\xampp\php\php.exe`)
  - **Database:** MariaDB / MySQL via `mysqli` (terdeteksi MariaDB 10.4.32 pada host `C:\xampp\mysql\bin\mysql.exe`)
  - **Cryptography Engine:** Custom AES Pure-PHP (`dashboard/AES.php`)
  - **Frontend:** HTML5, CSS3, JavaScript (jQuery 2.1.4, Nalika Dashboard Admin Template, Bootstrap, FontAwesome)
  - **Dependency Manager:** Tidak ada `composer.json` (semua library dipaketkan statis)
- **Struktur Direktori:**
  - `.` : Entry login (`index.php`, `auth.php`), konfigurasi (`config.php`), file sampel uji (`test_doc.docx`, `test_ppt.pptx`, `test_txt.txt`), dan panduan `PLAYBOOK.md`.
  - `assets/` : Static asset halaman login (CSS, JS, gambar, font) serta duplikat file dekripsi tak terpakai (`decrypt.php`, `decrypt-file.php`, `decrypt-process.php`).
  - `dashboard/` : Area terotentikasi meliputi manajemen enkripsi (`enkripsi.php`, `encrypt-process.php`), dekripsi (`dekripsi.php`, `decrypt-file.php`, `decrypt-process.php`), download (`download.php`), library AES (`AES.php`), serta penyimpanan cipher/plain (`hasil_ekripsi/`, `hasil_dekripsi/`).

---

## 2. Baseline Git & Perlindungan Workspace

Pemeriksaan status Git read-only yang dijalankan:
- `git status --short --branch`
- `git branch --show-current`
- `git log -5 --oneline`
- `git remote -v`
- `git diff --stat`

**Hasil Eksekusi:**
- Exit Code: `128`
- Output: `fatal: not a git repository (or any of the parent directories): .git`

**Analisis & Guardrail:**
- Workspace saat ini **bukan repository Git**.
- Sesuai **Playbook Klausul 0.2 & 8.3**, agent dilarang menjalankan `git init` secara otomatis tanpa otorisasi atau sebelum fase standardisasi Git yang disetujui.
- Seluruh file asli pengguna harus dilindungi secara ketat agar tidak ada yang terhapus, tertimpa secara merusak, atau hilang selama proses audit dan remediasi.

---

## 3. Baseline Kualitas Aplikasi Awal

### 3.1 Syntax & Linting Check (PHP Lint)
- **Command:** `for f in auth.php config.php index.php dashboard/*.php assets/*.php; do [ -f "$f" ] && php -l "$f"; done`
- **Exit Code:** `0`
- **Hasil:**
  - `auth.php`: No syntax errors detected.
  - `config.php`: No syntax errors detected.
  - `index.php`: No syntax errors detected.
  - `dashboard/AES.php`: No syntax errors detected.
  - `dashboard/decrypt-file.php`: No syntax errors detected.
  - `dashboard/decrypt-process.php`: No syntax errors detected.
  - `dashboard/dekripsi.php`: No syntax errors detected.
  - `dashboard/download.php`: No syntax errors detected.
  - `dashboard/encrypt-process.php`: No syntax errors detected.
  - `dashboard/enkripsi.php`: No syntax errors detected.
  - `dashboard/index.php`: No syntax errors detected.
  - `dashboard/logout.php`: No syntax errors detected.
  - `assets/decrypt.php`: No syntax errors detected.
  - `assets/decrypt-file.php`: No syntax errors detected.
  - `assets/decrypt-process.php`: No syntax errors detected.
- **Status:** `PASS` (Sintaks valid pada PHP 8.2.12).

### 3.2 Automated Tests
- **Status:** `NOT RUN`
- **Alasan:** Tidak tersedia test runner (PHPUnit), test suite, atau CI harness pada repositori awal.

### 3.3 Database State
- **Status:** `BLOCKED` (Runtime query)
- **Alasan:** Layanan MariaDB host aktif (`mysqld.exe`), namun database `db_kkp` yang didefinisikan pada `config.php` belum dibuat pada database engine lokal. Tidak ada file `.sql` bawaan di workspace.

---

## 4. Daftar Dokumen & Sumber Kebenaran

1. `PLAYBOOK.md` — Pedoman operasional audit, remediasi, pengujian, dan standardisasi.
2. Source code aplikasi (`*.php`, file statis template, dan file sample uji).
3. Tidak ditemukan dokumen resmi PRD, SRS, ERD, atau README bawaan. Draft PRD dan ERD akan direkonstruksi dari kode sumber pada fase dokumentasi.

---

## 5. Ringkasan Risiko Awal

1. **R-01 (Git Safety):** Workspace belum memiliki riwayat Git; pengubahan kode harus berhati-hati agar perubahan pengguna tidak terhapus.
2. **R-02 (Database Schema Missing):** Tidak ada dump skema database `db_kkp`; skema tabel `users` dan `file` harus direkonstruksi secara aman dari query SQL aplikasi.
3. **R-03 (Hardcoded Credentials Displayed):** Halaman `index.php` memaparkan kredensial default (`User: admin Pass: 1`).
4. **R-04 (Deprecated / Incompatible API Call):** `config.php` memanggil `mysql_error()` padahal menggunakan driver `mysqli`.
5. **R-05 (Stray Duplicates in Assets):** Folder `assets/` memuat file PHP (`decrypt.php`, `decrypt-file.php`, `decrypt-process.php`) yang terputus dari `AES.php`.
6. **R-06 (Security & Cryptography Correctness):** Implementasi kunci enkripsi diturunkan dari `substr(md5($password), 0, 16)` dan penanganan padding block cipher perlu diverifikasi integritasnya terhadap file binary (Word, PPTX).

---

## 6. Keputusan Gate Fase 0
- **Kondisi:** Root workspace, tech stack, status Git, pemeriksaan sintaks dasar, dan guardrails keselamatan telah teridentifikasi dan tercatat dengan bukti konkret.
- **Gate Status:** `PASS`
- **Langkah Berikutnya:** Memasuki **FASE 1 — RECONNAISSANCE & SECURITY AUDIT**.
