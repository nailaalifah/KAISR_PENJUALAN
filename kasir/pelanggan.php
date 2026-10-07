<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role'])) { header("location:../login.php"); exit(); }

if (isset($_POST['simpan'])) {
    $id = $_POST['pelanggan_id'];
    $nama = $_POST['nama_pelanggan'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['nomor_telepon'];
    $status = $_POST['status_member'];

    if ($id == '') {
        mysqli_query($koneksi, "INSERT INTO t_pelanggan VALUES (NULL, '$nama', '$alamat', '$hp', '$status')");
    } else {
        mysqli_query($koneksi, "UPDATE t_pelanggan SET nama_pelanggan='$nama', alamat='$alamat', nomor_telepon='$hp', status_member='$status' WHERE pelanggan_id='$id'");
    }
    header("location:pelanggan.php");
}

if (isset($_GET['hapus'])) {
    mysqli_query($koneksi, "DELETE FROM t_pelanggan WHERE pelanggan_id='".$_GET['hapus']."'");
    header("location:pelanggan.php");
}

$edit = ['pelanggan_id'=>'', 'nama_pelanggan'=>'', 'alamat'=>'', 'nomor_telepon'=>'', 'status_member'=>'aktif'];
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM t_pelanggan WHERE pelanggan_id='".$_GET['edit']."'"));
}
?>
<!DOCTYPE html>
<html>
<head><title>Data Pelanggan</title><link rel="stylesheet" href="../asset/css/bootstrap.min.css"></head>
<body class="p-4">
<div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">&laquo; Kembali</a>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5>Form Pelanggan</h5>
                <form method="POST">
                    <input type="hidden" name="pelanggan_id" value="<?= $edit['pelanggan_id'] ?>">
                    <div class="mb-2"><label>Nama Pelanggan</label><input type="text" name="nama_pelanggan" class="form-control" value="<?= $edit['nama_pelanggan'] ?>" required></div>
                    <div class="mb-2"><label>Alamat</label><textarea name="alamat" class="form-control" required><?= $edit['alamat'] ?></textarea></div>
                    <div class="mb-2"><label>No HP</label><input type="text" name="nomor_telepon" class="form-control" value="<?= $edit['nomor_telepon'] ?>" inputmode="numeric" pattern="[0-9]{10,13}" minlength="10" maxlength="13" oninput="this.value=this.value.replace(/\D/g,'')" placeholder="081234567890" required></div>
                    <div class="mb-2">
                        <label>Status Member</label>
                        <select name="status_member" class="form-control">
                            <option value="aktif" <?= $edit['status_member'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $edit['status_member'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100 mt-2">Simpan</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <form method="GET" class="d-flex gap-2 mb-3">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama / no HP..." value="<?= $_GET['cari'] ?? '' ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if(isset($_GET['cari'])): ?><a href="pelanggan.php" class="btn btn-secondary">Reset</a><?php endif; ?>
            </form>
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Nama</th><th>Alamat</th><th>No HP</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php 
                    $where = isset($_GET['cari']) ? "WHERE nama_pelanggan LIKE '%".$_GET['cari']."%' OR nomor_telepon LIKE '%".$_GET['cari']."%'" : "";
                    $q = mysqli_query($koneksi, "SELECT * FROM t_pelanggan $where");
                    while($d = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td>#<?= $d['pelanggan_id'] ?></td>
                        <td><?= $d['nama_pelanggan'] ?></td>
                        <td><?= $d['alamat'] ?></td>
                        <td><?= $d['nomor_telepon'] ?></td>
                        <td><span class="badge bg-<?= $d['status_member'] == 'aktif' ? 'success' : 'danger' ?>"><?= ucfirst($d['status_member']) ?></span></td>
                        <td>
                            <a href="pelanggan.php?edit=<?= $d['pelanggan_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="pelanggan.php?hapus=<?= $d['pelanggan_id'] ?>" onclick="return confirm('Hapus pelanggan ini?')" class="btn btn-danger btn-sm">Hapus</a>
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