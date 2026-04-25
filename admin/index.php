<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Statistik
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'] ?? 0;
$stok_menipis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stok <= 5"))['total'] ?? 0;
$pesanan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()"))['total'] ?? 0;
$pendapatan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"))['total'] ?? 0;

// Alert stok rendah
$stok_rendah = mysqli_query($conn, "SELECT id, nama_barang, stok FROM products WHERE stok <= 5 ORDER BY stok ASC LIMIT 5");

// Pesanan terbaru
$pesanan_terbaru = mysqli_query($conn, "
    SELECT o.id, o.total_harga, o.status, o.created_at, u.nama as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 8
");

// Produk terlaris
$produk_terlaris = mysqli_query($conn, "
    SELECT p.nama_barang, SUM(od.jumlah) as total_terjual, p.stok
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    GROUP BY od.product_id
    ORDER BY total_terjual DESC
    LIMIT 5
");

// Data Chart - 9 Brand HP (Alphabetical)
$brands = ['Infinix', 'iPhone', 'iQOO', 'Oppo', 'Realme', 'Samsung', 'Vivo', 'Xiaomi', 'Lainnya'];
$brand_data = [];

foreach ($brands as $brand) {
    if ($brand == 'Lainnya') {
        $query = "SELECT SUM(stok) as total FROM products WHERE kategori NOT IN ('Infinix', 'iPhone', 'iQOO', 'Oppo', 'Realme', 'Samsung', 'Vivo', 'Xiaomi')";
    } else {
        $query = "SELECT SUM(stok) as total FROM products WHERE kategori = '$brand'";
    }
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $brand_data[] = (int) ($row['total'] ?? 0);
}

// Status pesanan untuk pie chart
$status_chart = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM orders GROUP BY status");
$status_labels = [];
$status_data = [];
while ($row = mysqli_fetch_assoc($status_chart)) {
    $status_labels[] = ucfirst($row['status']);
    $status_data[] = (int) $row['total'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - 7Cellectronic</title>
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
            transition: 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            margin-bottom: 15px;
        }

        .bg-blue {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .bg-green {
            background: linear-gradient(135deg, #56ab2f, #a8e063);
        }

        .bg-orange {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .bg-purple {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            color: #718096;
            font-size: 0.85rem;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .content-card h5 {
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-stock {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .live-clock {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3 text-center border-bottom border-white border-opacity-25">
                    <h4 class="fw-bold mb-0"><i class="fas fa-mobile-alt me-2"></i>7Cellectronic</h4>
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a class="nav-link" href="produk.php"><i class="fas fa-box"></i> Kelola Produk</a>
                    <a class="nav-link" href="pesanan.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a>
                    <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i>
                        Logout</a>
                </nav>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Dashboard Admin</h2>
                        <p class="text-muted mb-0">Pantau performa toko HP secara real-time</p>
                    </div>
                    <div class="live-clock" id="liveClock">
                        <i class="far fa-clock me-2"></i><span id="clockTime">
                            <?= date('H:i:s') ?>
                        </span>
                    </div>
                </div>

                <!-- STATISTIK -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-blue"><i class="fas fa-mobile-alt"></i></div>
                            <div class="stat-value">
                                <?= $total_produk ?>
                            </div>
                            <div class="stat-label">Total Produk HP</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-orange"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-value">
                                <?= $stok_menipis ?>
                            </div>
                            <div class="stat-label">Stok Menipis</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-green"><i class="fas fa-shopping-bag"></i></div>
                            <div class="stat-value">
                                <?= $pesanan_hari_ini ?>
                            </div>
                            <div class="stat-label">Pesanan Hari Ini</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-purple"><i class="fas fa-wallet"></i></div>
                            <div class="stat-value">Rp
                                <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?>
                            </div>
                            <div class="stat-label">Pendapatan Hari Ini</div>
                        </div>
                    </div>
                </div>

                <!-- CHART -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="content-card">
                            <h5><i class="fas fa-chart-bar me-2 text-primary"></i>Stok Produk per Brand HP</h5>
                            <canvas id="chartBrand" height="120"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="content-card">
                            <h5><i class="fas fa-chart-pie me-2 text-primary"></i>Status Pesanan</h5>
                            <canvas id="chartStatus"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ALERT STOK -->
                <?php if ($stok_menipis > 0): ?>
                    <div class="content-card mb-4">
                        <h5><i class="fas fa-bell text-warning me-2"></i>Peringatan: Stok Produk Menipis</h5>
                        <?php while ($item = mysqli_fetch_assoc($stok_rendah)): ?>
                            <div class="alert-stock">
                                <div>
                                    <strong>
                                        <?= htmlspecialchars($item['nama_barang']) ?>
                                    </strong>
                                    <br><small class="text-muted">Sisa stok: <span class="text-danger fw-bold">
                                            <?= $item['stok'] ?>
                                        </span> unit</small>
                                </div>
                                <a href="produk.php?action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-plus me-1"></i> Tambah Stok
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <!-- TABEL -->
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="content-card">
                            <h5><i class="fas fa-clock me-2 text-primary"></i>Pesanan Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID Order</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($pesanan_terbaru):
                                            while ($order = mysqli_fetch_assoc($pesanan_terbaru)): ?>
                                                <tr>
                                                    <td>#
                                                        <?= $order['id'] ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?>
                                                    </td>
                                                    <td>Rp
                                                        <?= number_format($order['total_harga'], 0, ',', '.') ?>
                                                    </td>
                                                    <td><span class="badge-status status-<?= $order['status'] ?>">
                                                            <?= ucfirst($order['status']) ?>
                                                        </span></td>
                                                    <td><a href="pesanan.php" class="btn btn-sm btn-outline-primary"><i
                                                                class="fas fa-eye me-1"></i>Detail</a></td>
                                                </tr>
                                            <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Belum ada pesanan</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="content-card">
                            <h5><i class="fas fa-trophy me-2 text-warning"></i>Produk Terlaris</h5>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Terjual</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($produk_terlaris):
                                        while ($p = mysqli_fetch_assoc($produk_terlaris)): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($p['nama_barang']) ?>
                                                </td>
                                                <td class="fw-bold">
                                                    <?= $p['total_terjual'] ?>
                                                </td>
                                                <td class="<?= $p['stok'] <= 5 ? 'text-danger' : 'text-success' ?>">
                                                    <?= $p['stok'] ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">Belum ada data</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clockTime').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Chart Brand HP
        new Chart(document.getElementById('chartBrand'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($brands) ?>,
                datasets: [{ label: 'Stok', data: <?= json_encode($brand_data) ?>, backgroundColor: '#667eea', borderRadius: 8 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Chart Status
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{ data: <?= json_encode($status_data) ?>, backgroundColor: ['#f59e0b', '#3b82f6', '#6366f1', '#10b981', '#ef4444'], borderWidth: 0 }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } } }
        });

        // Auto refresh setiap 30 detik
        setInterval(() => location.reload(), 30000);
    </script>

</body>

</html>