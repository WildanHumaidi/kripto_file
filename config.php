<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'db_kkp';

$connect = mysqli_connect($host, $user, $pass, $dbname);
if (!$connect) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($connect, 'utf8mb4');
?>
