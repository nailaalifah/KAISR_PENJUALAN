<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role'])) { header("location:../login.php"); exit(); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi Batal</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">&laquo; Kembali</a>
    <h4>Laporan Transaksi Dibatalkan</h4>
    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Pelanggan</th>
                <th>Total Harga</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Menggunakan LOWER(p.status) agar aman jika di database tersimpan 'batal' atau 'BATAL'
            $q = mysqli_query($koneksi, "SELECT p.*, pel.nama_pelanggan, u.nama_user FROM t_penjualan p LEFT JOIN t_pelanggan pel ON p.pelanggan_id=pel.pelanggan_id LEFT JOIN t_user u ON p.user_id=u.user_id WHERE LOWER(p.status)='DIBATALKAN' ORDER BY p.penjualan_id DESC");
            
            if (mysqli_num_rows($q) > 0):
                while($d = mysqli_fetch_assoc($q)): ?>
                <tr>
                    <td>#<?= $d['penjualan_id'] ?></td>
                    <td><?= $d['tgl_penjualan'] ?></td>
                    <td><?= $d['nama_user'] ?></td>
                    <td><?= $d['nama_pelanggan'] ?: 'Umum' ?></td>
                    <td>Rp <?= number_format($d['total_harga']) ?></td>
                    <td><span class="badge bg-danger">Batal</span></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada transaksi yang dibatalkan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>