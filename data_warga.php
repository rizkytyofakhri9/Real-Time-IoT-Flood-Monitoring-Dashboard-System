<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$message = "";
$status = "";

if (isset($_POST['tambah_data'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $nomor_hp = mysqli_real_escape_string($koneksi, $_POST['nomor_hp']);

    $query = "INSERT INTO warga (nama_lengkap, alamat, nomor_hp) VALUES ('$nama_lengkap', '$alamat', '$nomor_hp')";
    if (mysqli_query($koneksi, $query)) {
        $message = "Berhasil menambah data warga baru!";
        $status = "success";
    } else {
        $message = "Gagal menambah data: " . mysqli_error($koneksi);
        $status = "error";
    }
}

if (isset($_POST['edit_data'])) {
    $id_warga = mysqli_real_escape_string($koneksi, $_POST['id_warga']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $nomor_hp = mysqli_real_escape_string($koneksi, $_POST['nomor_hp']);

    $query = "UPDATE warga SET nama_lengkap='$nama_lengkap', alamat='$alamat', nomor_hp='$nomor_hp' WHERE id='$id_warga'";
    if (mysqli_query($koneksi, $query)) {
        $message = "Data warga berhasil diperbarui!";
        $status = "success";
        header("Refresh: 1; url=data_warga.php");
    } else {
        $message = "Gagal memperbarui data: " . mysqli_error($koneksi);
        $status = "error";
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $query = "DELETE FROM warga WHERE id='$id_hapus'";
    if (mysqli_query($koneksi, $query)) {
        $message = "Data warga berhasil dihapus!";
        $status = "success";
        header("Refresh: 1; url=data_warga.php");
    } else {
        $message = "Gagal menghapus data: " . mysqli_error($koneksi);
        $status = "error";
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $res_edit = mysqli_query($koneksi, "SELECT * FROM warga WHERE id='$id_edit'");
    $edit_data = mysqli_fetch_assoc($res_edit);
}

$result_warga = mysqli_query($koneksi, "SELECT * FROM warga ORDER BY id DESC") 
                or die("Error Database Anda: " . mysqli_error($koneksi));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Data Warga - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
    <h3>MENU ADMIN</h3>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="riwayat.php">Riwayat</a>
    <a href="data_warga.php" class="active">Data Warga</a>
    <a href="kelola_warga.php">Kelola Akun Warga</a>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>KELOLA DATA WARGA</h2>
    
    <?php if ($message != ""): ?>
        <div class="alert alert-<?= $status; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <div class="form-section">
        <?php if ($edit_data): ?>
            <h3>EDIT DATA WARGA</h3>
            <form method="POST" action="">
                <input type="hidden" name="id_warga" value="<?= $edit_data['id']; ?>">
                <div class="form-group">
                    <label>Nama Lengkap Warga</label>
                    <input type="text" name="nama_lengkap" value="<?= $edit_data['nama_lengkap']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Alamat Rumah</label>
                    <input type="text" name="alamat" value="<?= $edit_data['alamat']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Nomor HP Aktif</label>
                    <input type="text" name="nomor_hp" value="<?= $edit_data['nomor_hp']; ?>" required>
                </div>
                <button type="submit" name="edit_data" class="btn-submit">SIMPAN PERUBAHAN</button>
                <a href="data_warga.php" class="btn-cancel">BATAL</a>
            </form>
        <?php else: ?>
            <h3>TAMBAH DATA WARGA BARU</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap Warga</label>
                    <input type="text" name="nama_lengkap" placeholder="Masukkan nama asli warga" required>
                </div>
                <div class="form-group">
                    <label>Alamat Rumah</label>
                    <input type="text" name="alamat" placeholder="Masukkan alamat lengkap" required>
                </div>
                <div class="form-group">
                    <label>Nomor HP Aktif</label>
                    <input type="text" name="nomor_hp" placeholder="Masukkan nomor HP/WhatsApp" required>
                </div>
                <button type="submit" name="tambah_data" class="btn-submit">SIMPAN DATA</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="table-section">
        <h3>DAFTAR DATA WARGA</h3>
        <button type="button" class="btn-cetak" onclick="window.print()">CETAK PDF</button>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA LENGKAP</th>
                    <th>ALAMAT</th>
                    <th>NOMOR HP</th>
                    <th class="no-print">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if (mysqli_num_rows($result_warga) > 0) {
                    while ($row = mysqli_fetch_assoc($result_warga)) {
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><b><?= $row['nama_lengkap']; ?></b></td>
                    <td><?= $row['alamat']; ?></td>
                    <td><?= $row['nomor_hp']; ?></td>
                    <td class="no-print">
                        <a href="data_warga.php?edit=<?= $row['id']; ?>" class="btn-edit">EDIT</a>
                        <a href="data_warga.php?hapus=<?= $row['id']; ?>" class="btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data warga ini?')">HAPUS</a>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='5'>Belum ada data fisik warga yang tersimpan di sistem.</td></tr>";
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