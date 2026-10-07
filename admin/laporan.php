<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { header("location:../login.php"); exit(); }

$where = "WHERE p.status='selesai'";
if (isset($_GET['filter'])) {
    $tipe = $_GET['tipe'];
    if ($tipe == 'hari' && !empty($_GET['tgl'])) {
        $tgl = $_GET['tgl'];
        $where .= " AND DATE(p.tgl_penjualan) = '$tgl'";
    } elseif ($tipe == 'bulan' && !empty($_GET['bulan']) && !empty($_GET['tahun'])) {
        $bulan = $_GET['bulan'];
        $tahun = $_GET['tahun'];
        $where .= " AND MONTH(p.tgl_penjualan) = '$bulan' AND YEAR(p.tgl_penjualan) = '$tahun'";
    }
}

$query = mysqli_query($koneksi, "SELECT p.*, pel.nama_pelanggan, u.nama_user FROM t_penjualan p LEFT JOIN t_pelanggan pel ON p.pelanggan_id=pel.pelanggan_id LEFT JOIN t_user u ON p.user_id=u.user_id $where ORDER BY p.penjualan_id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container-fluid">
    <a href="index.php" class="btn btn-secondary mb-3 d-print-none">&laquo; Kembali</a>
    <h3 class="mb-3">Laporan Penjualan (Selesai)</h3>

    <form method="GET" class="row g-2 mb-4 d-print-none">
        <div class="col-md-2">
            <select name="tipe" id="tipe_filter" class="form-control" onchange="toggleFilterInput()">
                <option value="hari" <?= ($_GET['tipe']??'') == 'hari' ? 'selected':'' ?>>Harian</option>
                <option value="bulan" <?= ($_GET['tipe']??'') == 'bulan' ? 'selected':'' ?>>Bulanan</option>
            </select>
        </div>

        <div class="col-md-3" id="box_tgl">
            <input type="date" name="tgl" class="form-control" value="<?= $_GET['tgl'] ?? date('Y-m-d') ?>">
        </div>

        <div class="col-md-2" id="box_bulan" style="display: none;">
            <select name="bulan" class="form-control">
                <?php
                $nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                $bln_pilih = $_GET['bulan'] ?? date('m');
                foreach ($nama_bulan as $k => $v) { echo "<option value='$k' ".($bln_pilih == $k ? 'selected':'').">$v</option>"; }
                ?>
            </select>
        </div>

        <div class="col-md-2" id="box_tahun" style="display: none;">
            <select name="tahun" class="form-control">
                <?php
                $thn_sekarang = date('Y');
                $thn_pilih = $_GET['tahun'] ?? $thn_sekarang;
                for ($t = $thn_sekarang; $t >= $thn_sekarang - 5; $t--) { echo "<option value='$t' ".($thn_pilih == $t ? 'selected':'').">$t</option>"; }
                ?>
            </select>
        </div>

        <div class="col-md-3">
            <button type="submit" name="filter" class="btn btn-primary">Filter</button>
            <button type="button" onclick="window.print()" class="btn btn-success">Print</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Tanggal & Jam</th>
                <th>Kasir</th>
                <th>Pelanggan</th>
                <th>Detail Produk Dibeli</th>
                <th>Diskon</th>
                <th>Total Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; $grand_total = 0;
            if (mysqli_num_rows($query) > 0):
                while($d = mysqli_fetch_assoc($query)): 
                    $p_id = $d['penjualan_id'];
                    $q_detail = mysqli_query($koneksi, "SELECT d.*, pr.nama_produk FROM t_penjualan_detail d JOIN t_produk pr ON d.produk_id=pr.produk_id WHERE d.penjualan_id='$p_id'");
                    $subtotal_kotor = 0; $produk_list = [];
                    while($dt = mysqli_fetch_assoc($q_detail)){
                        $subtotal_kotor += $dt['subtotal'];
                        $produk_list[] = $dt['nama_produk'] . " (" . $dt['jumlah_produk'] . "x)";
                    }
                    
                    $diskon = $d['pelanggan_id'] ? ($subtotal_kotor * 0.10) : 0;
                    $grand_total += $d['total_harga'];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d-m-Y H:i', strtotime($d['tgl_penjualan'])) ?></td>
                    <td><?= $d['nama_user'] ?? 'Kasir' ?></td>
                    <td><?= $d['nama_pelanggan'] ?: 'Umum' ?></td>
                    <td><?= implode('<br>', $produk_list) ?></td>
                    <td><?= $diskon > 0 ? '10% (Rp '.number_format($diskon).')' : '-' ?></td>
                    <td>Rp <?= number_format($d['total_harga']) ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="7" class="text-center text-muted">Data transaksi tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold table-secondary">
                <th colspan="6" class="text-end">Total Pendapatan:</th>
                <th>Rp <?= number_format($grand_total) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<script>
function toggleFilterInput() {
    var tipe = document.getElementById('tipe_filter').value;
    document.getElementById('box_tgl').style.display = (tipe === 'bulan') ? 'none' : 'block';
    document.getElementById('box_bulan').style.display = (tipe === 'bulan') ? 'block' : 'none';
    document.getElementById('box_tahun').style.display = (tipe === 'bulan') ? 'block' : 'none';
}
toggleFilterInput();
</script>
</body>
</html>