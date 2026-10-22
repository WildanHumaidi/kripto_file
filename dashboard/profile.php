<?php
session_start();
require_once '../config.php';
require_once 'audit_helper.php';

if (empty($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}

$user = $_SESSION['username'];
$msg = '';
$msg_type = '';

// Proses Simpan Profil / Ubah Password
if (isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $old_pwd = $_POST['old_password'] ?? '';
    $new_pwd = $_POST['new_password'] ?? '';
    $cfm_pwd = $_POST['confirm_password'] ?? '';

    // Ambil data user saat ini
    $stmt = mysqli_prepare($connect, "SELECT password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $current_user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!empty($new_pwd)) {
        if (empty($old_pwd)) {
            $msg = 'Password lama wajib diisi untuk mengubah password!';
            $msg_type = 'danger';
        } elseif (md5($old_pwd) !== $current_user['password']) {
            $msg = 'Password lama tidak sesuai!';
            $msg_type = 'danger';
        } elseif (strlen($new_pwd) < 4) {
            $msg = 'Password baru minimal 4 karakter!';
            $msg_type = 'danger';
        } elseif ($new_pwd !== $cfm_pwd) {
            $msg = 'Konfirmasi password baru tidak cocok!';
            $msg_type = 'danger';
        } else {
            // Update password dan profil
            $new_hash = md5($new_pwd);
            $up_stmt = mysqli_prepare($connect, "UPDATE users SET fullname = ?, job_title = ?, password = ? WHERE username = ?");
            mysqli_stmt_bind_param($up_stmt, "ssss", $fullname, $job_title, $new_hash, $user);
            if (mysqli_stmt_execute($up_stmt)) {
                $msg = 'Profil dan password berhasil diperbarui!';
                $msg_type = 'success';
                log_activity($connect, $user, 'CHANGE_PASSWORD', 'Pengguna berhasil mengubah password dan profil akun');
            } else {
                $msg = 'Gagal memperbarui profil: ' . mysqli_error($connect);
                $msg_type = 'danger';
            }
            mysqli_stmt_close($up_stmt);
        }
    } else {
        // Hanya update nama dan job_title
        $up_stmt = mysqli_prepare($connect, "UPDATE users SET fullname = ?, job_title = ? WHERE username = ?");
        mysqli_stmt_bind_param($up_stmt, "sss", $fullname, $job_title, $user);
        if (mysqli_stmt_execute($up_stmt)) {
            $msg = 'Profil berhasil diperbarui!';
            $msg_type = 'success';
            log_activity($connect, $user, 'UPDATE_PROFILE', 'Pengguna memperbarui nama lengkap atau jabatan');
        } else {
            $msg = 'Gagal memperbarui profil: ' . mysqli_error($connect);
            $msg_type = 'danger';
        }
        mysqli_stmt_close($up_stmt);
    }
}

// Ambil info pengguna terbaru
$stmt = mysqli_prepare($connect, "SELECT fullname, job_title, last_activity FROM users WHERE username = ?");
$data = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result) ?: [];
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Pengaturan Akun & Profil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/nalika-icon.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/meanmenu.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/main.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="">
            <div class="sidebar-header">
                <a href="index.php"><img class="main-logo" src="img/logo/logosn.png" alt="" /></a>
                <strong><img src="img/logo/logos.png" alt=""></strong>
            </div>
            <div class="nalika-profile">
                <div class="profile-dtl">
                    <a href="#"><img src="../assets/img/avatar.svg" alt="Avatar" /></a>
                    <h2><?php echo htmlspecialchars($data['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?><p class="designation icon" style="color:green;"><?php echo htmlspecialchars($data['job_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p></h2>
                </div>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li>
                            <a class="nav-link" href="index.php">
                                <i class="icon nalika-home icon-wrap"></i>
                                <span class="mini-click-non">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="enkripsi.php" aria-expanded="false"><i class="icon nalika-unlocked icon-wrap"></i> <span class="mini-click-non">Enkripsi</span></a>
                        </li>
                        <li>
                            <a class="nav-link" href="dekripsi.php" aria-expanded="false"><i class="icon nalika-unlocked icon-wrap"></i> <span class="mini-click-non">Dekripsi</span></a>
                        </li>
                        <li class="active">
                            <a class="nav-link" href="profile.php" aria-expanded="false"><i class="fa fa-user icon-wrap"></i> <span class="mini-click-non">Profil</span></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>

    <div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="logo-pro">
                        <a href="index.php"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="header-top-wraper">
                                <div class="row">
                                    <div class="col-lg-1 col-md-0 col-sm-1 col-xs-12">
                                        <div class="menu-switcher-pro">
                                            <button type="button" id="sidebarCollapse" class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
                                            <i class="icon nalika-menu-task"></i>
                                          </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-7 col-sm-6 col-xs-12"></div>
                                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                        <div class="header-right-info">
                                            <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                                <li class="nav-item">
                                                    <a href="profile.php" style="color:#fff; margin-right:15px;"><i class="fa fa-user"></i> Profil</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="logout.php" style="color:#fff;"><i class="fa fa-sign-out"></i> Log Out</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="breadcome-list" style="margin-top: 20px;">
        <div class="row">
          <div class="col-md-8 col-md-offset-2">
            <div class="card" style="background:#152036; padding: 25px; border-radius: 6px;">
              <div class="card-body">
                <h3 style="color:#fff; border-bottom: 1px solid #2a3c5a; padding-bottom: 10px;">Pengaturan Akun & Kata Sandi</h3>
                
                <?php if (!empty($msg)): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>

                <form class="form-horizontal" method="post" action="profile.php" style="margin-top: 20px;">
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Nama Lengkap</label>
                        <div class="col-sm-9">
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($data['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Jabatan / Role</label>
                        <div class="col-sm-9">
                            <input type="text" name="job_title" class="form-control" value="<?php echo htmlspecialchars($data['job_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <hr style="border-color: #2a3c5a;">
                    <h4 style="color:#fff; margin-bottom: 15px;">Ubah Kata Sandi (Opsional)</h4>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Password Lama</label>
                        <div class="col-sm-9">
                            <input type="password" name="old_password" class="form-control" placeholder="Masukkan password saat ini jika ingin mengubah">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Password Baru</label>
                        <div class="col-sm-9">
                            <input type="password" name="new_password" class="form-control" placeholder="Minimal 4 karakter">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" style="color:#fff;">Konfirmasi Password</label>
                        <div class="col-sm-9">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 25px;">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" name="update_profile" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
                            <a href="index.php" class="btn btn-default">Kembali</a>
                        </div>
                    </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        </section>
    </div>

    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.meanmenu.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/main.js"></script>
</body>
</html>