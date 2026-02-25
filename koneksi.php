<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ukk_stok_barang";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if ($koneksi === false) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
