<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'warga') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$nama_hari = array(
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
);

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';

$query_sql = "SELECT *, (16 - level_air) AS jarak_sensor FROM tabel_banjir";

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $query_sql .= " WHERE waktu BETWEEN '$tanggal_mulai 00:00:00' AND '$tanggal_selesai 23:59:59'";
}

$query_sql .= " ORDER BY id DESC";
$result = mysqli_query($koneksi, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Ketinggian Air - Warga</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
    <h3>MENU WARGA</h3>
    <a href="warga_dashboard.php">Dashboard</a>
    <a href="riwayat_warga.php" class="active">Riwayat Informasi</a>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>RIWAYAT KETINGGIAN AIR</h2>
    
    <div class="filter-section">
        <h3>FILTER DATA BERDASARKAN TANGGAL</h3>
        <form method="GET" action="" class="form-inline">
            <label>Dari Tanggal:</label>
            <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai; ?>" required>
            
            <label>Sampai Tanggal:</label>
            <input type="date" name="tanggal_selesai" value="<?= $tanggal_selesai; ?>" required>
            
            <button type="submit" class="btn-filter">FILTER</button>
            <?php if(!empty($tanggal_mulai)): ?>
                <a href="riwayat_warga.php" class="btn-reset">RESET</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>HARI</th>
                    <th>TANGGAL</th>
                    <th>WAKTU</th>
                    <th>JARAK SENSOR (cm)</th>
                    <th>KETINGGIAN AIR (cm)</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) { 
                        $timestamp = strtotime($row['waktu']);
                        $hari_eng = date('l', $timestamp);
                        $hari_indo = $nama_hari[$hari_eng];
                        
                        $tanggal = date('d-m-Y', $timestamp);
                        $waktu = date('H:i:s', $timestamp) . ' WIB';
                        
                        $badge_class = 'bg-aman';
                        if($row['status'] == 'Siaga 1') { $badge_class = 'bg-siaga1'; }
                        else if($row['status'] == 'Siaga 2') { $badge_class = 'bg-siaga2'; }
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><b><?= $hari_indo; ?></b></td>
                    <td><?= $tanggal; ?></td>
                    <td><?= $waktu; ?></td>
                    <td><?= $row['jarak_sensor']; ?> cm</td>
                    <td style="color: #2196F3; font-weight: bold;"><?= $row['level_air']; ?> cm</td>
                    <td><span class="badge <?= $badge_class; ?>"><?= $row['status']; ?></span></td>
                </tr>
                <?php 
                    } 
                } else {
                    echo "<tr><td colspan='7'>Tidak ada data riwayat banjir pada tanggal tersebut.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>