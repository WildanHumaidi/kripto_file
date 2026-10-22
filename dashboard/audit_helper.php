<?php
// Helper Fungsi Pencatatan Audit Trail
function log_activity($connect, $username, $action, $description = '') {
    if (!$connect || empty($username) || empty($action)) {
        return;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = mysqli_prepare($connect, "INSERT INTO audit_logs (username, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $username, $action, $description, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>