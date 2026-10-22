# SYSTEM ARCHITECTURE & CRYPTOGRAPHIC DESIGN
**Project:** kripto_file (Aplikasi Web Kriptografi File Berbasis AES-128)  
**Date:** 2026-10-22  
**Status:** Canonical Verified Architecture  

---

## 1. Arsitektur Komponen Tingkat Tinggi

Aplikasi `kripto_file` mengadopsi arsitektur monolitik PHP Native dengan pemisahan fungsional berbasis modul direktori.

```mermaid
graph TD
    Client[Web Browser / User Agent] -->|HTTP Request| WebRoot[Web Root / Entrypoint]
    
    subgraph Presentation & Authentication
        WebRoot --> LoginUI[index.php / auth.php]
        LoginUI --> SessionStore[(PHP Session Storage)]
    end
    
    subgraph Authenticated Dashboard Area
        SessionStore -.->|Validasi Sesi| DashGuard[Session Guard / EAR Protection]
        DashGuard --> DashHome[dashboard/index.php]
        DashGuard --> EncryptPage[dashboard/enkripsi.php]
        DashGuard --> DecryptPage[dashboard/dekripsi.php]
        DashGuard --> DecryptForm[dashboard/decrypt-file.php]
    end

    subgraph Processing Engines
        EncryptPage -->|Form Multipart POST| EncryptProc[dashboard/encrypt-process.php]
        DecryptForm -->|Form POST Key| DecryptProc[dashboard/decrypt-process.php]
        DecryptProc -->|Session Token| Downloader[dashboard/download.php]
        
        EncryptProc --> AESModule[dashboard/AES.php Engine]
        DecryptProc --> AESModule[dashboard/AES.php Engine]
    end

    subgraph Data & Storage Persistence
        EncryptProc -->|Record Metadata| DB[(MariaDB db_kkp)]
        DecryptProc -->|Query & Update Status| DB
        LoginUI -->|Otentikasi Kredensial| DB
        
        EncryptProc -->|Write Cipher .rda| EncStore[dashboard/hasil_ekripsi/]
        DecryptProc -->|Read Cipher .rda| EncStore
        DecryptProc -->|Write Plain Document| DecStore[dashboard/hasil_dekripsi/]
        Downloader -->|Stream Plain Document| DecStore
    end
```

---

## 2. Alur Enkripsi Berkas (Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna (Browser)
    participant EncUI as dashboard/enkripsi.php
    participant EncProc as dashboard/encrypt-process.php
    participant AES as dashboard/AES.php
    participant DB as MariaDB (db_kkp)
    participant Storage as Storage (hasil_ekripsi/)

    User->>EncUI: Buka Form & Unggah Berkas + Kunci Password
    EncUI->>EncProc: POST /dashboard/encrypt-process.php (Multipart)
    EncProc->>EncProc: Validasi Sesi Pengguna ($_SESSION['username'])
    EncProc->>EncProc: Validasi Ekstensi Berkas & Ukuran (Maks 3MB)
    EncProc->>EncProc: Sanitasi Nama Berkas (basename) & Bentuk Nama Unik
    EncProc->>DB: INSERT INTO file (Metadata, file_url, exact_size, key)
    loop Setiap Blok 16-Byte
        EncProc->>AES: encrypt(block)
        AES-->>EncProc: 16-byte ciphertext block
        EncProc->>Storage: fwrite(cipher block ke .rda)
    end
    EncProc-->>User: Alert "Enkripsi Berhasil" & Redirect ke enkripsi.php
```

---

## 3. Alur Dekripsi & Pengunduhan Berkas (Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna (Browser)
    participant DecForm as dashboard/decrypt-file.php
    participant DecProc as dashboard/decrypt-process.php
    participant AES as dashboard/AES.php
    participant DB as MariaDB (db_kkp)
    participant Storage as Storage (hasil_ekripsi / hasil_dekripsi)
    participant Download as dashboard/download.php

    User->>DecForm: Pilih Berkas & Masukkan Password Dekripsi
    DecForm->>DecProc: POST /dashboard/decrypt-process.php (fileid, pwdfile)
    DecProc->>DecProc: Validasi Sesi Pengguna
    DecProc->>DB: SELECT * FROM file WHERE id_file = ? AND password = ?
    alt Password Salah / Tidak Cocok
        DecProc-->>User: Alert "Password Tidak Sesuai"
    else Password Valid
        DecProc->>DB: UPDATE file SET status = '2' WHERE id_file = ?
        loop Setiap Blok Cipher 16-Byte
            Storage->>DecProc: fread(cipher block dari .rda)
            DecProc->>AES: decrypt(cipher block)
            AES-->>DecProc: 16-byte plaintext block
            DecProc->>Storage: fwrite(plaintext ke hasil_dekripsi/)
        end
        DecProc->>Storage: ftruncate(file, exact_byte_size) [Pemulihan Integritas Bit-for-Bit]
        DecProc->>User: Set $_SESSION['download'] & Buka download.php
        User->>Download: GET /dashboard/download.php
        Download->>Download: Validasi Sesi & Sanitasi Path Traversal
        Download->>User: Stream Binary File Asli (Content-Disposition: attachment)
    end
```

---

## 4. Desain Mesin Kriptografi (AES-128 Implementation)

Modul `dashboard/AES.php` mengimplementasikan spesifikasi Federal Information Processing Standards (FIPS 197) untuk Rijndael Cipher Block:
1. **Block Length ($Nb$):** 4 words (16 bytes = 128 bits).
2. **Key Length ($Nk$):** 4 words (16 bytes = 128 bits).
3. **Number of Rounds ($Nr$):** 10 putaran ($Nk + Nb + 2$).
4. **Transformasi Putaran:**
   - `SubByte`: Substitusi non-linear byte menggunakan S-Box statis.
   - `ShiftRow`: Pergeseran sirkular baris matriks status (baris 1 shift 0, baris 2 shift 1, baris 3 shift 2, baris 4 shift 3).
   - `MixColumns`: Perkalian polinomial modulo $x^4 + 1$ di atas Galois Field $GF(2^8)$ menggunakan log table dan anti-log table.
   - `AddRoundKey`: Bitwise XOR antara matriks status dan sub-key jadwal kunci ($w$).
5. **Kompatibilitas Runtime:**
   Modul telah diperbarui untuk mendukung konstruktor standar PHP 8 (`__construct`) sekaligus mempertahankan alias `AES()` untuk backward compatibility.
6. **Integritas Padding:**
   Sistem enkripsi mengisi byte nol (`\0`) pada blok terakhir yang kurang dari 16 byte. Pemotongan pasca dekripsi menggunakan metadata ukuran eksak (`ftruncate`) memastikan berkas hasil dekripsi 100% bit-for-bit identik dengan file asli.
