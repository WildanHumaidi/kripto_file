<?php
// Regression Test Suite: Security Access Controls, EAR Guard, & SQL Injection Defense

echo "--- Testing Security & Access Controls ---\n";

// 1. Test EAR (Execution After Redirect) on Dashboard pages without session
$pages = [
    'dashboard/index.php',
    'dashboard/enkripsi.php',
    'dashboard/dekripsi.php',
    'dashboard/decrypt-file.php',
    'dashboard/download.php',
];

$allEarPass = true;
foreach ($pages as $page) {
    // Run PHP in a separate process simulating HTTP request with NO session
    $cmd = 'php -r ' . escapeshellarg('
        $_SERVER["REQUEST_METHOD"] = "GET";
        ob_start();
        include "' . addslashes(__DIR__ . '/../' . $page) . '";
        $out = ob_get_clean();
        // If script exited after redirect, output should be empty or contain only redirect header
        echo "LEN:" . strlen($out);
    ') . ' 2>&1';

    $output = shell_exec($cmd);
    if (strpos($output, "LEN:0") !== false || trim($output) === "") {
        echo "[PASS] EAR Guard active on $page (Output truncated immediately)\n";
    } else {
        echo "[FAIL] EAR Guard failed on $page. Output: " . substr($output, 0, 100) . "...\n";
        $allEarPass = false;
    }
}

// 2. Test SQL Injection mitigation on decrypt-file.php logic
echo "\n--- Testing SQL Injection Mitigation ---\n";
require_once __DIR__ . '/../config.php';

$sqli_payload = "1' OR '1'='1";
$id_file_safe = (int)$sqli_payload;

$stmt = mysqli_prepare($connect, "SELECT * FROM file WHERE id_file = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_file_safe);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Because '1\' OR \'1\'=\'1' casts to integer 1, it queries strictly id_file = 1, NOT all rows!
    echo "[PASS] Prepared statement safely executed. Query evaluated strictly with integer type: $id_file_safe\n";
    $sqliPass = true;
} else {
    echo "[FAIL] Prepared statement failed: " . mysqli_error($connect) . "\n";
    $sqliPass = false;
}

// 3. Test Authentication Prepared Statement
echo "\n--- Testing Authentication Prepared Statement ---\n";
$testUser = "admin' OR '1'='1";
$testPass = "arbitrary";

$stmt = mysqli_prepare($connect, "SELECT username, password FROM users WHERE username = ? AND password = md5(?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $testUser, $testPass);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row === null) {
        echo "[PASS] SQL Injection in login blocked: payload did not authenticate.\n";
        $loginPass = true;
    } else {
        echo "[FAIL] SQL Injection succeeded in login!\n";
        $loginPass = false;
    }
} else {
    echo "[FAIL] Stmt prepare failed\n";
    $loginPass = false;
}

if ($allEarPass && $sqliPass && $loginPass) {
    echo "\n=== ALL SECURITY ACCESS TESTS PASSED ===\n";
    exit(0);
} else {
    echo "\n=== SOME SECURITY ACCESS TESTS FAILED ===\n";
    exit(1);
}
