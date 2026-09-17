<?php
session_start();
if (!isset($_SESSION['role'])) { header("Location: login.php"); exit; }

include 'koneksi.php';
$role_aktif = $_SESSION['role'];

$nama_hari = array('Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu');

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';

$query_tabel = "SELECT 
                    MIN(waktu) AS waktu_mulai,
                    MAX(waktu) AS waktu_akhir, 
                    MAX(level_air) AS level_air, 
                    (16 - MAX(level_air)) AS jarak_sensor,
                    MAX(status) AS status
                FROM tabel_banjir";

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $query_tabel .= " WHERE waktu BETWEEN '$tanggal_mulai 00:00:00' AND '$tanggal_selesai 23:59:59'";
}

$query_tabel .= " GROUP BY DATE(waktu), FLOOR(HOUR(waktu) / 2) ORDER BY MIN(waktu) DESC";
$result = mysqli_query($koneksi, $query_tabel);

$query_chart = "SELECT 
                    MIN(waktu) AS waktu, 
                    MAX(level_air) AS level_air 
                FROM tabel_banjir";

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $query_chart .= " WHERE waktu BETWEEN '$tanggal_mulai 00:00:00' AND '$tanggal_selesai 23:59:59'";
}

$query_chart .= " GROUP BY DATE(waktu), FLOOR(HOUR(waktu) / 2) ORDER BY MIN(waktu) ASC";
$res_chart = mysqli_query($koneksi, $query_chart);

$data_labels = []; 
$data_levels = [];
while($row = mysqli_fetch_assoc($res_chart)) {
    $data_labels[] = date('d/m H:i', strtotime($row['waktu']));
    $data_levels[] = $row['level_air'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Ketinggian Air</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <h3><?= ($role_aktif == 'admin') ? "MENU ADMIN" : "MENU WARGA"; ?></h3>
    <a href="<?= ($role_aktif == 'admin') ? "admin_dashboard.php" : "warga_dashboard.php"; ?>">Dashboard</a>
    <a href="riwayat.php" class="active">Riwayat</a>
    <?php if ($role_aktif == 'admin') : ?>
        <a href="Data_warga.php">Data Warga</a>
        <a href="kelola_warga.php">Kelola Akun Warga</a>
    <?php endif; ?>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>RIWAYAT KETINGGIAN AIR</h2>

    <div class="filter-section">
        <form method="GET" action="">
            <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai; ?>" required>
            <input type="date" name="tanggal_selesai" value="<?= $tanggal_selesai; ?>" required>
            <button type="submit">FILTER</button>
            <?php if(!empty($tanggal_mulai)): ?><a href="riwayat.php" style="margin-left: 10px; text-decoration: none; color: #e74c3c; font-weight: bold;">RESET</a><?php endif; ?>
        </form>
    </div>

    <div class="chart-container">
        <canvas id="riwayatChart"></canvas>
    </div>

    <div class="table-section">
        <h3>RIWAYAT KETINGGIAN AIR</h3>

        <?php if ($role_aktif == 'admin') : ?>
            <button type="button" class="btn-cetak" onclick="window.print()">CETAK PDF</button>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>HARI</th>
                    <th>TANGGAL</th>
                    <th>RENTANG WAKTU</th>
                    <th>JARAK SENSOR</th>
                    <th>TINGGI AIR TERTINGGI</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                while($row = mysqli_fetch_assoc($result)) { 
                    $status_cek = strtolower(trim($row['status']));
                    
                    if ($status_cek == 'siaga 1' || $status_cek == 'waspada') {
                        $badge = 'bg-waspada';
                        $status_tampil = 'WASPADA';
                    } elseif ($status_cek == 'siaga 2' || $status_cek == 'bahaya') {
                        $badge = 'bg-bahaya';
                        $status_tampil = 'BAHAYA';
                    } else {
                        $badge = 'bg-aman';
                        $status_tampil = 'AMAN';
                    }

                    $jam_mulai = floor(date('H', strtotime($row['waktu_mulai'])) / 2) * 2;
                    $jam_selesai = $jam_mulai + 2;
                    $rentang_waktu = sprintf('%02d:00 - %02d:00 WIB', $jam_mulai, $jam_selesai);
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><b><?= $nama_hari[date('l', strtotime($row['waktu_mulai']))]; ?></b></td>
                    <td><?= date('d-m-Y', strtotime($row['waktu_mulai'])); ?></td>
                    <td><?= $rentang_waktu; ?></td>
                    <td><?= $row['jarak_sensor']; ?> cm</td>
                    <td style="color: #2196F3; font-weight: bold;"><?= $row['level_air']; ?> cm</td>
                    <td><span class="badge <?= $badge; ?>"><?= $status_tampil; ?></span></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if ($role_aktif == 'admin') : ?>
            <div class="ttd-container">
                <p>Cibitung, <span class="tgl-cetak-otomatis"></span><br>
                <b>Ketua RT 02</b></p>
                <br><br><br><br>
                <p><b>(Purwadi)</b></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="script.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        initMonitoringChart('riwayatChart', <?php echo json_encode($data_labels); ?>, <?php echo json_encode($data_levels); ?>);
    });
</script>
</body>
</html>