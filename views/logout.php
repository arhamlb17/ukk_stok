<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}


if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">

    <div class="card shadow" style="width: 400px;">
        <div class="card-body text-center">
            <h5 class="mb-3">Konfirmasi Logout</h5>
            <p>Apakah Anda yakin ingin keluar?</p>

            <form method="POST" class="d-flex justify-content-center gap-3">
                <a href="data_barang.php" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" name="logout" class="btn btn-danger">
                    Ya, Logout
                </button>
            </form>
        </div>
    </div>

</body>
</html>
