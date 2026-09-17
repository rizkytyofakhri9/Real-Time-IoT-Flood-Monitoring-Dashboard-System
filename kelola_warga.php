<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$message = "";
$status = "";

if (isset($_POST['tambah_warga'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role = 'warga'; 

    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $message = "Gagal! Username sudah digunakan oleh warga lain.";
        $status = "error";
    } else {
        $query = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        if (mysqli_query($koneksi, $query)) {
            $message = "Berhasil menambah akun warga baru!";
            $status = "success";
        } else {
            $message = "Gagal menambah data: " . mysqli_error($koneksi);
            $status = "error";
        }
    }
}

if (isset($_POST['edit_warga'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);

    $query = "UPDATE users SET username='$username', password='$password', nama_lengkap='$nama_lengkap' WHERE id='$id_user' AND role='warga'";
    if (mysqli_query($koneksi, $query)) {
        $message = "Data warga berhasil diperbarui!";
        $status = "success";
        header("Refresh: 1; url=kelola_warga.php");
    } else {
        $message = "Gagal memperbarui data: " . mysqli_error($koneksi);
        $status = "error";
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $query = "DELETE FROM users WHERE id='$id_hapus' AND role='warga'";
    if (mysqli_query($koneksi, $query)) {
        $message = "Akun warga berhasil dihapus!";
        $status = "success";
        header("Refresh: 1; url=kelola_warga.php");
    } else {
        $message = "Gagal menghapus data: " . mysqli_error($koneksi);
        $status = "error";
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $res_edit = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id_edit' AND role='warga'");
    $edit_data = mysqli_fetch_assoc($res_edit);
}

$result_warga = mysqli_query($koneksi, "SELECT * FROM users WHERE role='warga' ORDER BY id DESC") 
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
    <a href="data_warga.php">Data Warga</a>
    <a href="kelola_warga.php" class="active">Kelola Akun Warga</a>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>KELOLA DATA AKUN WARGA</h2>
    
    <?php if ($message != ""): ?>
        <div class="alert alert-<?= $status; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <div class="form-section">
        <?php if ($edit_data): ?>
            <h3>EDIT DATA AKUN WARGA</h3>
            <form method="POST" action="">
                <input type="hidden" name="id_user" value="<?= $edit_data['id']; ?>">
                <div class="form-group">
                    <label>Nama Lengkap Warga</label>
                    <input type="text" name="nama_lengkap" value="<?= $edit_data['nama_lengkap']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Username Login</label>
                    <input type="text" name="username" value="<?= $edit_data['username']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Password Akun</label>
                    <input type="text" name="password" value="<?= $edit_data['password']; ?>" required>
                </div>
                <button type="submit" name="edit_warga" class="btn-submit">SIMPAN PERUBAHAN</button>
            </form>
        <?php else: ?>
            <h3>TAMBAH AKUN WARGA BARU</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap Warga</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" id="input_nama" name="nama_lengkap" placeholder="Masukkan nama asli warga" required>
                        <button type="button" class="btn-list" onclick="toggleList()">List</button>
                    </div>
                    <div id="div_daftar_warga" style="display:none; border:1px solid #ccc; margin-top:5px; max-height:100px; overflow-y:auto; background:white;">
                        <?php
                        $q_warga = mysqli_query($koneksi, "SELECT nama_lengkap FROM warga ORDER BY nama_lengkap ASC");
                        while($w = mysqli_fetch_assoc($q_warga)) {
                            echo "<div style='padding:5px; cursor:pointer; border-bottom:1px solid #eee;' onclick=\"pilihWarga('".$w['nama_lengkap']."')\">".$w['nama_lengkap']."</div>";
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Username Login</label>
                    <input type="text" name="username" placeholder="Buat username unik" required>
                </div>
                <div class="form-group">
                    <label>Password Akun</label>
                    <input type="text" name="password" placeholder="Masukkan password awal" required>
                </div>
                <button type="submit" name="tambah_warga" class="btn-submit">DAFTARKAN AKUN</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="table-section">
        <h3>DAFTAR AKUN WARGA TERDAFTAR</h3>
        <button type="button" class="btn-cetak" onclick="window.print()">CETAK PDF</button>
        
        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA LENGKAP</th>
                    <th>USERNAME</th>
                    <th>PASSWORD (PLAIN)</th>
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
                    <td><mark><?= $row['username']; ?></mark></td>
                    <td><?= $row['password']; ?></td>
                    <td class="no-print">
                        <a href="kelola_warga.php?edit=<?= $row['id']; ?>" class="btn-edit">EDIT</a>
                        <a href="kelola_warga.php?hapus=<?= $row['id']; ?>" class="btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus warga ini?')">HAPUS</a>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='5'>Belum ada akun warga yang didaftarkan.</td></tr>";
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