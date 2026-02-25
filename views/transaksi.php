<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require '../koneksi.php';
$username = $_SESSION['username'] ?? 'User';

$errors = [];
$success = '';

/* Ambil daftar barang */
$barangList = [];
$resultBarang = mysqli_query($koneksi, "SELECT id, nama_barang, harga_satuan, stok FROM barang ORDER BY nama_barang ASC");
while ($row = mysqli_fetch_assoc($resultBarang)) {
    $barangList[] = $row;
}

/* HANDLE TAMBAH BARANG CEPAT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_barang'])) {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $harga_satuan = (int) ($_POST['harga_satuan'] ?? 0);
    $stok_awal = (int) ($_POST['stok_awal'] ?? 0);

    if (empty($nama_barang)) $errors[] = "Nama barang tidak boleh kosong";
    if ($harga_satuan <= 0) $errors[] = "Harga satuan harus lebih dari 0";
    if ($stok_awal < 0) $errors[] = "Stok awal tidak boleh negatif";

    if (empty($errors)) {
        $stmt = $koneksi->prepare("INSERT INTO barang (nama_barang, harga_satuan, stok) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $nama_barang, $harga_satuan, $stok_awal);
        if ($stmt->execute()) {
            $_SESSION['success_barang'] = "Barang berhasil ditambahkan!";
            header("Location: transaksi.php");
            exit;
        } else {
            $errors[] = "Gagal menambahkan barang";
        }
        $stmt->close();
    }
}

/* HANDLE TRANSAKSI */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaksi'])) {

    $jenis_transaksi = $_POST['jenis_transaksi'] ?? '';
    $id_barang       = $_POST['id_barang'] ?? '';
    $jumlah          = (int) ($_POST['jumlah'] ?? 0);
    $metode_bayar    = $_POST['metode_bayar'] ?? '';

    if (!in_array($jenis_transaksi, ['masuk', 'keluar'])) $errors[] = "Jenis transaksi wajib dipilih.";
    if (!$id_barang) $errors[] = "Pilih barang terlebih dahulu.";
    if ($jumlah < 1) $errors[] = "Jumlah minimal 1.";
    if (!in_array($metode_bayar, ['cash', 'debit'])) $errors[] = "Metode bayar tidak valid.";

    $harga_satuan = 0;
    $stok_sekarang = 0;

    foreach ($barangList as $b) {
        if ($b['id'] == $id_barang) {
            $harga_satuan  = $b['harga_satuan'];
            $stok_sekarang = $b['stok'];
            break;
        }
    }

    if ($harga_satuan <= 0) $errors[] = "Harga barang tidak valid.";
    if ($jenis_transaksi === 'keluar' && $jumlah > $stok_sekarang)
        $errors[] = "Stok tidak mencukupi. Stok tersedia: $stok_sekarang";

    $total_harga = ($jenis_transaksi === 'keluar') ? $harga_satuan * $jumlah : 0;

    if (empty($errors)) {
        $stmt = $koneksi->prepare(
            "INSERT INTO transaksi (id_barang, jenis_transaksi, jumlah, total_harga, waktu, metode_bayar) 
             VALUES (?, ?, ?, ?, NOW(), ?)"
        );
        $stmt->bind_param("isiis", $id_barang, $jenis_transaksi, $jumlah, $total_harga, $metode_bayar);
        if ($stmt->execute()) {
            $stok_baru = ($jenis_transaksi === 'masuk') ? $stok_sekarang + $jumlah : $stok_sekarang - $jumlah;
            $stmtUpdate = $koneksi->prepare("UPDATE barang SET stok = ? WHERE id = ?");
            $stmtUpdate->bind_param("ii", $stok_baru, $id_barang);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $success = "Transaksi berhasil disimpan.";
        } else {
            $errors[] = "Gagal menyimpan transaksi.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi - Gudang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f5f5f5; font-family: 'Segoe UI', Arial, sans-serif; }
        .sidebar { width: 220px; background-color: #2c3e50; color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #34495e; }
        .sidebar-header h4 { font-size: 1.3rem; margin-bottom: 0; color: white; }
        .user-info { padding: 15px 20px; background-color: #34495e; border-bottom: 1px solid #2c3e50; }
        .user-info p { margin-bottom: 0; color: #ecf0f1; font-size: 0.9rem; }
        .nav-menu { padding: 15px 10px; flex-grow: 1; }
        .nav-item { margin-bottom: 5px; }
        .nav-link { color: #bdc3c7; padding: 10px 15px; border-radius: 5px; text-decoration: none; display: flex; align-items: center; transition: all 0.2s; }
        .nav-link:hover { background-color: #34495e; color: white; }
        .nav-link.active { background-color: #3498db; color: white; }
        .nav-link i { width: 25px; text-align: center; margin-right: 10px; }
        .logout-section { padding: 15px 10px; border-top: 1px solid #34495e; }
        .main-content { margin-left: 220px; padding: 20px; }
        .card { margin-bottom: 20px; }
        .card-header { background-color: #f8f9fa; font-weight: bold; }
        #total_harga { background: #f8f9fa; font-weight: bold; }
        @media (max-width:768px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; } }
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
        <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></div>
        <div class="nav-item"><a class="nav-link" href="data_barang.php"><i class="fas fa-boxes"></i> Data Barang</a></div>
        <div class="nav-item"><a class="nav-link active" href="transaksi.php"><i class="fas fa-exchange-alt"></i> Transaksi</a></div>
        <div class="nav-item"><a class="nav-link" href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat</a></div>
    </div>
    <div class="logout-section">
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <h3 class="mb-4">Transaksi</h3>

    <!-- NOTIFIKASI TAMBAH BARANG -->
    <?php if (!empty($_SESSION['success_barang'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success_barang']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_barang']); ?>
    <?php endif; ?>

    <!-- NOTIFIKASI TRANSAKSI -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH BARANG -->
    <div class="card mb-4">
        <div class="card-header">Tambah Barang Baru</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="tambah_barang" value="1">
                <div class="col-md-4">
                    <label class="form-label">Nama Barang</label>
                    <input type="text" name="nama_barang" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Harga Satuan</label>
                    <input type="number" name="harga_satuan" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stok Awal</label>
                    <input type="number" name="stok_awal" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-success w-100">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FORM TRANSAKSI -->
    <div class="card">
        <div class="card-header">Transaksi Barang</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="submit_transaksi" value="1">
                <div class="col-md-6">
                    <label class="form-label">Jenis Transaksi</label>
                    <select name="jenis_transaksi" id="jenis_transaksi" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="masuk">Barang Masuk</option>
                        <option value="keluar">Barang Keluar</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Barang</label>
                    <select name="id_barang" id="id_barang" class="form-select" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barangList as $b): ?>
                            <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_satuan'] ?>" data-stok="<?= $b['stok'] ?>">
                                <?= htmlspecialchars($b['nama_barang']) ?> (Stok: <?= $b['stok'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah" id="jumlah" class="form-control" value="1" min="1" required>
                    <small class="text-muted">Stok tersedia: <span id="stok-info">-</span></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metode Bayar</label>
                    <select name="metode_bayar" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="cash">Cash</option>
                        <option value="debit">Debit</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total Harga</label>
                    <input type="text" id="total_harga" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const jenis = document.getElementById('jenis_transaksi');
const barang = document.getElementById('id_barang');
const jumlah = document.getElementById('jumlah');
const total = document.getElementById('total_harga');
const stokInfo = document.getElementById('stok-info');

function updateInfo() {
    const selectedBarang = barang.options[barang.selectedIndex];
    if (selectedBarang && selectedBarang.value) {
        const stok = selectedBarang.dataset.stok || 0;
        stokInfo.textContent = stok;
        stokInfo.style.color = stok < 10 ? 'red' : 'greeen';
    } else {
        stokInfo.textContent = '-';
        stokInfo.style.color = '';
    }

    if (jenis.value !== 'keluar') {
        total.value = 'Rp 0';
        return;
    }
    const harga = selectedBarang?.dataset.harga || 0;
    const jml = jumlah.value || 0;
    total.value = 'Rp ' + (harga * jml).toLocaleString('id-ID');
}

jenis.addEventListener('change', updateInfo);
barang.addEventListener('change', updateInfo);
jumlah.addEventListener('input', updateInfo);

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => a.style.display = 'none');
}, 5000);
</script>
</body>
</html>
