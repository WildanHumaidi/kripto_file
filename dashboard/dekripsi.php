<?php
session_start();
include('../config.php');
if (empty($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}
$user = $_SESSION['username'];
$stmt = mysqli_prepare($connect, "UPDATE users SET last_activity=NOW() WHERE username=?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<?php
$stmt = mysqli_prepare($connect, "SELECT fullname, job_title, last_activity FROM users WHERE username=?");
$data = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_array($result, MYSQLI_ASSOC) ?: [];
    mysqli_stmt_close($stmt);
}
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>File Enkripsi & Dekripsi AES</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- favicon
		============================================ -->
        <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <!-- Google Fonts
		============================================ -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
	<!-- nalika Icon CSS
		============================================ -->
    <link rel="stylesheet" href="css/nalika-icon.css">
    <!-- owl.carousel CSS
		============================================ -->
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.css">
    <link rel="stylesheet" href="css/owl.transitions.css">
    <!-- animate CSS
		============================================ -->
    <link rel="stylesheet" href="css/animate.css">
    <!-- normalize CSS
		============================================ -->
    <link rel="stylesheet" href="css/normalize.css">
    <!-- meanmenu icon CSS
		============================================ -->
    <link rel="stylesheet" href="css/meanmenu.min.css">
    <!-- main CSS
		============================================ -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/main.css">
    <!-- morrisjs CSS
		============================================ -->
    <link rel="stylesheet" href="css/morrisjs/morris.css">
    <!-- mCustomScrollbar CSS
		============================================ -->
    <link rel="stylesheet" href="css/scrollbar/jquery.mCustomScrollbar.min.css">
    <!-- metisMenu CSS
		============================================ -->
    <link rel="stylesheet" href="css/metisMenu/metisMenu.min.css">
    <link rel="stylesheet" href="css/metisMenu/metisMenu-vertical.css">
    <!-- calendar CSS
		============================================ -->
    <link rel="stylesheet" href="css/calendar/fullcalendar.min.css">
    <link rel="stylesheet" href="css/calendar/fullcalendar.print.min.css">
    <!-- style CSS
		============================================ -->
    <link rel="stylesheet" href="style.css">
    <!-- responsive CSS
		============================================ -->
    <link rel="stylesheet" href="css/responsive.css">
    <!-- modernizr JS
		============================================ -->
    <script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

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
                    <div class="profile-social-dtl">
                        <ul class="dtl-social">
                            <li><a href="#"><i class="icon nalika-facebook"></i></a></li>
                            <li><a href="#"><i class="icon nalika-twitter"></i></a></li>
                            <li><a href="#"><i class="icon nalika-linkedin"></i></a></li>
                        </ul>
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
                            <li class="active">
                                <a class="nav-link" href="dekripsi.php" aria-expanded="false"><i class="icon nalika-unlocked icon-wrap"></i> <span class="mini-click-non">Dekripsi</span></a>
                            </li>
                            <li>
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
    <!-- Start Welcome area -->
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
                                    <div class="col-lg-6 col-md-7 col-sm-6 col-xs-12">
                                        <div class="header-top-menu tabl-d-n hd-search-rp">
                                            <div class="breadcome-heading">
												<form role="search" class="">
													<input type="text" placeholder="Search..." class="form-control">
													<a href=""><i class="fa fa-search"></i></a>
												</form>
											</div>
                                        </div>
                                    </div>
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
            <section class="breadcome-list">
            <div class="row">
                <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                    <div class="table-responsive" style="color:#fff;">
                    <table id="file" class="table striped">
                        <thead>
                            <tr>
                            <td width="5%"><strong>No</strong></td>
                            <td width="20%"><strong>File Sumber</strong></td>
                            <td width="15%"><strong>File Enkripsi (.rda)</strong></td>
                            <td width="10%"><strong>Ukuran</strong></td>
                            <td width="15%"><strong>Status</strong></td>
                            <td width="35%"><strong>Aksi</strong></td>
                            </tr>
                        </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            if ($user === 'admin') {
                                $query = mysqli_query($connect,"SELECT * FROM file ORDER BY id_file DESC");
                            } else {
                                $stmt_fl = mysqli_prepare($connect, "SELECT * FROM file WHERE username = ? ORDER BY id_file DESC");
                                mysqli_stmt_bind_param($stmt_fl, "s", $user);
                                mysqli_stmt_execute($stmt_fl);
                                $query = mysqli_stmt_get_result($stmt_fl);
                            }
                            while ($data = mysqli_fetch_array($query)) { ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo htmlspecialchars($data['file_name_source'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($data['file_name_finish'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php 
                                    $sz = $data['file_size'];
                                    if (is_numeric($sz)) {
                                        echo ($sz > 500 ? round($sz / 1024, 2) : round((float)$sz, 2)) . ' KB';
                                    } else {
                                        echo htmlspecialchars((string)$sz, ENT_QUOTES, 'UTF-8') . ' KB';
                                    }
                                ?></td>
                                <td><?php if ($data['status'] == 1) {
                                    echo "<span class='label label-danger'>Terenkripsi</span>";
                                }elseif ($data['status'] == 2) {
                                    echo "<span class='label label-success'>Sudah Didekripsi</span>";
                                }else {
                                    echo "<span class='label label-default'>Status Tidak Diketahui</span>";
                                }
                                ?></td>
                                <td>
                                <?php
                                $a = (int)$data['id_file'];
                                if ($data['status'] == 1) {
                                    echo '<a href="decrypt-file.php?id_file='.$a.'" class="btn btn-primary btn-xs" style="margin-right:4px;"><i class="fa fa-unlock"></i> Dekripsi</a>';
                                    echo '<a href="download-file.php?id_file='.$a.'&type=encrypted" class="btn btn-warning btn-xs" style="margin-right:4px;"><i class="fa fa-download"></i> Unduh .RDA</a>';
                                } elseif ($data['status'] == 2) {
                                    echo '<a href="download-file.php?id_file='.$a.'&type=decrypted" class="btn btn-success btn-xs" style="margin-right:4px;"><i class="fa fa-download"></i> Unduh File Asli</a>';
                                    echo '<a href="download-file.php?id_file='.$a.'&type=encrypted" class="btn btn-warning btn-xs" style="margin-right:4px;"><i class="fa fa-download"></i> Unduh .RDA</a>';
                                }
                                echo '<a href="delete-file.php?id_file='.$a.'" onclick="return confirm(\'Apakah Anda yakin ingin menghapus berkas ini dari server?\');" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Hapus</a>';
                                ?>

                                </td>
                            </tr>
                            <?php
                            $i++;
                            } ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </section>
        <!-- <div class="footer-copyright-area">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="footer-copy-right">
                            <h2 style="color:#fff;">Enkripsi File</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    <!-- jquery
		============================================ -->
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <!-- bootstrap JS
		============================================ -->
    <script src="js/bootstrap.min.js"></script>
    <!-- wow JS
		============================================ -->
    <script src="js/wow.min.js"></script>
    <!-- price-slider JS
		============================================ -->
    <script src="js/jquery-price-slider.js"></script>
    <!-- meanmenu JS
		============================================ -->
    <script src="js/jquery.meanmenu.js"></script>
    <!-- owl.carousel JS
		============================================ -->
    <script src="js/owl.carousel.min.js"></script>
    <!-- sticky JS
		============================================ -->
    <script src="js/jquery.sticky.js"></script>
    <!-- scrollUp JS
		============================================ -->
    <script src="js/jquery.scrollUp.min.js"></script>
    <!-- mCustomScrollbar JS
		============================================ -->
    <script src="js/scrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="js/scrollbar/mCustomScrollbar-active.js"></script>
    <!-- metisMenu JS
		============================================ -->
    <script src="js/metisMenu/metisMenu.min.js"></script>
    <script src="js/metisMenu/metisMenu-active.js"></script>
    <!-- sparkline JS
		============================================ -->
    <script src="js/sparkline/jquery.sparkline.min.js"></script>
    <script src="js/sparkline/jquery.charts-sparkline.js"></script>
    <!-- calendar JS
		============================================ -->
    <script src="js/calendar/moment.min.js"></script>
    <script src="js/calendar/fullcalendar.min.js"></script>
    <script src="js/calendar/fullcalendar-active.js"></script>
	<!-- float JS
		============================================ -->
    <script src="js/flot/jquery.flot.js"></script>
    <script src="js/flot/jquery.flot.resize.js"></script>
    <script src="js/flot/curvedLines.js"></script>
    <script src="js/flot/flot-active.js"></script>
    <!-- plugins JS
		============================================ -->
    <script src="js/plugins.js"></script>
    <!-- main JS
		============================================ -->
    <script src="js/main.js"></script>
    <script src="../assets/plugins/datatables/js/jquery.dataTables.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if ($.fn.dataTable) {
                $('#file').dataTable({
                    "bPaginate": true,
                    "bLengthChange": true,
                    "bFilter": true,
                    "bInfo": true,
                    "bAutoWidth": false,
                    "order": [[0, "asc"]],
                    "language": {
                        "search": "Cari Berkas:",
                        "emptyTable": "Belum ada berkas yang tersimpan",
                        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ berkas",
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