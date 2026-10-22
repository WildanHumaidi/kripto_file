<?php
session_start();
include 'config.php';

$error = '';
if (isset($_POST['login'])) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = "Username or Password Tidak Valid!";
    } else {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        $stmt = mysqli_prepare($connect, "SELECT username, password FROM users WHERE username = ? AND password = md5(?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $user, $pass);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rows = mysqli_fetch_array($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);

            if ($rows) {
                session_regenerate_id(true);
                $_SESSION['username'] = $rows['username'];
                header("location: dashboard/index.php");
                exit();
            } else {
                echo "<script language=\"JavaScript\">\n";
                echo "alert('Username atau Password Salah!');\n";
                echo "window.location='index.php';\n";
                echo "</script>";
                exit();
            }
        } else {
            echo "<script language=\"JavaScript\">\n";
            echo "alert('Terjadi kesalahan pada sistem!');\n";
            echo "window.location='index.php';\n";
            echo "</script>";
            exit();
        }
    }
}
?>
