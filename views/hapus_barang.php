<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require '../koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: data_barang.php");
    exit;
}

$id = intval($_GET['id']);

// Hapus data barang
$query = "DELETE FROM barang WHERE id = $id";
$result = mysqli_query($koneksi, $query);

if ($result) {
    header("Location: data_barang.php?status=deleted");
} else {
    echo "Gagal menghapus data!";
}
