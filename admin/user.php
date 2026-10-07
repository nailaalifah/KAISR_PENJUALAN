<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { header("location:../login.php"); exit(); }

if (isset($_POST['simpan'])) {
    $id = $_POST['user_id'];
    $nama = $_POST['nama_user'];
    $u = $_POST['username'];
    $r = $_POST['role'];

    if ($id == '') {
        $p = md5($_POST['password']);
        mysqli_query($koneksi, "INSERT INTO t_user VALUES (NULL, '$u', '$p', '$nama', '$r')");
    } else {
        $p = $_POST['password'] != '' ? ", password='".md5($_POST['password'])."'" : "";
        mysqli_query($koneksi, "UPDATE t_user SET username='$u', nama_user='$nama', role='$r' $p WHERE user_id='$id'");
    }
    header("location:user.php");
}

if (isset($_GET['hapus'])) {
    mysqli_query($koneksi, "DELETE FROM t_user WHERE user_id='".$_GET['hapus']."'");
    header("location:user.php");
}

$edit = ['user_id'=>'', 'username'=>'', 'nama_user'=>'', 'role'=>'2'];
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM t_user WHERE user_id='".$_GET['edit']."'"));
}
?>
<!DOCTYPE html>
<html>
<head><title>Kelola User</title><link rel="stylesheet" href="../asset/css/bootstrap.min.css"></head>
<body class="p-4">
<div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">&laquo; Kembali</a>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Form User</h5>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $edit['user_id'] ?>">
                    <div class="mb-2"><label>Nama</label><input type="text" name="nama_user" class="form-control" value="<?= $edit['nama_user'] ?>" required></div>
                    <div class="mb-2"><label>Username</label><input type="text" name="username" class="form-control" value="<?= $edit['username'] ?>" required></div>
                    <div class="mb-2"><label>Password</label><input type="password" name="password" class="form-control" <?= $edit['user_id'] ? '' : 'required' ?>></div>
                    <div class="mb-2">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="2" <?= $edit['role'] == 2 ? 'selected' : '' ?>>Kasir (Role 2)</option>
                            <option value="1" <?= $edit['role'] == 1 ? 'selected' : '' ?>>Admin (Role 1)</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $q = mysqli_query($koneksi, "SELECT * FROM t_user");
                    while($d = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td>#<?= $d['user_id'] ?></td>
                        <td><?= $d['username'] ?></td>
                        <td><?= $d['nama_user'] ?></td>
                        <td><span class="badge bg-<?= $d['role'] == 1 ? 'danger' : 'info' ?>"><?= $d['role'] == 1 ? 'Admin' : 'Kasir' ?></span></td>
                        <td>
                            <a href="user.php?edit=<?= $d['user_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="user.php?hapus=<?= $d['user_id'] ?>" onclick="return confirm('Hapus user?')" class="btn btn-danger btn-sm">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>