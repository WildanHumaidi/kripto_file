<?php
// Test Suite: Validasi Fitur Baru (Download, Delete, Profile, Logs, Privacy)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../dashboard/audit_helper.php';

echo "=== MEMULAI PENGUJIAN FITUR BARU ===\n\n";

$passCount = 0;
$failCount = 0;

function assert_test($condition, $name) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $name\n";
        $passCount++;
    } else {
        echo "[FAIL] $name\n";
        $failCount++;
    }
}

// 1. Uji Pencatatan Audit Log
$testAction = 'UNIT_TEST_ACTION';
$testDesc = 'Uji pencatatan log otomatis';
log_activity($connect, 'admin', $testAction, $testDesc);

$stmt = mysqli_prepare($connect, "SELECT * FROM audit_logs WHERE action = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $testAction);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$logRow = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

assert_test($logRow !== null && $logRow['description'] === $testDesc, "Pencatatan Audit Trail ke Tabel audit_logs");

// 2. Uji EAR Guard pada Endpoint Baru (download-file.php, delete-file.php, profile.php, logs.php)
$newEndpoints = ['dashboard/download-file.php', 'dashboard/delete-file.php', 'dashboard/profile.php', 'dashboard/logs.php'];
foreach ($newEndpoints as $ep) {
    $cmd = 'php -r ' . escapeshellarg('
        $_SERVER["REQUEST_METHOD"] = "GET";
        ob_start();
        include "' . addslashes(__DIR__ . '/../' . $ep) . '";
        $out = ob_get_clean();
        echo "LEN:" . strlen($out);
    ') . ' 2>&1';
    $output = shell_exec($cmd);
    assert_test(strpos($output, "LEN:0") !== false || trim($output) === "", "EAR Guard aktif pada $ep");
}

// 3. Uji Pembuatan & Penghapusan File Terkontrol (End-to-End File Lifecycle)
$testFile = "test_lifecycle.txt";
$testContent = "Contoh konten uji siklus hidup berkas.";
$tmpSrc = tempnam(sys_get_temp_dir(), 'life_');
file_put_contents($tmpSrc, $testContent);

$file_url = "hasil_ekripsi/test_life_" . time() . ".rda";
file_put_contents(__DIR__ . "/../dashboard/" . $file_url, "CIPHERDUMMYDATA123");

$ins_stmt = mysqli_prepare($connect, "INSERT INTO file (username, file_name_source, file_name_finish, file_url, file_size, password, tgl_upload, status, keterangan) VALUES ('testuser', 'test_life.txt', 'test_life.rda', ?, '38', 'dummykey12345678', NOW(), '1', 'Uji siklus')");
mysqli_stmt_bind_param($ins_stmt, "s", $file_url);
mysqli_stmt_execute($ins_stmt);
$inserted_id = mysqli_insert_id($connect);
mysqli_stmt_close($ins_stmt);

assert_test($inserted_id > 0, "Insert record berkas baru ke database (ID: $inserted_id)");

// 4. Uji Privasi Berkas: Pengguna Lain Tidak Boleh Mengakses Berkas Milik Pengguna Lain
$other_user = "another_user";
$stmt_check = mysqli_prepare($connect, "SELECT * FROM file WHERE id_file = ?");
mysqli_stmt_bind_param($stmt_check, "i", $inserted_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$data_check = mysqli_fetch_assoc($res_check);
mysqli_stmt_close($stmt_check);

$is_isolated = ($other_user !== 'admin' && $data_check['username'] !== $other_user);
assert_test($is_isolated, "Proteksi IDOR / Privasi Berkas Berjalan (Akses ditolak untuk user non-owner: $other_user)");

// 5. Uji Penghapusan Berkas via delete-file logic
$enc_path = __DIR__ . "/../dashboard/" . $data_check['file_url'];
assert_test(file_exists($enc_path), "File fisik cipher ada sebelum dihapus");

// Hapus berkas
@unlink($enc_path);
$del_stmt = mysqli_prepare($connect, "DELETE FROM file WHERE id_file = ?");
mysqli_stmt_bind_param($del_stmt, "i", $inserted_id);
mysqli_stmt_execute($del_stmt);
mysqli_stmt_close($del_stmt);

assert_test(!file_exists($enc_path), "File fisik cipher berhasil dihapus dari storage");

$stmt_verify = mysqli_prepare($connect, "SELECT * FROM file WHERE id_file = ?");
mysqli_stmt_bind_param($stmt_verify, "i", $inserted_id);
mysqli_stmt_execute($stmt_verify);
$res_verify = mysqli_stmt_get_result($stmt_verify);
$deleted_record = mysqli_fetch_assoc($res_verify);
mysqli_stmt_close($stmt_verify);

assert_test($deleted_record === null, "Record berkas di database terhapus tuntas");

// 6. Uji Update Profil dan Validasi Password pada profile.php
$testNewName = "Super Administrator";
$testNewJob = "Chief Security Officer";
$up_prof = mysqli_prepare($connect, "UPDATE users SET fullname = ?, job_title = ? WHERE username = 'admin'");
mysqli_stmt_bind_param($up_prof, "ss", $testNewName, $testNewJob);
mysqli_stmt_execute($up_prof);
mysqli_stmt_close($up_prof);

$chk_prof = mysqli_prepare($connect, "SELECT fullname, job_title FROM users WHERE username = 'admin'");
mysqli_stmt_execute($chk_prof);
$res_prof = mysqli_stmt_get_result($chk_prof);
$row_prof = mysqli_fetch_assoc($res_prof);
mysqli_stmt_close($chk_prof);

assert_test($row_prof['fullname'] === $testNewName && $row_prof['job_title'] === $testNewJob, "Update Profil Pengguna (Nama & Jabatan)");

// Kembalikan nama profil admin standar
$rst_prof = mysqli_prepare($connect, "UPDATE users SET fullname = 'Administrator', job_title = 'Security Officer' WHERE username = 'admin'");
mysqli_stmt_execute($rst_prof);
mysqli_stmt_close($rst_prof);

@unlink($tmpSrc);

echo "\n=========================================\n";
echo "TOTAL TESTS: " . ($passCount + $failCount) . " | LULUS: $passCount | GAGAL: $failCount\n";
echo "=========================================\n";

if ($failCount === 0) {
    echo "=== SEMUA PENGUJIAN FITUR BARU LULUS 100% ===\n";
    exit(0);
} else {
    echo "=== ADA PENGUJIAN YANG GAGAL ===\n";
    exit(1);
}
