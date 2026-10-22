<?php
session_start();
include "../config.php";   // Memasukkan koneksi
include "AES.php";          // Memasukkan file AES

if (empty($_SESSION['username'])) {
    header("location: ../index.php");
    exit();
}

$idfile  = isset($_POST['fileid']) ? (int)$_POST['fileid'] : 0;
$raw_pwd = isset($_POST['pwdfile']) ? $_POST['pwdfile'] : '';
$pwdfile = substr(md5($raw_pwd), 0, 16);

$stmt = mysqli_prepare($connect, "SELECT * FROM file WHERE id_file = ? AND password = ?");
$data = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $idfile, $pwdfile);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if ($data) {
    $file_path = $data["file_url"];
    $key       = $data["password"];
    $file_name = basename($data["file_name_source"]);
    $size      = $data["file_size"];

    if (!file_exists($file_path)) {
        echo("<script language='javascript'>
        window.location.href='dekripsi.php';
        window.alert('File terenkripsi fisik tidak ditemukan di server.');
        </script>");
        exit();
    }

    $file_size = filesize($file_path);

    // Update status file menjadi 2 (sudah didekripsi)
    $stmt_up = mysqli_prepare($connect, "UPDATE file SET status='2' WHERE id_file = ?");
    if ($stmt_up) {
        mysqli_stmt_bind_param($stmt_up, "i", $idfile);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);
    }

    $mod = $file_size % 16;
    $banyak = ($mod == 0) ? ($file_size / 16) : (floor($file_size / 16) + 1);

    $cache  = "hasil_dekripsi/" . $file_name;
    $fopen1 = fopen($file_path, "rb");
    $fopen2 = fopen($cache, "wb");

    if ($fopen1 && $fopen2) {
        set_time_limit(180);
        $aes = new AES($key);

        for ($bawah = 0; $bawah < $banyak; $bawah++) {
            $filedata = fread($fopen1, 16);
            $plain    = $aes->decrypt($filedata);
            fwrite($fopen2, $plain);
        }
        fclose($fopen1);

        // Jika ukuran byte asli tersimpan (integer exact byte length), truncate file ke ukuran byte asli
        if (is_numeric($size) && strpos((string)$size, '.') === false) {
            $target_bytes = (int)$size;
            if ($target_bytes > 0 && $target_bytes <= $file_size) {
                ftruncate($fopen2, $target_bytes);
            }
        }
        fclose($fopen2);

        $_SESSION["download"] = $cache;

        echo("<script language='javascript'>
        window.open('download.php', '_blank');
        window.location.href='dekripsi.php';
        window.alert('Berhasil mendekripsi file.');
        </script>");
        exit();
    } else {
        if ($fopen1) fclose($fopen1);
        if ($fopen2) fclose($fopen2);
        echo("<script language='javascript'>
        window.location.href='dekripsi.php';
        window.alert('Gagal membuka file saat proses dekripsi.');
        </script>");
        exit();
    }
} else {
    echo("<script language='javascript'>
    window.location.href='decrypt-file.php?id_file=" . (int)$idfile . "';
    window.alert('Maaf, Password tidak sesuai.');
    </script>");
    exit();
}
?>
