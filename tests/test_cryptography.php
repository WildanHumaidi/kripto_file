<?php
// Regression Test Suite: Cryptography Integrity, Padding Truncation, & Round-Trip
require_once __DIR__ . '/../dashboard/AES.php';

function run_test($name, $filePath, $password) {
    echo "--- Testing: $name ($filePath) ---\n";
    if (!file_exists($filePath)) {
        echo "[FAIL] File not found: $filePath\n";
        return false;
    }

    $origContent = file_get_contents($filePath);
    $origSize = strlen($origContent);
    $origHash = hash('sha256', $origContent);
    echo "Original Size: $origSize bytes\n";
    echo "Original SHA256: $origHash\n";

    $key = substr(md5($password), 0, 16);
    $aes = new AES($key);

    // 1. Enkripsi (simulasi encrypt-process.php)
    $tmpEncrypt = tempnam(sys_get_temp_dir(), 'enc_');
    $sourceFp = fopen($filePath, 'rb');
    $outFp = fopen($tmpEncrypt, 'wb');

    $mod = $origSize % 16;
    $banyak = ($mod == 0) ? ($origSize / 16) : (floor($origSize / 16) + 1);

    for ($bawah = 0; $bawah < $banyak; $bawah++) {
        $block = fread($sourceFp, 16);
        $cipher = $aes->encrypt($block);
        fwrite($outFp, $cipher);
    }
    fclose($sourceFp);
    fclose($outFp);
    clearstatcache(true, $tmpEncrypt);

    $cipherSize = filesize($tmpEncrypt);
    echo "Cipher Size: $cipherSize bytes (expected multiple of 16: " . ($cipherSize % 16 === 0 ? "YES" : "NO") . ")\n";

    // 2. Dekripsi (simulasi decrypt-process.php dengan exact padding truncation)
    $tmpDecrypt = tempnam(sys_get_temp_dir(), 'dec_');
    $inFp = fopen($tmpEncrypt, 'rb');
    $decFp = fopen($tmpDecrypt, 'wb');

    $cipherBlocks = $cipherSize / 16;
    for ($bawah = 0; $bawah < $cipherBlocks; $bawah++) {
        $cipherBlock = fread($inFp, 16);
        $plainBlock = $aes->decrypt($cipherBlock);
        fwrite($decFp, $plainBlock);
    }
    fclose($inFp);

    // Truncate to exact recorded original byte size
    ftruncate($decFp, $origSize);
    fclose($decFp);

    $decContent = file_get_contents($tmpDecrypt);
    $decSize = strlen($decContent);
    $decHash = hash('sha256', $decContent);

    echo "Decrypted Size: $decSize bytes\n";
    echo "Decrypted SHA256: $decHash\n";

    // Bersihkan file sementara
    @unlink($tmpEncrypt);
    @unlink($tmpDecrypt);

    if ($origHash === $decHash && $origSize === $decSize) {
        echo "[PASS] 100% Bit-for-bit match!\n\n";
        return true;
    } else {
        echo "[FAIL] Hash or size mismatch!\n\n";
        return false;
    }
}

$allPass = true;
$allPass = run_test("Plain Text File", __DIR__ . '/../test_txt.txt', "secret123") && $allPass;
$allPass = run_test("Word DOCX Archive", __DIR__ . '/../test_doc.docx', "MyDocKey") && $allPass;
$allPass = run_test("PowerPoint PPTX Archive", __DIR__ . '/../test_ppt.pptx', "PresentationPwd") && $allPass;

if ($allPass) {
    echo "=== ALL CRYPTOGRAPHY TESTS PASSED ===\n";
    exit(0);
} else {
    echo "=== SOME CRYPTOGRAPHY TESTS FAILED ===\n";
    exit(1);
}
