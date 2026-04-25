<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$query = "
    SELECT o.*, 
           GROUP_CONCAT(od.product_id) as product_ids,
           GROUP_CONCAT(p.nama_barang SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    LEFT JOIN products p ON od.product_id = p.id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.id DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

$status_labels = array(
    'pending' => 'Menunggu Pembayaran',
    'paid' => 'Dibayar - Diproses',
    'shipped' => 'Sedang Dikirim',
    'delivered' => 'Selesai Diterima',
    'cancelled' => 'Dibatalkan'
);

$status_colors = array(
    'pending' => 'warning',
    'paid' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger'
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .order-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="katalog.php">7Cellectronic</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="katalog.php">Katalog</a>
            <a class="nav-link" href="keranjang.php">Keranjang</a>
            <a class="nav-link active" href="pesanan.php">Pesanan</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-box"></i> Pesanan Saya</h2>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <p class="text-muted">Belum ada pesanan</p>
            <a href="katalog.php" class="btn btn-primary">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php 
            $status = $order['status'];
            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
            $color = isset($status_colors[$status]) ? $status_colors[$status] : 'secondary';
            ?>
            <div class="order-card">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">No. Pesanan</small>
                        <h6>#<?php echo $order['id']; ?></h6>
                        <?php if (isset($order['created_at'])): ?>
                            <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Produk</small>
                        <p class="mb-0"><?php echo htmlspecialchars($order['product_names'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Total</small>
                        <h6 class="mb-0">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></h6>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="badge bg-<?php echo $color; ?> status-badge"><?php echo $label; ?></span>
                        <div class="mt-2">
                            <a href="invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">Lihat Invoice</a>
                        </div>
                    </div>
                </div>
                
                <?php if ($status == 'pending'): ?>
                <div class="mt-3 pt-3 border-top">
                    <form method="POST" action="cancel_order.php" style="display: inline;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Batalkan pesanan?')">Batalkan Pesanan</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>