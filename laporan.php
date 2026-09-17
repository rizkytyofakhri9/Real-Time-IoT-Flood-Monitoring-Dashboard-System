<?php
session_start();
if (!isset($_SESSION['role'])) { header("Location: login.php"); exit; }

include 'koneksi.php';
$role_aktif = $_SESSION['role'];

$nama_aktif = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Warga');

$message = "";
$status = "";

if (isset($_POST['kirim_laporan'])) {
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $nama_warga = mysqli_real_escape_string($koneksi, $nama_aktif);

    $query = "INSERT INTO laporan (nama_warga, isi_laporan, waktu) VALUES ('$nama_warga', '$isi_laporan', NOW())";
    if (mysqli_query($koneksi, $query)) {
        $message = "Laporan lingkungan Anda berhasil dikirim ke Admin!";
        $status = "success";
    } else {
        $message = "Gagal mengirim laporan: " . mysqli_error($koneksi);
        $status = "error";
    }
}

if ($role_aktif == 'admin') {
    $query_tampil = "SELECT * FROM laporan ORDER BY waktu DESC";
} else {
    $query_tampil = "SELECT * FROM laporan WHERE nama_warga='$nama_aktif' ORDER BY waktu DESC";
}
$result_laporan = mysqli_query($koneksi, $query_tampil);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Lingkungan Warga</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
    <h3><?= ($role_aktif == 'admin') ? "MENU ADMIN" : "MENU WARGA"; ?></h3>
    <a href="<?= ($role_aktif == 'admin') ? "admin_dashboard.php" : "warga_dashboard.php"; ?>">Dashboard</a>
    <a href="riwayat.php">Riwayat</a>
    <?php if ($role_aktif == 'admin') : ?>
        <a href="data_warga.php">Data Warga</a>
        <a href="kelola_warga.php">Kelola Akun Warga</a>
    <?php endif; ?>
    <a href="laporan.php" class="active">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2><?= ($role_aktif == 'admin') ? "PENGELOLAAN PENGADUAN LINGKUNGAN" : "PENGADUAN LINGKUNGAN WARGA"; ?></h2>
    
    <?php if ($message != ""): ?>
        <div class="alert alert-<?= $status; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <?php if ($role_aktif == 'warga'): ?>
    <div class="form-section">
        <h3>BUAT LAPORAN LINGKUNGAN BARU</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Isi Laporan / Keluhan Lingkungan</label>
                <textarea name="isi_laporan" rows="5" placeholder="Tuliskan detail kondisi lingkungan...." required></textarea>
            </div>
            <button type="submit" name="kirim_laporan" class="btn-submit">KIRIM LAPORAN</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="table-section">
        <h3 class="print-title"><?= ($role_aktif == 'admin') ? "DAFTAR PENGADUAN KELUHAN WARGA MASUK" : "RIWAYAT PENGADUAN SAYA"; ?></h3>
        
        <?php if ($role_aktif == 'admin'): ?>
            <button type="button" class="btn-cetak" onclick="window.print()">CETAK PDF</button>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 20%;">WAKTU / TANGGAL</th>
                    <?php if ($role_aktif == 'admin'): ?>
                        <th style="width: 20%;">NAMA WARGA</th>
                    <?php endif; ?>
                    <th style="width: 55%;">ISI LAPORAN / KELUHAN</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if (mysqli_num_rows($result_laporan) > 0) {
                    while ($row = mysqli_fetch_assoc($result_laporan)) {
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++; ?></td>
                    <td style="text-align: center;"><?= date('d-m-Y H:i:s', strtotime($row['waktu'])); ?> WIB</td>
                    <?php if ($role_aktif == 'admin'): ?>
                        <td><b><?= $row['nama_warga']; ?></b></td>
                    <?php endif; ?>
                    <td style="text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($row['isi_laporan'])); ?></td>
                </tr>
                <?php 
                    }
                } else {
                    $colspan_val = ($role_aktif == 'admin') ? 4 : 3;
                    echo "<tr><td colspan='$colspan_val'>Belum ada catatan laporan lingkungan saat ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="ttd-container">
            <p>Cibitung, <span class="tgl-cetak-otomatis"></span><br>
            <b>Ketua RT 02</b></p>
            <br><br><br><br>
            <p><b>(Purwadi)</b></p>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>