<?php
session_start();
require_once "../config.php";
require_once "audit_helper.php";

if (empty($_SESSION['username'])) {
    header("location: ../index.php");
    exit();
}

$id_file = isset($_GET['id_file']) ? (int)$_GET['id_file'] : 0;
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
    echo "<script>alert('File tidak ditemukan!'); window.location='dekripsi.php';</script>";
    exit();
}

$user = $_SESSION['username'];
if ($user !== 'admin' && ($data['username'] ?? '') !== $user) {
    echo "<script>alert('Anda tidak memiliki izin untuk menghapus berkas milik pengguna lain!'); window.location='dekripsi.php';</script>";
    exit();
}

// Hapus file fisik hasil enkripsi jika ada
$enc_file = __DIR__ . "/" . $data['file_url'];
if (file_exists($enc_file) && is_file($enc_file)) {
    @unlink($enc_file);
}

// Hapus file fisik hasil dekripsi jika ada
$dec_file = __DIR__ . "/hasil_dekripsi/" . basename($data['file_name_source']);
if (file_exists($dec_file) && is_file($dec_file)) {
    @unlink($dec_file);
}

// Hapus record dari database
$del_stmt = mysqli_prepare($connect, "DELETE FROM file WHERE id_file = ?");
if ($del_stmt) {
    mysqli_stmt_bind_param($del_stmt, "i", $id_file);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);
    log_activity($connect, $user, 'DELETE_FILE', "Hapus berkas ID $id_file: " . ($data['file_name_source'] ?? ''));
}

echo "<script>alert('Berkas dan data berhasil dihapus.'); window.location='dekripsi.php';</script>";
exit();
?>