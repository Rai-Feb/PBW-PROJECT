<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = '';
$user_query = mysqli_query($conn, "SELECT nama FROM users WHERE id = $user_id");
if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
    $user_name = $user_data['nama'] ?? 'Customer';
}

// Ambil Data dari URL (Beli Sekarang) atau Session (Keranjang)
$product_id = (int)($_GET['product_id'] ?? 0);
$qty = (int)($_GET['qty'] ?? 1);
$variant_price = (int)($_GET['variant_price'] ?? 0);
$variant_label = $_GET['variant_label'] ?? 'Standard';

$produk = null;
$items = [];
$total_harga = 0;

// Skenario 1: Beli Sekarang (Langsung dari Detail)
if ($product_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($produk) {
        if ($variant_price == 0) $variant_price = $produk['harga_min'];
        
        $total_harga = $variant_price * $qty;
        $items[] = [
            'product_id' => $produk['id'],
            'nama' => $produk['nama_barang'],
            'varian' => $variant_label,
            'qty' => $qty,
            'harga' => $variant_price,
            'subtotal' => $total_harga
        ];
    }
} 
// Skenario 2: Checkout dari Keranjang
else if (!empty($_SESSION['keranjang'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['keranjang'])));
    $query = "SELECT * FROM products WHERE id IN ($ids)";
    $result = mysqli_query($conn, $query);
    
    while ($p = mysqli_fetch_assoc($result)) {
        $q = $_SESSION['keranjang'][$p['id']];
        $h = $p['harga_min']; 
        $sub = $h * $q;
        $total_harga += $sub;
        $items[] = [
            'product_id' => $p['id'],
            'nama' => $p['nama_barang'],
            'varian' => 'Standard',
            'qty' => $q,
            'harga' => $h,
            'subtotal' => $sub
        ];
    }
}

// --- PROSES PEMBAYARAN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    $alamat = trim($_POST['alamat']);
    $payment_method = $_POST['payment_method'];
    $catatan = trim($_POST['catatan']);

    if (empty($alamat) || empty($payment_method)) {
        $error_msg = "Alamat dan Metode Pembayaran wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Insert Order
            $stmt_order = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_harga, alamat, payment_method, status, catatan, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
            mysqli_stmt_bind_param($stmt_order, "idsss", $user_id, $total_harga, $alamat, $payment_method, $catatan);
            mysqli_stmt_execute($stmt_order);
            $order_id = mysqli_insert_id($conn);

            // 2. Insert Order Details & Kurangi Stok
            foreach ($items as $item) {
                $stmt_detail = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, varian) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detail, "iiids", $order_id, $item['product_id'], $item['qty'], $item['harga'], $item['varian']);
                mysqli_stmt_execute($stmt_detail);

                mysqli_query($conn, "UPDATE products SET stok = stok - {$item['qty']} WHERE id = {$item['product_id']}");
            }

            mysqli_commit($conn);

            // Kosongkan Keranjang jika dari keranjang
            if ($product_id == 0) unset($_SESSION['keranjang']);

            // 3. Redirect ke Invoice Page
            header("Location: invoice.php?id=$order_id");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "Gagal memproses pesanan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - 7Cellectronic</title>
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
        body { background: var(--cream); }
        
        .navbar {
            background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 16px 0; position: sticky; top: 0; z-index: 1000;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 32px; }
        .navbar-content { display: flex; align-items: center; justify-content: space-between; }
        .navbar-brand {
            font-size: 1.8rem; font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none; display: flex; align-items: center; gap: 10px;
        }
        
        .page-header { padding: 40px 0 20px; }
        .page-header h1 { font-size: 2rem; font-weight: 800; color: var(--dark); }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 32px;
            margin-bottom: 60px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        
        .card-title { font-size: 1.3rem; font-weight: 700; color: var(--dark); margin-bottom: 24px; border-bottom: 2px solid #f3f4f6; padding-bottom: 16px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); }
        .form-control {
            width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--gold-primary); }
        
        .payment-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .payment-option {
            border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; transition: 0.3s;
        }
        .payment-option:hover, .payment-option.selected { border-color: var(--gold-primary); background: var(--gold-light); }
        .payment-option input { display: none; }
        .payment-icon { font-size: 1.5rem; color: var(--gold-dark); margin-bottom: 8px; }
        .payment-name { font-weight: 600; font-size: 0.9rem; }
        
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--gray); }
        .summary-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6; font-size: 1.2rem; font-weight: 800; color: var(--dark); }
        
        .btn-checkout {
            width: 100%; padding: 18px; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; margin-top: 20px; transition: 0.3s;
        }
        .btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(212,175,55,0.4); }
        
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        
        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .payment-methods { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="katalog.php" class="navbar-brand"><i class="fas fa-bolt"></i> 7Cellectronic</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-credit-card me-2"></i>Checkout</h1>
            <p style="color: var(--gray);">Lengkapi data pengiriman dan pembayaran Anda</p>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="alert"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($items)): ?>
        <form method="POST">
            <div class="checkout-grid">
                <!-- Kolom Kiri: Form Input -->
                <div>
                    <!-- Alamat -->
                    <div class="card">
                        <div class="card-title"><i class="fas fa-map-marker-alt me-2" style="color: var(--gold-primary);"></i>Alamat Pengiriman</div>
                        <div class="form-group">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Jalan, No. Rumah, RT/RW, Kelurahan..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan untuk Kurir (Opsional)</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Misal: Taruh di depan pintu">
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="card">
                        <div class="card-title"><i class="fas fa-wallet me-2" style="color: var(--gold-primary);"></i>Metode Pembayaran</div>
                        <div class="payment-methods">
                            <label class="payment-option" onclick="selectPayment(this)">
                                <input type="radio" name="payment_method" value="transfer" required>
                                <div class="payment-icon"><i class="fas fa-university"></i></div>
                                <div class="payment-name">Transfer Bank</div>
                            </label>
                            <label class="payment-option" onclick="selectPayment(this)">
                                <input type="radio" name="payment_method" value="ewallet">
                                <div class="payment-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="payment-name">E-Wallet</div>
                            </label>
                            <label class="payment-option" onclick="selectPayment(this)">
                                <input type="radio" name="payment_method" value="cod">
                                <div class="payment-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                <div class="payment-name">COD</div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Ringkasan -->
                <div>
                    <div class="card">
                        <div class="card-title"><i class="fas fa-receipt me-2" style="color: var(--gold-primary);"></i>Ringkasan Pesanan</div>
                        <?php foreach($items as $item): ?>
                            <div class="summary-item">
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($item['nama']); ?></div>
                                    <div style="font-size: 0.85rem;">Varian: <?php echo htmlspecialchars($item['varian']); ?> (<?php echo $item['qty']; ?>x)</div>
                                </div>
                                <div style="font-weight: 600;">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="summary-item">
                            <span>Ongkos Kirim</span>
                            <span style="color: #10b981; font-weight: 600;">Gratis</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total Bayar</span>
                            <span style="color: var(--gold-dark);">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" class="btn-checkout">
                            <i class="fas fa-lock me-2"></i>Bayar Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 60px;">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                <h3>Keranjang Kosong</h3>
                <p style="color: var(--gray); margin: 10px 0 20px;">Belum ada produk yang dipilih.</p>
                <a href="katalog.php" style="display: inline-block; padding: 12px 30px; background: var(--gold-primary); color: white; text-decoration: none; border-radius: 10px; font-weight: 600;">Belanja Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectPayment(el) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
        }
    </script>
</body>
</html>