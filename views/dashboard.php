<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require '../koneksi.php';

$username = $_SESSION['username'] ?? 'User';


$total_stok = 0;
$result = mysqli_query($koneksi, "SELECT SUM(stok) AS total FROM barang");
if ($row = mysqli_fetch_assoc($result)) {
    $total_stok = (int) $row['total'];
}


$barang_masuk = 0;
$barang_keluar = 0;

$result = mysqli_query($koneksi, "
    SELECT 
        SUM(CASE WHEN jenis_transaksi='masuk' THEN jumlah ELSE 0 END) AS masuk,
        SUM(CASE WHEN jenis_transaksi='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi
");
if ($row = mysqli_fetch_assoc($result)) {
    $barang_masuk = (int) $row['masuk'];
    $barang_keluar = (int) $row['keluar'];
}

$statistik = [];
$bulan_nama = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$year = date('Y');
$result = mysqli_query($koneksi, "
    SELECT 
        MONTH(waktu) AS bulan_num,
        SUM(CASE WHEN jenis_transaksi='masuk' THEN jumlah ELSE 0 END) AS masuk,
        SUM(CASE WHEN jenis_transaksi='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi
    WHERE YEAR(waktu) = $year
    GROUP BY MONTH(waktu)
    ORDER BY MONTH(waktu)
");

$chart_labels = [];
$chart_masuk = [];
$chart_keluar = [];

while ($row = mysqli_fetch_assoc($result)) {
    $chart_labels[] = $bulan_nama[(int)$row['bulan_num']];
    $chart_masuk[] = (int)$row['masuk'];
    $chart_keluar[] = (int)$row['keluar'];
}

// Hitung total untuk grafik donat
$total_transaksi = $barang_masuk + $barang_keluar;
$persen_masuk = $total_transaksi > 0 ? round(($barang_masuk / $total_transaksi) * 100, 1) : 0;
$persen_keluar = $total_transaksi > 0 ? round(($barang_keluar / $total_transaksi) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Gudang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* Sidebar Sederhana */
        .sidebar {
            display: flex;
            flex-direction: column;
            width: 220px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
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
            margin-top: auto;
            padding: 15px 10px;
            border-top: 1px solid #34495e;
        }


        /* Main Content */
        .main-content {
            margin-left: 220px;
            padding: 20px;
        }

        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .page-header h3 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #7f8c8d;
            margin-bottom: 0;
        }

        /* Stat Cards */
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.total {
            border-left-color: #3498db;
        }

        .stat-card.masuk {
            border-left-color: #2ecc71;
        }

        .stat-card.keluar {
            border-left-color: #e74c3c;
        }

        .stat-card h6 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .stat-card h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 5px;
        }

        /* Chart Section */
        .chart-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .chart-section .card-header {
            background-color: transparent;
            border-bottom: 1px solid #eee;
            padding: 0 0 15px 0;
            margin-bottom: 15px;
        }

        .chart-section h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0;
        }

        .chart-container {
            height: 380px;
            position: relative;
        }

        /* Table */
        .data-table {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border-color: #dee2e6;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
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
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-warehouse me-2"></i>Gudang App</h4>
        </div>

        <div class="user-info">
            <p><i class="fas fa-user me-2"></i><?= htmlspecialchars($username); ?></p>
        </div>

        <div class="nav-menu">
            <div class="nav-item">
                <a class="nav-link active" href="dashboard.php">
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

    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <h3>Dashboard Stok Gudang</h3>
            <p>Selamat datang, <?= htmlspecialchars($username); ?> - Sistem Manajemen Stok</p>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card total">
                    <h6>TOTAL STOK</h6>
                    <h2><?= number_format($total_stok); ?></h2>
                    <small class="text-muted">Jumlah barang di gudang</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card masuk">
                    <h6>BARANG MASUK</h6>
                    <h2 class="text-success"><?= number_format($barang_masuk); ?></h2>
                    <small class="text-muted"><?= $persen_masuk; ?>% dari total transaksi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card keluar">
                    <h6>BARANG KELUAR</h6>
                    <h2 class="text-danger"><?= number_format($barang_keluar); ?></h2>
                    <small class="text-muted"><?= $persen_keluar; ?>% dari total transaksi</small>
                </div>
            </div>
        </div>

        <!-- Single Chart Section - Bar Chart -->
        <div class="chart-section">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Grafik Transaksi Bulanan <?= date('Y'); ?></h5>
            </div>
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Table Section -->
        <div class="data-table">
            <div class="card-header mb-3">
                <h5><i class="fas fa-table me-2"></i>Data Per Bulan (<?= date('Y'); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="text-center">
                            <th>Bulan</th>
                            <th>Barang Masuk</th>
                            <th>Barang Keluar</th>
                            <th>Selisih</th>
                            <th>Persentase Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_masuk = 0;
                        $total_keluar = 0;
                        foreach ($chart_labels as $i => $bulan):
                            $masuk = $chart_masuk[$i] ?? 0;
                            $keluar = $chart_keluar[$i] ?? 0;
                            $selisih = $masuk - $keluar;
                            $total_bulan = $masuk + $keluar;
                            $persen_masuk_bulan = $total_bulan > 0 ? round(($masuk / $total_bulan) * 100, 1) : 0;
                            $total_masuk += $masuk;
                            $total_keluar += $keluar;
                        ?>
                            <tr>
                                <td class="text-center"><strong><?= $bulan; ?></strong></td>
                                <td class="text-success fw-bold text-center"><?= number_format($masuk); ?></td>
                                <td class="text-danger fw-bold text-center"><?= number_format($keluar); ?></td>
                                <td class="<?= $selisih >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold text-center">
                                    <?= ($selisih >= 0 ? '+' : '') . number_format($selisih); ?>
                                </td>
                                <td style="width: 200px;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2" style="min-width: 45px;"><?= $persen_masuk_bulan; ?>%</div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-success" style="width: <?= $persen_masuk_bulan; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Total Row -->
                        <tr class="table-active fw-bold">
                            <td class="text-center">TOTAL</td>
                            <td class="text-center"><?= number_format($total_masuk); ?></td>
                            <td class="text-center"><?= number_format($total_keluar); ?></td>
                            <td class="<?= ($total_masuk - $total_keluar) >= 0 ? 'text-success' : 'text-danger'; ?> text-center">
                                <?= (($total_masuk - $total_keluar) >= 0 ? '+' : '') . number_format($total_masuk - $total_keluar); ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $total_persen = $total_masuk + $total_keluar > 0 ?
                                    round(($total_masuk / ($total_masuk + $total_keluar)) * 100, 1) : 0;
                                echo $total_persen . '%';
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Single Bar Chart for Monthly Data - Clean and Professional
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?= json_encode($chart_masuk); ?>,
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: '#27ae60',
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.65,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Barang Keluar',
                        data: <?= json_encode($chart_keluar); ?>,
                        backgroundColor: 'rgba(231, 76, 60, 0.8)',
                        borderColor: '#c0392b',
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.65,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 12,
                            padding: 15,
                            font: {
                                size: 13,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw;
                                return label + ': ' + value.toLocaleString() + ' item';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Barang',
                            font: {
                                weight: 'bold',
                                size: 13
                            }
                        },
                        grid: {
                            color: '#e9ecef',
                            drawBorder: true
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Bulan',
                            font: {
                                weight: 'bold',
                                size: 13
                            }
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>