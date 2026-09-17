<?php
include 'koneksi.php';
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query  = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        if ($password == $row['password']) {
            $_SESSION['id_user']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama']     = $row['nama_lengkap'];
            $_SESSION['role']     = $row['role'];

            if ($row['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else if ($row['role'] == 'warga') {
                header("Location: warga_dashboard.php");
            }
            exit;
        } else { $error = "Password salah!"; }
    } else { $error = "Username tidak ditemukan!"; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Monitoring Banjir</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        .login-container h2 { margin-bottom: 24px; color: #333; letter-spacing: 1px; }
        .input-field { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 10px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-login:hover { background-color: #0b7dda; }
        .error-msg { color: red; margin-bottom: 15px; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>LOGIN MONITORING BANJIR</h2>
    <?php if($error != "") { echo "<div class='error-msg'>$error</div>"; } ?>
    <form method="POST" action="">
        <input type="text" name="username" class="input-field" placeholder="User Name" required>
        <input type="password" name="password" class="input-field" placeholder="Password" required>
        <button type="submit" class="btn-login">Login</button>
    </form>
</div>

</body>
</html>