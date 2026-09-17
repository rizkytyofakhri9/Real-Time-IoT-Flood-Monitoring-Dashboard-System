<?php
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['level_air']) && isset($_POST['status'])) {
        
        $level  = mysqli_real_escape_string($koneksi, $_POST['level_air']);
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);

        // Query SQL tanpa kolom lokasi
        $query = "INSERT INTO tabel_banjir (level_air, status) VALUES ('$level', '$status')";
        
        if (mysqli_query($koneksi, $query)) {
            echo "Data Berhasil Disimpan!";
        } else {
            echo "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    } else {
        echo "Data POST tidak lengkap.";
    }
} else {
    echo "Halaman ini hanya menerima request POST dari NodeMCU.";
}
mysqli_close($koneksi);
?>