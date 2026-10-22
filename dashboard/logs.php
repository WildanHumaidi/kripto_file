<?php
session_start();
require_once '../config.php';

if (empty($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}

$user = $_SESSION['username'];
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
    <title>Log Aktivitas & Audit Trail</title>
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
                            <a class="nav-link" href="logs.php" aria-expanded="false"><i class="fa fa-history icon-wrap"></i> <span class="mini-click-non">Log Aktivitas</span></a>
                        </li>
                        <li>
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
          <div class="col-md-12">
            <div class="card" style="background:#152036; padding: 25px; border-radius: 6px;">
              <div class="card-body">
                <h3 style="color:#fff; border-bottom: 1px solid #2a3c5a; padding-bottom: 10px;">Riwayat Audit Trail & Aktivitas Sistem</h3>
                <div class="table-responsive" style="margin-top: 20px; color:#fff;">
                    <table id="logTable" class="table striped">
                        <thead>
                            <tr>
                                <th width="5%" style="color:#fff;">No</th>
                                <th width="15%" style="color:#fff;">Waktu</th>
                                <th width="12%" style="color:#fff;">Username</th>
                                <th width="18%" style="color:#fff;">Aksi</th>
                                <th width="35%" style="color:#fff;">Deskripsi</th>
                                <th width="15%" style="color:#fff;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($user === 'admin') {
                                $log_q = mysqli_query($connect, "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100");
                            } else {
                                $stmt_l = mysqli_prepare($connect, "SELECT * FROM audit_logs WHERE username = ? ORDER BY id DESC LIMIT 50");
                                mysqli_stmt_bind_param($stmt_l, "s", $user);
                                mysqli_stmt_execute($stmt_l);
                                $log_q = mysqli_stmt_get_result($stmt_l);
                            }
                            if ($log_q && mysqli_num_rows($log_q) > 0) {
                                while ($row = mysqli_fetch_assoc($log_q)) {
                                    $act = $row['action'];
                                    $label = 'label-default';
                                    if (strpos($act, 'SUCCESS') !== false || strpos($act, 'ENCRYPT') !== false) {
                                        $label = 'label-success';
                                    } elseif (strpos($act, 'FAILED') !== false || strpos($act, 'DELETE') !== false) {
                                        $label = 'label-danger';
                                    } elseif (strpos($act, 'DOWNLOAD') !== false) {
                                        $label = 'label-info';
                                    } elseif (strpos($act, 'PASSWORD') !== false || strpos($act, 'PROFILE') !== false) {
                                        $label = 'label-warning';
                                    }
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><span class="label <?php echo $label; ?>"><?php echo htmlspecialchars($act, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars($row['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
                            </tr>
                            <?php
                                    $i++;
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">Belum ada catatan aktivitas.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
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
    <script src="../assets/plugins/datatables/js/jquery.dataTables.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if ($.fn.dataTable) {
                $('#logTable').dataTable({
                    "bPaginate": true,
                    "bLengthChange": true,
                    "bFilter": true,
                    "bInfo": true,
                    "bAutoWidth": false,
                    "order": [[0, "asc"]],
                    "language": {
                        "search": "Cari Log:",
                        "emptyTable": "Belum ada riwayat aktivitas",
                        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ aktivitas",
                        "paginate": {
                            "next": "Berikutnya",
                            "previous": "Sebelumnya"
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>