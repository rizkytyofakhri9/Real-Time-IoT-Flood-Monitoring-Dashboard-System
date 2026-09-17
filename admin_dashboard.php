<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$query_last = "SELECT level_air, status, waktu FROM tabel_banjir ORDER BY id DESC LIMIT 1";
$res_last = mysqli_query($koneksi, $query_last);
$data_last = mysqli_fetch_assoc($res_last);

$level_sekarang = $data_last ? $data_last['level_air'] : 0;
$status_sekarang = $data_last ? $data_last['status'] : "Aman";

$status_sistem = "Offline"; 
$warna_status = "#e74c3c";

if ($data_last) {
    date_default_timezone_set('Asia/Jakarta');
    $waktu_data = strtotime($data_last['waktu']);
    $waktu_sekarang = time();
    $selisih_detik = $waktu_sekarang - $waktu_data;
    
    if ($selisih_detik <= 315) {
        $status_sistem = "Online";
        $warna_status = "#2ecc71";
    }
}

$query_warga = "SELECT COUNT(*) as total_warga FROM users WHERE role = 'warga'";
$res_warga = mysqli_query($koneksi, $query_warga);
$data_warga = mysqli_fetch_assoc($res_warga);
$total_warga = $data_warga['total_warga'];

$query_chart = "SELECT level_air, waktu FROM tabel_banjir ORDER BY id DESC LIMIT 10";
$res_chart = mysqli_query($koneksi, $query_chart);
$arr_level = []; $arr_waktu = [];
while($row = mysqli_fetch_assoc($res_chart)){
    $arr_level[] = $row['level_air'];
    $arr_waktu[] = date('H:i', strtotime($row['waktu']));
}
$arr_level = array_reverse($arr_level);
$arr_waktu = array_reverse($arr_waktu);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5"> 
    <title>Dashboard Admin - Monitoring Banjir</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <h3>MENU ADMIN</h3>
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="riwayat.php">Riwayat</a>
    <a href="data_warga.php">Data Warga</a>
    <a href="kelola_warga.php">Kelola Akun Warga</a>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>DASHBOARD ADMIN</h2>
    <p>Selamat Datang, <b><?php echo $_SESSION['nama']; ?></b> (Hak Akses: Admin)</p>
    
    <div class="cards-grid">
        <div class="card">
            <h4>Ketinggian Air</h4>
            <p style="color: #2196F3;"><?= $level_sekarang; ?> cm</p>
            <small>Status: <b><?= $status_sekarang; ?></b></small>
        </div>
        <div class="card">
            <h4>Total Warga</h4>
            <p><?= $total_warga; ?></p>
            <small>Akun Warga Terdaftar</small>
        </div>
        <div class="card">
            <h4>Status Sistem</h4>
            <p style="color: <?= $warna_status; ?>;"><?= $status_sistem; ?></p>
        </div>
    </div>

    <div class="chart-section">
        <h3>GRAFIK MONITORING REAL-TIME</h3>
        <canvas id="lineChart" style="max-height: 350px;"></canvas>
    </div>
</div>

<script src="script.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        initMonitoringChart('lineChart', <?php echo json_encode($arr_waktu); ?>, <?php echo json_encode($arr_level); ?>);
    });
</script>
</body>
</html>