<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { header("location:../login.php"); exit(); }

if (isset($_POST['simpan'])) {
    $id = $_POST['produk_id'];
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    if ($id == '') {
        mysqli_query($koneksi, "INSERT INTO t_produk VALUES (NULL, '$nama', '$harga', '$stok')");
    } else {
        mysqli_query($koneksi, "UPDATE t_produk SET nama_produk='$nama', harga='$harga', stok='$stok' WHERE produk_id='$id'");
    }
    header("location:produk.php");
}

if (isset($_GET['hapus'])) {
    mysqli_query($koneksi, "DELETE FROM t_produk WHERE produk_id='".$_GET['hapus']."'");
    header("location:produk.php");
}

$edit = ['produk_id'=>'', 'nama_produk'=>'', 'harga'=>'', 'stok'=>''];
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM t_produk WHERE produk_id='".$_GET['edit']."'"));
}

// Logika Pencarian
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
?>
<!DOCTYPE html>
<html>
<head><title>Kelola Produk</title><link rel="stylesheet" href="../asset/css/bootstrap.min.css"></head>
<body class="p-4">
<div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">&laquo; Kembali</a>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Form Produk</h5>
                <form method="POST">
                    <input type="hidden" name="produk_id" value="<?= $edit['produk_id'] ?>">
                    <div class="mb-2"><label>Nama Produk</label><input type="text" name="nama_produk" class="form-control" value="<?= $edit['nama_produk'] ?>" required></div>
                    <div class="mb-2"><label>Harga (Rp)</label><input type="number" name="harga" class="form-control" value="<?= $edit['harga'] ?>" required></div>
                    <div class="mb-2"><label>Stok</label><input type="number" name="stok" class="form-control" value="<?= $edit['stok'] ?>" required></div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Form Pencarian -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="cari" class="form-control" placeholder="Cari ID atau nama produk..." value="<?= htmlspecialchars($cari) ?>">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                    <?php if ($cari != ''): ?>
                        <a href="produk.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Nama Produk</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php 
                    // Query Pencarian berdasarkan Nama Produk ATAU produk_id
                    if ($cari != '') {
                        $q = mysqli_query($koneksi, "SELECT * FROM t_produk WHERE nama_produk LIKE '%$cari%' OR produk_id LIKE '%$cari%'");
                    } else {
                        $q = mysqli_query($koneksi, "SELECT * FROM t_produk");
                    }
                    while($d = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td>#<?= $d['produk_id'] ?></td>
                        <td><?= $d['nama_produk'] ?></td>
                        <td>Rp <?= number_format($d['harga']) ?></td>
                        <td><?= $d['stok'] ?></td>
                        <td>
                            <a href="produk.php?edit=<?= $d['produk_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="produk.php?hapus=<?= $d['produk_id'] ?>" onclick="return confirm('Hapus produk?')" class="btn btn-danger btn-sm">Hapus</a>
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