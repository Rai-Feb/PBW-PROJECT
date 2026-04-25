<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
$email_msg = $_GET['msg'] ?? '';

// Ambil data order
$order_query = mysqli_query($conn, "
    SELECT o.*, u.nama as customer_name, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id AND o.user_id = " . (int)$_SESSION['user_id']
);
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: pesanan.php');
    exit;
}

// Ambil detail produk
$items_query = mysqli_query($conn, "
    SELECT od.*, p.nama_barang, p.gambar 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    WHERE od.order_id = $order_id
");
$items = [];
while($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

$status_config = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'paid' => ['label' => 'Dibayar - Diproses', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'shipped' => ['label' => 'Sedang Dikirim', 'color' => '#6366f1', 'bg' => '#e0e7ff'],
    'delivered' => ['label' => 'Selesai Diterima', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#ef4444', 'bg' => '#fee2e2']
];

$status = $status_config[$order['status']] ?? ['label' => $order['status'], 'color' => '#6b7280', 'bg' => '#f3f4f6'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--cream); padding: 40px 20px; }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .invoice-header h1 { font-size: 2rem; margin-bottom: 8px; }
        .invoice-header p { opacity: 0.9; }
        
        .invoice-body { padding: 40px; }
        
        .status-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            margin: 20px 0;
            background: <?php echo $status['bg']; ?>;
            color: <?php echo $status['color']; ?>;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin: 30px 0;
            padding: 24px;
            background: var(--cream);
            border-radius: 16px;
        }
        
        .info-item h4 { font-size: 0.9rem; color: var(--gray); margin-bottom: 8px; font-weight: 600; }
        .info-item p { font-size: 1rem; color: var(--dark); font-weight: 600; }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        .products-table th {
            text-align: left;
            padding: 14px;
            background: var(--cream);
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid var(--gold-primary);
        }
        
        .products-table td {
            padding: 16px 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .product-info { display: flex; align-items: center; gap: 12px; }
        .product-info img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        
        .summary-section {
            background: var(--cream);
            padding: 24px;
            border-radius: 16px;
            margin-top: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: var(--gray);
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 2px solid var(--gold-primary);
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }
        
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(212,175,55,0.4); }
        
        .btn-outline {
            background: white;
            color: var(--gold-primary);
            border: 2px solid var(--gold-primary);
        }
        
        .btn-outline:hover { background: var(--gold-primary); color: white; }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; }
            .action-buttons { display: none; }
        }
        
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .products-table th, .products-table td { padding: 10px; font-size: 0.9rem; }
            .product-info img { width: 40px; height: 40px; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <h1><i class="fas fa-bolt me-2"></i>7Cellectronic</h1>
            <p>Invoice Pesanan #<?php echo $order_id; ?></p>
            <p style="margin-top: 8px; font-size: 0.9rem;"><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
        </div>
        
        <div class="invoice-body">
            <?php if(!empty($email_msg)): ?>
                <div class="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($email_msg); ?>
                </div>
            <?php endif; ?>
            
            <!-- Status -->
            <div style="text-align: center;">
                <div class="status-badge">
                    <i class="fas fa-info-circle me-2"></i> <?php echo $status['label']; ?>
                </div>
            </div>
            
            <!-- Info Pelanggan & Pengiriman -->
            <div class="info-grid">
                <div class="info-item">
                    <h4><i class="fas fa-user me-2"></i>Nama Penerima</h4>
                    <p><?php echo htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></p>
                    <p style="font-size: 0.9rem; color: var(--gray);"><?php echo htmlspecialchars($order['email'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="fas fa-map-marker-alt me-2"></i>Alamat Pengiriman</h4>
                    <p><?php echo htmlspecialchars($order['alamat']); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="fas fa-wallet me-2"></i>Metode Pembayaran</h4>
                    <p><?php echo ucfirst($order['payment_method']); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="fas fa-calendar me-2"></i>Tanggal Pesan</h4>
                    <p><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                </div>
            </div>
            
            <!-- Tabel Produk -->
            <h3 style="margin: 30px 0 16px; font-size: 1.2rem; color: var(--dark);">Detail Pesanan</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <?php if(!empty($item['gambar'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="Produk">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['varian'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['jumlah']; ?></td>
                        <td style="font-weight: 700; color: var(--gold-dark);">Rp <?php echo number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Ringkasan Pembayaran -->
            <div class="summary-section">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span style="color: #10b981;">Gratis</span>
                </div>
                <div class="summary-total">
                    <span>Total Pembayaran</span>
                    <span style="color: var(--gold-dark);">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <!-- Catatan -->
            <?php if(!empty($order['catatan'])): ?>
            <div style="margin-top: 24px; padding: 16px; background: #fef3c7; border-radius: 12px; border-left: 4px solid var(--gold-primary);">
                <h4 style="font-size: 0.9rem; color: var(--dark); margin-bottom: 8px;"><i class="fas fa-sticky-note me-2"></i>Catatan:</h4>
                <p style="color: var(--gray);"><?php echo htmlspecialchars($order['catatan']); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Tombol Aksi -->
            <div class="action-buttons">
                <a href="pesanan.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Cetak Invoice
                </button>
            </div>
            
            <!-- Footer -->
            <div style="text-align: center; margin-top: 40px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: var(--gray); font-size: 0.9rem;">
                <p><strong>7Cellectronic</strong> - Premium Smartphone Store</p>
                <p style="margin-top: 8px;">Terima kasih telah berbelanja dengan kami!</p>
                <p style="margin-top: 8px; font-size: 0.85rem;">Jika ada pertanyaan, hubungi kami di support@7cellectronic.com</p>
            </div>
        </div>
    </div>
</body>
</html>