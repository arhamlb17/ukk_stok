<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: data_barang.php");
    exit;
}

$id = intval($_GET['id']);

// Ambil data barang berdasarkan ID
$query = "SELECT * FROM barang WHERE id = $id";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: data_barang.php");
    exit;
}

$barang = mysqli_fetch_assoc($result);

// Proses update
if (isset($_POST['update'])) {
    $nama          = $_POST['nama_barang'];
    $harga_satuan  = $_POST['harga_satuan'];
    $stok          = $_POST['stok'];

    $stmt = $koneksi->prepare(
        "UPDATE barang 
         SET nama_barang = ?, harga_satuan = ?, stok = ?
         WHERE id = ?"
    );
    $stmt->bind_param("sdii", $nama, $harga_satuan, $stok, $id);
    $stmt->execute();

    header("Location: data_barang.php?status=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eef2f6;
            padding: 25px 30px;
        }
        
        .card-header h5 {
            color: #1a2639;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: -0.02em;
        }
        
        .card-body {
            padding: 30px;
            background: white;
        }
        
        .form-label {
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #ffb347;
            box-shadow: 0 0 0 4px rgba(255, 180, 71, 0.15);
            outline: none;
        }
        
        .form-control:hover {
            border-color: #cbd5e1;
        }
        
        .input-group {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            color: #64748b;
            font-weight: 500;
            padding: 0 16px;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-color: #e2e8f0;
            box-shadow: none;
        }
        
        .input-group:focus-within {
            border-color: #ffb347;
            box-shadow: 0 0 0 4px rgba(255, 180, 71, 0.15);
            border-radius: 12px;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: #ffb347;
        }
        
        .input-group:focus-within .form-control {
            border-color: #ffb347;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-warning {
            background: #ffb347;
            color: white;
        }
        
        .btn-warning:hover {
            background: #ffa01c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 180, 71, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .info-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #eef2f6;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffb347;
            font-size: 1.2rem;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.02);
        }
        
        .info-text {
            flex: 1;
        }
        
        .info-text .label {
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-text .value {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .divider {
            border-top: 1px solid #eef2f6;
            margin: 20px 0;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        .badge-id {
            background: #ffb347;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <div class="card">
                    <div class="card-header">
                        <h5>
                            Edit Barang
                            <span class="badge-id">ID: #<?= $id ?></span>
                        </h5>
                    </div>
                    
                    <div class="card-body">
                        
                        <!-- Info Card -->
                        <div class="info-card">
                            <div class="info-item">
                                <div class="info-icon">
                                    <span>📦</span>
                                </div>
                                <div class="info-text">
                                    <div class="label">Item yang diedit</div>
                                    <div class="value"><?= htmlspecialchars($barang['nama_barang']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <!-- Nama Barang -->
                            <div class="mb-4">
                                <label class="form-label">Nama Barang</label>
                                <input type="text"
                                       name="nama_barang"
                                       class="form-control"
                                       value="<?= htmlspecialchars($barang['nama_barang']); ?>"
                                       placeholder="Masukkan nama barang"
                                       required>
                            </div>

                            <!-- Harga Satuan -->
                            <div class="mb-4">
                                <label class="form-label">Harga Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           name="harga_satuan"
                                           class="form-control"
                                           step="0.01"
                                           min="0"
                                           value="<?= $barang['harga_satuan']; ?>"
                                           placeholder="0"
                                           required>
                                </div>
                            </div>

                            <!-- Stok -->
                            <div class="mb-4">
                                <label class="form-label">Stok</label>
                                <input type="number"
                                       name="stok"
                                       class="form-control"
                                       min="0"
                                       value="<?= $barang['stok']; ?>"
                                       placeholder="Jumlah stok"
                                       required>
                            </div>

                            <div class="divider"></div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="data_barang.php" class="btn btn-secondary flex-fill">
                                    ← Kembali
                                </a>
                                <button type="submit" name="update" class="btn btn-warning flex-fill">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                        
                        <div class="footer-text">
                            Pastikan data yang dimasukkan sudah benar
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>

</body>

</html>