<?php
session_start();
include "../config.php";   // Memasukkan koneksi
include "AES.php";          // Memasukkan file AES
include "audit_helper.php"; // Memasukkan audit logger

if (empty($_SESSION['username'])) {
    header("location: ../index.php");
    exit();
}

if (isset($_POST['encrypt_now'])) {
    $user      = $_SESSION['username'];
    $key       = substr(md5($_POST["pwdfile"]), 0, 16);
    $deskripsi = $_POST['desc'] ?? '';

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Gagal mengunggah file. Silakan coba lagi.');
        </script>");
        exit();
    }

    $file_tmpname = $_FILES['file']['tmp_name'];
    $raw_name     = basename($_FILES['file']['name']);
    $info         = pathinfo($raw_name);
    $ext          = strtolower($info["extension"] ?? '');

    $allowed_ext = array("txt", "docx", "pptx", "pdf", "doc", "xls", "xlsx", "ppt");
    if (!in_array($ext, $allowed_ext, true)) {
        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Maaf, file yang bisa dienkrip hanya word, excel, text, ppt ataupun pdf.');
        </script>");
        exit();
    }

    $size = filesize($file_tmpname);
    if ($size > 3145728) { // 3 MB = 3 * 1024 * 1024 bytes
        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Maaf, file tidak bisa lebih besar dari 3MB.');
        </script>");
        exit();
    }

    $unique_prefix = rand(10000, 99999) . "-" . time();
    $clean_raw     = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '-', $raw_name));
    $final_file    = $unique_prefix . "-" . $clean_raw;

    $base_name     = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '-', $info["filename"] ?? 'file'));
    $finalfile     = $unique_prefix . "-" . $base_name;
    $url           = $finalfile . ".rda";
    $file_url      = "hasil_ekripsi/" . $url;

    // Simpan ukuran byte asli ke database agar saat didekripsi dapat dipulihkan 100% bit-for-bit
    $stmt = mysqli_prepare($connect, "INSERT INTO file (username, file_name_source, file_name_finish, file_url, file_size, password, tgl_upload, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, NOW(), '1', ?)");
    if ($stmt) {
        $size_str = (string)$size;
        mysqli_stmt_bind_param($stmt, "sssssss", $user, $final_file, $url, $file_url, $size_str, $key, $deskripsi);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Gagal menyimpan metadata file ke database.');
        </script>");
        exit();
    }

    $file_source = fopen($file_tmpname, 'rb');
    $file_output = fopen($file_url, 'wb');

    if ($file_source && $file_output) {
        set_time_limit(180);
        $aes = new AES($key);

        $mod = $size % 16;
        $banyak = ($mod == 0) ? ($size / 16) : (floor($size / 16) + 1);

        for ($bawah = 0; $bawah < $banyak; $bawah++) {
            $data   = fread($file_source, 16);
            $cipher = $aes->encrypt($data);
            fwrite($file_output, $cipher);
        }
        fclose($file_source);
        fclose($file_output);

        log_activity($connect, $user, 'ENCRYPT_FILE', "Enkripsi berkas: " . $raw_name . " -> " . $url);

        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Enkripsi Berhasil..');
        </script>");
        exit();
    } else {
        if ($file_source) fclose($file_source);
        if ($file_output) fclose($file_output);
        echo("<script language='javascript'>
        window.location.href='enkripsi.php';
        window.alert('Encrypt file mengalami masalah saat menulis file.');
        </script>");
        exit();
    }
} else {
    header("location: enkripsi.php");
    exit();
}
?>