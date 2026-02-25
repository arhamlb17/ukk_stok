<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require '../koneksi.php';

$username = $_SESSION['username'] ?? 'User';

/* Ambil data transaksi */
$query = "
    SELECT 
        t.id,
        t.jenis_transaksi,
        t.jumlah,
        t.total_harga,
        t.waktu,
        t.metode_bayar,
        b.nama_barang
    FROM transaksi t
    JOIN barang b ON t.id_barang = b.id
    ORDER BY t.waktu DESC, t.id DESC
";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Transaksi - Gudang</title>
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
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-warehouse me-2"></i>Gudang App</h4>
        </div>

        <div class="user-info">
            <p><i class="fas fa-user me-2"></i><?= htmlspecialchars($username) ?></p>
        </div>

        <div class="nav-menu">
            <div class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link" href="data_barang.php">
                    <i class="fas fa-boxes"></i> Data Barang
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link" href="transaksi.php">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link active" href="riwayat_transaksi.php">
                    <i class="fas fa-history"></i> Riwayat Transaksi
                </a>
            </div>
        </div>

        <div class="logout-section">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h3 class="mb-4">Riwayat Transaksi</h3>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Total Harga</th>
                        <th>Metode Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $isMasuk = $row['jenis_transaksi'] === 'masuk';
                            $badgeClass = $isMasuk ? 'success' : 'danger';
                            $labelJenis = $isMasuk ? 'Barang Masuk' : 'Barang Keluar';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="text-center"><?= date('d-m-Y', strtotime($row['waktu'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badgeClass ?>"><?= $labelJenis ?></span>
                                </td>
                                <td class="text-center"><?= $row['jumlah'] ?></td>
                                <td class="text-end">
                                    <?= $row['total_harga'] > 0
                                        ? 'Rp ' . number_format($row['total_harga'], 0, ',', '.')
                                        : '-' ?>
                                </td>
                                <td class="text-center text-uppercase"><?= htmlspecialchars($row['metode_bayar']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Belum ada transaksi
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>