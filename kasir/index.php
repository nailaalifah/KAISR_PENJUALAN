<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role'])) { header("location:../index.php"); exit(); }

// CETAK STRUK
if (isset($_GET['cetak'])) {
    $id = $_GET['cetak']; $bayar = $_SESSION['bayar_terakhir'][$id] ?? 0;
    $p = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT p.*, pel.nama_pelanggan, u.nama_user FROM t_penjualan p LEFT JOIN t_pelanggan pel ON p.pelanggan_id=pel.pelanggan_id LEFT JOIN t_user u ON p.user_id=u.user_id WHERE p.penjualan_id='$id'"));
    $det = mysqli_query($koneksi, "SELECT d.*, pr.nama_produk FROM t_penjualan_detail d JOIN t_produk pr ON d.produk_id=pr.produk_id WHERE d.penjualan_id='$id'");
    ?>
    <!DOCTYPE html><html><head><title>Struk</title>
    <style>@media print { .no-print { display: none; } }</style>
    </head><body onload="window.print()" style="font-family:monospace;width:250px;">
        <div class="no-print" style="margin-bottom: 10px;"><a href="index.php">&laquo; Kembali</a></div>
        <h4 style="text-align:center;margin:0;">STRUK PENJUALAN</h4>
        <p>ID: #<?= $p['penjualan_id'] ?><br>Kasir: <?= $p['nama_user'] ?><br>Member: <?= $p['nama_pelanggan'] ?: 'Umum' ?></p><hr>
        <?php while($r = mysqli_fetch_assoc($det)): ?>
            <div><?= $r['nama_produk'] ?> (<?= $r['jumlah_produk'] ?>x) = Rp <?= number_format($r['subtotal']) ?></div>
        <?php endwhile; ?><hr>
        <div>TOTAL: Rp <?= number_format($p['total_harga']) ?></div>
        <div>BAYAR: Rp <?= number_format($bayar) ?></div>
        <b>KEMBALI: Rp <?= number_format($bayar - $p['total_harga']) ?></b>
    </body></html>
    <?php exit();
}

// PROSES HAPUS / BATALKAN TRANSAKSI RIWAYAT
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // 1. Kembalikan stok produk
    $det = mysqli_query($koneksi, "SELECT * FROM t_penjualan_detail WHERE penjualan_id='$id_hapus'");
    while($d = mysqli_fetch_assoc($det)) {
        mysqli_query($koneksi, "UPDATE t_produk SET stok = stok + ".$d['jumlah_produk']." WHERE produk_id='".$d['produk_id']."'");
    }
    
    // 2. Ubah status transaksi menjadi 'DIBATALKAN'
    $update = mysqli_query($koneksi, "UPDATE t_penjualan SET status='DIBATALKAN' WHERE penjualan_id='$id_hapus'");
    
    // 3. Pengecekan jika gagal update di database
    if (!$update) {
        die("Gagal membatalkan transaksi: " . mysqli_error($koneksi));
    }
    
    // 4. Redirect kembali ke index.php kasir
    header("location:index.php"); 
    exit();
}

// SIMPAN TRANSAKSI
$auto_print_id = 0;
if (isset($_POST['transaksi'])) {
    $pel_id = $_POST['pelanggan_id'] != '' ? "'".$_POST['pelanggan_id']."'" : "NULL";
    $bayar = $_POST['bayar']; $user_id = $_SESSION['user_id']; $tgl = date('Y-m-d H:i:s');
    
    // -------------------------------------------------------------------------
    // VALIDASI STOK: Cek apakah ada produk yang dibeli melebihi stok t_produk
    // -------------------------------------------------------------------------
    foreach ($_POST['produk_id'] as $i => $prd_id) {
        $qty = (int)$_POST['jumlah'][$i];
        if ($prd_id && $qty > 0) {
            $cek_stok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_produk, stok FROM t_produk WHERE produk_id='$prd_id'"));
            if ($qty > $cek_stok['stok']) {
                echo "<script>
                    alert('Transaksi Gagal! Stok produk \"".$cek_stok['nama_produk']."\" tidak mencukupi. (Stok Tersedia: ".$cek_stok['stok'].")');
                    window.history.back();
                </script>";
                exit();
            }
        }
    }

    // SIMPAN KE DATABASE (Jika stok mencukupi)
    mysqli_query($koneksi, "INSERT INTO t_penjualan VALUES (NULL, '$tgl', 0, $pel_id, '$user_id', 'selesai')");
    $p_id = mysqli_insert_id($koneksi); $total = 0;

    foreach ($_POST['produk_id'] as $i => $prd_id) {
        $qty = (int)$_POST['jumlah'][$i];
        if ($prd_id && $qty > 0) {
            $prd = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM t_produk WHERE produk_id='$prd_id'"));
            $sub = $prd['harga'] * $qty; $total += $sub;
            mysqli_query($koneksi, "INSERT INTO t_penjualan_detail VALUES (NULL, '$p_id', '$prd_id', '$qty', '$sub')");
            mysqli_query($koneksi, "UPDATE t_produk SET stok = stok - $qty WHERE produk_id='$prd_id'");
        }
    }
    
    if ($_POST['pelanggan_id'] != '') $total -= ($total * 0.10);

    if ($bayar < $total) {
        $det = mysqli_query($koneksi, "SELECT * FROM t_penjualan_detail WHERE penjualan_id='$p_id'");
        while($d = mysqli_fetch_assoc($det)) mysqli_query($koneksi, "UPDATE t_produk SET stok = stok + ".$d['jumlah_produk']." WHERE produk_id='".$d['produk_id']."'");
        mysqli_query($koneksi, "DELETE FROM t_penjualan WHERE penjualan_id='$p_id'");
        mysqli_query($koneksi, "DELETE FROM t_penjualan_detail WHERE penjualan_id='$p_id'");
        echo "<script>alert('Uang pembayaran kurang!'); window.history.back();</script>"; exit();
    }

    mysqli_query($koneksi, "UPDATE t_penjualan SET total_harga='$total' WHERE penjualan_id='$p_id'");
    $_SESSION['bayar_terakhir'][$p_id] = $bayar;
    $auto_print_id = $p_id;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transaksi Penjualan</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
</head>
<body class="p-4"><div class="container">
    <!-- NAVIGASI KASIR / ADMIN -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="index.php" class="btn btn-secondary">&laquo; Kembali Ke Dashboard</a>
            <?php endif; ?>
            <a href="pelanggan.php" class="btn btn-info text-white">Data Pelanggan (Member)</a>
        </div>
        <div>
            <span class="me-2 fw-bold">Kasir: <?= $_SESSION['nama_user'] ?? 'User' ?></span>
            <a href="../logout.php" onclick="return confirm('Yakin ingin logout?')" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Transaksi Baru</h5>
        <form method="POST" id="form-transaksi" onsubmit="return verifikasiPembayaran()">
            <div class="mb-2">
                <label>Pelanggan</label>
                <select name="pelanggan_id" id="pelanggan_id" class="form-control select2" onchange="hitungTotal()">
                    <option value="" data-diskon="0">-- Umum (Tanpa Diskon) --</option>
                    <?php $pel = mysqli_query($koneksi, "SELECT * FROM t_pelanggan WHERE status_member='aktif'");
                    while($p = mysqli_fetch_assoc($pel)) echo "<option value='".$p['pelanggan_id']."' data-diskon='0.10'>".$p['nama_pelanggan']." (Member 10%)</option>"; ?>
                </select>
            </div>
            <div id="item-list">
                <div class="row g-2 mb-2 item">
                    <div class="col-7">
                        <select name="produk_id[]" class="form-control produk-select select2" onchange="hitungTotal()" required>
                            <option value="" data-harga="0">-- Pilih Produk --</option>
                            <?php $pr = mysqli_query($koneksi, "SELECT * FROM t_produk WHERE stok > 0");
                            while($r = mysqli_fetch_assoc($pr)) echo "<option value='".$r['produk_id']."' data-harga='".$r['harga']."'>".$r['nama_produk']." (Rp".number_format($r['harga']).") [Stok: ".$r['stok']."]</option>"; ?>
                        </select>
                    </div>
                    <div class="col-3"><input type="number" name="jumlah[]" class="form-control jumlah-input" value="1" min="1" oninput="hitungTotal()" required></div>
                    <div class="col-2 fw-bold align-self-center subtotal-text">Rp 0</div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="tambahRow()">+ Tambah Produk</button>
            <div class="p-2 bg-light mb-2 d-flex justify-content-between">
                <span>Total Belanja:</span><h4 class="text-primary m-0" id="tampil-total">Rp 0</h4>
            </div>
            
            <div class="mb-2">
                <label>Nominal Uang Bayar</label>
                <input type="number" name="bayar" id="bayar" class="form-control" placeholder="Nominal Uang Bayar (Rp)" oninput="hitungTotal()" required>
            </div>

            <!-- Kolom Kembalian -->
            <div class="p-2 bg-light mb-3 d-flex justify-content-between align-items-center">
                <span>Kembalian:</span>
                <h4 class="text-success m-0" id="tampil-kembalian">Rp 0</h4>
            </div>
            
            <button type="submit" name="transaksi" class="btn btn-success w-100 mb-2">Bayar & Cetak</button>
            <button type="button" class="btn btn-danger w-100" onclick="batalTransaksi()">Batal Transaksi</button>
        </form>
    </div>

    <h5>Riwayat Penjualan Hari Ini</h5>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php 
            $q = mysqli_query($koneksi, "SELECT p.*, pel.nama_pelanggan FROM t_penjualan p LEFT JOIN t_pelanggan pel ON p.pelanggan_id=pel.pelanggan_id WHERE p.status='selesai' AND DATE(p.tgl_penjualan) = CURDATE() ORDER BY p.penjualan_id DESC");
            while($d = mysqli_fetch_assoc($q)): ?>
            <tr>
                <td>#<?= $d['penjualan_id'] ?></td><td><?= $d['tgl_penjualan'] ?></td><td><?= $d['nama_pelanggan'] ?: 'Umum' ?></td><td>Rp <?= number_format($d['total_harga']) ?></td>
                <td>
                    <a href="index.php?cetak=<?= $d['penjualan_id'] ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Struk</a>
                    <a href="index.php?hapus=<?= $d['penjualan_id'] ?>" onclick="return confirm('Yakin ingin membatalkan transaksi ini? Stok produk akan dikembalikan.')" class="btn btn-danger btn-sm">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() { 
    initSelect2(); 
    <?php if ($auto_print_id > 0): ?>
        window.open('index.php?cetak=<?= $auto_print_id ?>', '_blank');
        window.location.href = 'index.php';
    <?php endif; ?>
});

function initSelect2() { $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); }

function tambahRow() {
    $('.produk-select').select2('destroy');
    var item = document.querySelector('.item').cloneNode(true);
    item.querySelector('.jumlah-input').value = 1;
    item.querySelector('.subtotal-text').innerText = 'Rp 0';
    document.getElementById('item-list').appendChild(item);
    initSelect2(); hitungTotal();
}

function hitungTotal() {
    var total = 0;
    document.querySelectorAll('.item').forEach(function(el) {
        var select = el.querySelector('.produk-select');
        var harga = select.options[select.selectedIndex].getAttribute('data-harga') || 0;
        var qty = el.querySelector('.jumlah-input').value || 0;
        var sub = harga * qty;
        el.querySelector('.subtotal-text').innerText = 'Rp ' + sub.toLocaleString('id-ID');
        total += sub;
    });
    var selPel = document.getElementById('pelanggan_id');
    var diskon = selPel.options[selPel.selectedIndex].getAttribute('data-diskon') || 0;
    if (diskon > 0) total -= (total * diskon);
    
    document.getElementById('tampil-total').innerText = 'Rp ' + total.toLocaleString('id-ID');

    // Hitung Kembalian Otomatis
    var bayar = parseInt(document.getElementById('bayar').value) || 0;
    var kembalian = bayar - total;
    
    var elKembalian = document.getElementById('tampil-kembalian');
    if (bayar === 0) {
        elKembalian.innerText = 'Rp 0';
        elKembalian.className = "text-success m-0";
    } else if (kembalian >= 0) {
        elKembalian.innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
        elKembalian.className = "text-success m-0";
    } else {
        elKembalian.innerText = 'Rp 0 (Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID') + ')';
        elKembalian.className = "text-danger m-0";
    }
}

function verifikasiPembayaran() {
    var totalText = document.getElementById('tampil-total').innerText.replace(/[^0-9]/g, '');
    var totalBelanja = parseInt(totalText) || 0;
    var bayar = parseInt(document.getElementById('bayar').value) || 0;

    if (bayar < totalBelanja) {
        alert('Uang pembayaran kurang! Mohon periksa kembali nominal bayar.');
        document.getElementById('bayar').focus();
        return false;
    }
    return true;
}

function batalTransaksi() {
    if (confirm('Batal & kosongkan form transaksi?')) {
        document.getElementById('form-transaksi').reset();
        var items = document.querySelectorAll('#item-list .item');
        for (var i = 1; i < items.length; i++) items[i].remove();
        $('#pelanggan_id, .produk-select').val('').trigger('change.select2');
        document.getElementById('tampil-kembalian').innerText = 'Rp 0';
        hitungTotal();
    }
}
</script>
</body>
</html>