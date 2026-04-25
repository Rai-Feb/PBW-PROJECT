<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Total pendapatan
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE status != 'cancelled'"))['total'] ?? 0;
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
$total_produk_terjual = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.status != 'cancelled'"))['total'] ?? 0;

// Pendapatan per bulan
$pendapatan_bulanan = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as bulan, SUM(total_harga) as total 
    FROM orders 
    WHERE status != 'cancelled'
    GROUP BY bulan 
    ORDER BY bulan DESC 
    LIMIT 6
");

$bulan_labels = [];
$bulan_data = [];
while ($row = mysqli_fetch_assoc($pendapatan_bulanan)) {
    $bulan_labels[] = date('M Y', strtotime($row['bulan'] . '-01'));
    $bulan_data[] = (int) $row['total'];
}
$bulan_labels = array_reverse($bulan_labels);
$bulan_data = array_reverse($bulan_data);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3 text-center border-bottom border-white border-opacity-25">
                    <h4 class="fw-bold mb-0"><i class="fas fa-mobile-alt me-2"></i>7Cellectronic</h4>
                </div>
                <nav class="nav flex-column mt-3">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a class="nav-link" href="produk.php"><i class="fas fa-box"></i> Kelola Produk</a>
                    <a class="nav-link" href="pesanan.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a>
                    <a class="nav-link active" href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i>
                        Logout</a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="fw-bold mb-4"><i class="fas fa-chart-line me-2"></i>Laporan Penjualan</h2>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <small class="text-muted">Total Pendapatan</small>
                            <div class="stat-value text-success">Rp
                                <?= number_format($total_pendapatan, 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <small class="text-muted">Total Pesanan</small>
                            <div class="stat-value text-primary">
                                <?= $total_pesanan ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <small class="text-muted">Total Produk Terjual</small>
                            <div class="stat-value text-warning">
                                <?= $total_produk_terjual ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h5><i class="fas fa-chart-area me-2"></i>Grafik Pendapatan 6 Bulan Terakhir</h5>
                    <canvas id="chartPendapatan" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        new Chart(document.getElementById('chartPendapatan'), {
            type: 'line',
            data: {
                labels: <?= json_encode($bulan_labels) ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?= json_encode($bulan_data) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>

</body>

</html>