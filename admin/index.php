<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { header("location:../index.php"); exit(); }

$tgl = date('Y-m-d');
$t_penjualan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_penjualan WHERE status='selesai'"));
$t_produk    = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_produk"));
$t_pelanggan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_pelanggan"));

$d_today     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) AS total FROM t_penjualan WHERE DATE(tgl_penjualan)='$tgl' AND status='selesai'"));
$omset_today = $d_today['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard Admin</title><link rel="stylesheet" href="../asset/css/bootstrap.min.css"></head>
<body class="bg-light p-4"><div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Dashboard Admin</h3>
        <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="mb-3">
        <a href="index.php" class="btn btn-dark btn-sm active">Dashboard</a>
        <a href="user.php" class="btn btn-outline-dark btn-sm">Admin</a>
        <a href="produk.php" class="btn btn-outline-dark btn-sm">Produk</a>
        <a href="pelanggan.php" class="btn btn-outline-dark btn-sm">Pelanggan</a>
        <a href="penjualan.php" class="btn btn-outline-dark btn-sm">Transaksi</a>
        <a href="laporan_batal.php" class="btn btn-outline-dark btn-sm">Laporan Batal</a>
         <a href="laporan.php" class="btn btn-outline-dark btn-sm">Laporan</a>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white p-3 text-center"><h5>Total Penjualan</h5><h2><?= $t_penjualan ?></h2></div></div>
        <div class="col-md-3"><div class="card bg-success text-white p-3 text-center"><h5>Data Produk</h5><h2><?= $t_produk ?></h2></div></div>
        <div class="col-md-3"><div class="card bg-info text-white p-3 text-center"><h5>Data Pelanggan</h5><h2><?= $t_pelanggan ?></h2></div></div>
        <div class="col-md-3"><div class="card bg-warning p-3 text-center"><h5>Omset Hari Ini</h5><h4>Rp <?= number_format($omset_today) ?></h4></div></div>
    </div>

    <h5>Transaksi Hari Ini (<?= date('d-m-Y') ?>)</h5>
    <table class="table table-bordered bg-white">
        <thead class="table-dark"><tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php 
            $q = mysqli_query($koneksi, "SELECT p.*, pel.nama_pelanggan FROM t_penjualan p LEFT JOIN t_pelanggan pel ON p.pelanggan_id=pel.pelanggan_id WHERE DATE(p.tgl_penjualan)='$tgl' ORDER BY p.penjualan_id DESC");
            if (mysqli_num_rows($q) > 0): while($d = mysqli_fetch_assoc($q)): ?>
            <tr>
                <td>#<?= $d['penjualan_id'] ?></td>
                <td><?= $d['nama_pelanggan'] ?: 'Umum' ?></td>
                <td>Rp <?= number_format($d['total_harga']) ?></td>
                <td><span class="badge bg-<?= $d['status']=='selesai'?'success':'danger' ?>"><?= $d['status'] ?></span></td>
                <td><a href="penjualan.php?cetak=<?= $d['penjualan_id'] ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Struk</a></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" class="text-center text-muted">Belum ada transaksi hari ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div></body>
</html>