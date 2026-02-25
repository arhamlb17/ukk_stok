<?php
session_start();
require '../koneksi.php'; 

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama          = $_POST['nama_barang'];
    $harga_satuan  = $_POST['harga_satuan'];
    $stok          = $_POST['stok'];

    $stmt = $koneksi->prepare(
        "INSERT INTO barang (nama_barang, harga_satuan, stok) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sdi", $nama, $harga_satuan, $stok);
    $stmt->execute();

    header("Location: data_barang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Data Barang</h5>
            </div>
            <div class="card-body">

                <!-- FORM TAMBAH BARANG -->
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Harga Satuan</label>
                        <input type="number"
                               name="harga_satuan"
                               class="form-control"
                               step="0.01"
                               min="0"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number"
                               name="stok"
                               class="form-control"
                               min="0"
                               required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="data_barang.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</body>

</html>
