<?php
session_start();

if (empty($_SESSION['username'])) {
    header("location: ../index.php");
    exit();
}

if (empty($_SESSION["download"])) {
    header("location: dekripsi.php");
    exit();
}

$raw_file = $_SESSION["download"];
$base_dir = realpath(__DIR__ . '/hasil_dekripsi');
$target_path = realpath(__DIR__ . '/' . $raw_file);

// Cegah path traversal: pastikan file berada di dalam folder hasil_dekripsi
if (!$target_path || !$base_dir || strpos($target_path, $base_dir) !== 0 || !file_exists($target_path)) {
    unset($_SESSION["download"]);
    header("location: dekripsi.php");
    exit();
}

if (ob_get_level()) {
    ob_end_clean();
}

header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=\"" . basename($target_path) . "\"");
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize($target_path));
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");
header("Connection: close");

readfile($target_path);
unset($_SESSION["download"]);
exit();
?>
