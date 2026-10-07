<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $u = mysqli_real_escape_string($koneksi, $_POST['username']);
    $p = md5($_POST['password']);

    $q = mysqli_query($koneksi, "SELECT * FROM t_user WHERE username='$u' AND password='$p'");
    if (mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        $_SESSION['user_id'] = $d['user_id'];
        $_SESSION['nama_user'] = $d['nama_user'];
        $_SESSION['role'] = $d['role'];

        if ($d['role'] == 1) {
            header("location:admin/index.php");
        } else {
            header("location:kasir/index.php");
        }
        exit();
    } else {
        echo "<script>alert('Username/Password Salah!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login System</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
<div class="card p-4 shadow" style="width: 320px;">
    <h4 class="text-center mb-3">Login Kasir</h4>
    <form method="POST">
        <div class="mb-2">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>