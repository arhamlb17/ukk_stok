<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
require '../koneksi.php';

$keyword = mysqli_real_escape_string($koneksi, $_GET['search'] ?? '');

$query = "SELECT * FROM barang 
          WHERE nama_barang LIKE '%$keyword%' 
          ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);

$total_barang = mysqli_num_rows($result);

// Total stok
$q_stok = mysqli_query($koneksi, "SELECT SUM(stok) AS total_stok FROM barang");
$total_stok = mysqli_fetch_assoc($q_stok)['total_stok'] ?? 0;

// Total nilai
$q_nilai = mysqli_query($koneksi, "SELECT SUM(stok * harga_satuan) AS total_nilai FROM barang");
$total_nilai = mysqli_fetch_assoc($q_nilai)['total_nilai'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }

        .sidebar-header h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0;
            color: white;
        }

        .user-info {
            padding: 15px 20px;
            background-color: #34495e;
            border-bottom: 1px solid #2c3e50;
        }

        .user-info p {
            margin-bottom: 0;
            color: #ecf0f1;
            font-size: 0.9rem;
        }

        .nav-menu {
            padding: 15px 10px;
            flex-grow: 1;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            color: #bdc3c7;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: #34495e;
            color: white;
        }

        .nav-link.active {
            background-color: #3498db;
            color: white;
        }

        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }

        .logout-section {
            padding: 15px 10px;
            border-top: 1px solid #34495e;
        }

        /* Main Content */
        .main-content {
            margin-left: 220px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>

</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-warehouse me-2"></i>Gudang App</h4>
        </div>

        <div class="user-info">
            <p><i class="fas fa-user me-2"></i><?= htmlspecialchars($username); ?></p>
        </div>

        <div class="nav-menu">
            <div class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link active" href="data_barang.php">
                    <i class="fas fa-boxes"></i> Data Barang
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link" href="transaksi.php">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link" href="riwayat_transaksi.php">
                    <i class="fas fa-history"></i> Riwayat
                </a>
            </div>
        </div>

        <div class="logout-section">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>



    <!-- MAIN -->
    <div class="main-content">

        <h3>Data Barang</h3>
        <p class="text-muted">Manajemen data barang dan stok gudang</p>

        <!-- STAT -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small>Total Jenis Barang</small>
                        <h3><?= $total_barang; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small>Total Stok</small>
                        <h3><?= number_format($total_stok); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <small>Total Nilai Barang</small>
                        <h3>Rp <?= number_format($total_nilai, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEARCH -->
        <div class="d-flex justify-content-between mb-3">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2"
                    placeholder="Cari barang..."
                    value="<?= htmlspecialchars($keyword); ?>">
                <button class="btn btn-primary">Cari</button>
                <?php if ($keyword): ?>
                    <a href="data_barang.php" class="btn btn-secondary ms-2">Reset</a>
                <?php endif; ?>
            </form>

        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center">Stok</th>
                            <th class="text-end">Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1;
                            while ($b = mysqli_fetch_assoc($result)): ?>
                                <?php $total = $b['stok'] * $b['harga_satuan']; ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($b['nama_barang']); ?></td>
                                    <td class="text-end">Rp <?= number_format($b['harga_satuan'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <?= $b['stok']; ?>
                                        <?php if ($b['stok'] == 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php elseif ($b['stok'] < 10): ?>
                                            <span class="badge bg-warning text-dark">Hampir</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold">
                                        Rp <?= number_format($total, 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <a href="edit_barang.php?id=<?= $b['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="hapus_barang.php?id=<?= $b['id']; ?>"
                                            onclick="return confirm('Yakin hapus?')"
                                            class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Data tidak ditemukan
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <small class="text-muted">
                    Menampilkan <?= $total_barang; ?> data barang
                </small>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>