<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_email = '';
$user_query = mysqli_query($conn, "SELECT email FROM users WHERE id = $user_id");
if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
    $user_email = $user_data['email'] ?? '';
}

$query = "
    SELECT o.*, 
           GROUP_CONCAT(p.nama_barang SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    LEFT JOIN products p ON od.product_id = p.id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

$status_config = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'paid' => ['label' => 'Dibayar - Diproses', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'shipped' => ['label' => 'Sedang Dikirim', 'color' => '#6366f1', 'bg' => '#e0e7ff'],
    'delivered' => ['label' => 'Selesai Diterima', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#ef4444', 'bg' => '#fee2e2']
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - 7Cellectronic</title>
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
            gap: 32px;
            list-style: none;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .nav-menu a:hover {
            color: var(--gold-primary);
        }

        .user-info {
            text-align: right;
            padding-left: 24px;
            border-left: 2px solid #e5e7eb;
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 4px;
        }

        .logout-link {
            color: var(--gold-primary);
            text-decoration: none;
            font-weight: 700;
        }

        .page-header {
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--cream) 100%);
            padding: 60px 0 40px;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .orders-list {
            margin-bottom: 60px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 20px;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .order-id span {
            color: var(--gold-primary);
        }

        .order-date {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 24px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-body {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 24px;
            margin-bottom: 20px;
        }

        .order-products h4 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .order-products p {
            color: var(--dark);
            font-weight: 600;
            font-size: 1rem;
        }

        .order-total h4 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .order-total p {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .order-actions {
            display: flex;
            gap: 12px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--gold-primary);
            border: 2px solid var(--gold-primary);
        }

        .btn-outline:hover {
            background: var(--gold-primary);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .empty-state i {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 24px;
        }

        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0 30px;
            text-align: center;
            margin-top: 80px;
        }

        @media (max-width: 768px) {
            .order-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="katalog.php" class="navbar-brand">
                    <i class="fas fa-bolt"></i>
                    7Cellectronic
                </a>
                <ul class="nav-menu">
                    <li><a href="katalog.php"><i class="fas fa-store"></i> Katalog</a></li>
                    <li><a href="keranjang.php"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                </ul>
                <div class="user-info">
                    <div class="user-email">
                        <?php echo htmlspecialchars($user_email); ?>
                    </div>
                    <a href="../auth/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-box me-3"></i>Pesanan Saya</h1>
            <p>Pantau status pesanan Anda dengan mudah dan transparan</p>
        </div>
    </div>

    <div class="container">
        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Belum Ada Pesanan</h3>
                    <p style="color: var(--gray); margin: 12px 0 28px;">Mulai belanja dan temukan smartphone impian Anda</p>
                    <a href="katalog.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order):
                    $status = $status_config[$order['status']] ?? ['label' => $order['status'], 'color' => '#6b7280', 'bg' => '#f3f4f6'];
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order <span>#
                                        <?php echo $order['id']; ?>
                                    </span></div>
                                <div class="order-date">
                                    <i class="far fa-calendar me-2"></i>
                                    <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <div class="status-badge"
                                style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['color']; ?>;">
                                <?php echo $status['label']; ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-products">
                                <h4>Produk</h4>
                                <p>
                                    <?php echo htmlspecialchars($order['product_names'] ?? '-'); ?>
                                </p>
                            </div>
                            <div class="order-total">
                                <h4>Total Pembayaran</h4>
                                <p>Rp
                                    <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                                </p>
                            </div>
                            <div class="order-total">
                                <h4>Metode Pembayaran</h4>
                                <p style="font-size: 1rem; color: var(--dark);">
                                    <?php echo ucfirst($order['payment_method'] ?? '-'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-file-invoice"></i> Lihat Invoice
                            </a>
                            <?php if ($order['status'] == 'pending'): ?>
                                <a href="cancel_order.php?id=<?php echo $order['id']; ?>" class="btn btn-outline"
                                    onclick="return confirm('Batalkan pesanan ini?')">
                                    <i class="fas fa-times"></i> Batalkan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div style="font-size: 1.6rem; font-weight: 800; margin-bottom: 12px; color: var(--gold-primary);">
                <i class="fas fa-bolt me-2"></i>7Cellectronic
            </div>
            <p style="opacity: 0.8;">Premium Smartphone Store</p>
            <p style="margin-top: 16px; opacity: 0.6;">© 2024 - Project UAS PBW</p>
        </div>
    </footer>
</body>

</html>