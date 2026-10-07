<?php
$koneksi = mysqli_connect("localhost", "root", "", "praktek_kasir");
if (mysqli_connect_errno()) {
    echo "Koneksi gagal: " . mysqli_connect_error();
    exit();
}
?>