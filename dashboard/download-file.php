<?php
session_start();
require_once "../config.php";
require_once "audit_helper.php";

if (empty($_SESSION['username'])) {
    header("location: ../index.php");
    exit();
}

$id_file = isset($_GET['id_file']) ? (int)$_GET['id_file'] : 0;
$type    = isset($_GET['type']) ? $_GET['type'] : 'encrypted';

if ($id_file <= 0) {
    header("location: dekripsi.php");
    exit();
}

$stmt = mysqli_prepare($connect, "SELECT * FROM file WHERE id_file = ?");
$data = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_file);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$data) {
    echo "<script>alert('Data file tidak ditemukan!'); window.location='dekripsi.php';</script>";
    exit();
}

$user = $_SESSION['username'];
if ($user !== 'admin' && ($data['username'] ?? '') !== $user) {
    echo "<script>alert('Anda tidak memiliki izin untuk mengunduh berkas milik pengguna lain!'); window.location='dekripsi.php';</script>";
    exit();
}

if ($type === 'encrypted') {
    // Unduh berkas cipher (.rda)
    $file_path = __DIR__ . "/" . $data['file_url'];
    $download_name = basename($data['file_name_finish']);
    $base_dir = realpath(__DIR__ . '/hasil_ekripsi');
} elseif ($type === 'decrypted') {
    // Unduh berkas hasil dekripsi
    if ($data['status'] != '2') {
        echo "<script>alert('Berkas belum didekripsi!'); window.location='dekripsi.php';</script>";
        exit();
    }
    $file_path = __DIR__ . "/hasil_dekripsi/" . basename($data['file_name_source']);
    $download_name = basename($data['file_name_source']);
    $base_dir = realpath(__DIR__ . '/hasil_dekripsi');
} else {
    header("location: dekripsi.php");
    exit();
}

$real_file = realpath($file_path);
if (!$real_file || !$base_dir || strpos($real_file, $base_dir) !== 0 || !file_exists($real_file)) {
    echo "<script>alert('Berkas fisik tidak ditemukan di server.'); window.location='dekripsi.php';</script>";
    exit();
}

if (ob_get_level()) {
    ob_end_clean();
}

header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=\"" . $download_name . "\"");
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize($real_file));
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");
header("Connection: close");

log_activity($connect, $user, $type === 'encrypted' ? 'DOWNLOAD_CIPHER' : 'DOWNLOAD_PLAIN', "Unduh berkas $download_name (ID $id_file)");

readfile($real_file);
exit();
?>