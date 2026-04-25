<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'] ?? 0;
$stok_menipis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stok <= 5"))['total'] ?? 0;
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE status IN ('paid', 'delivered')"))['total'] ?? 0;

$pesanan_baru = mysqli_query($conn, "SELECT o.*, u.nama as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #f4e5c2;
            --gold-dark: #aa8c2c;
            --cream: #faf8f3;
            --dark: #1a1a1a;
            --gray: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--cream);
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            gap: 24px;
            list-style: none;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--gold-light);
            color: var(--gold-dark);
        }

        .main-content {
            padding: 30px 0;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.2);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon.gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 32px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }

        .card-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }

        .btn-sm {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            background: var(--cream);
            font-weight: 700;
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--gray);
        }

        tr:hover td {
            background: var(--cream);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 16px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">
                    <i class="fas fa-bolt"></i>
                    7Cellectronic Admin
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="produk.php"><i class="fas fa-box"></i> Produk</a></li>
                    <li><a href="pesanan.php"><i class="fas fa-shopping-cart"></i> Pesanan</a></li>
                    <li><a href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
                    <li><a href="chat.php"><i class="fas fa-comments"></i> Chat Customer</a></li>
                    <li><a href="../customer/katalog.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat
                            Toko</a></li>
                    <li><a href="../auth/logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i>
                            Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p style="color: var(--gray);">Selamat datang kembali! Berikut ringkasan toko Anda.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon gold">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $total_produk; ?>
                        </h3>
                        <p>Total Produk</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $stok_menipis; ?>
                        </h3>
                        <p>Stok Menipis</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $total_pesanan; ?>
                        </h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Rp
                            <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                        </h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-clock me-2"></i>Pesanan Terbaru</h3>
                    <a href="pesanan.php" class="btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($pesanan_baru)): ?>
                                <tr>
                                    <td><strong>#
                                            <?php echo $order['id']; ?>
                                        </strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td>Rp
                                        <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        setInterval(function () {
            location.reload();
        }, 30000);
    </script>
</body>

</html>