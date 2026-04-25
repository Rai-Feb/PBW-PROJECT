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

$keranjang = $_SESSION['keranjang'] ?? [];

$items = [];
$total = 0;

if (!empty($keranjang)) {
    $ids = implode(',', array_map('intval', array_keys($keranjang)));
    $query = "SELECT * FROM products WHERE id IN ($ids)";
    $result = mysqli_query($conn, $query);

    while ($produk = mysqli_fetch_assoc($result)) {
        $qty = $keranjang[$produk['id']];
        $harga = $produk['harga_min'] ?? $produk['harga'] ?? 0;
        $subtotal = $harga * $qty;
        $total += $subtotal;
        $produk['qty'] = $qty;
        $produk['subtotal'] = $subtotal;
        $items[] = $produk;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['qty'] as $id => $qty) {
            if ($qty > 0) {
                $_SESSION['keranjang'][$id] = $qty;
            } else {
                unset($_SESSION['keranjang'][$id]);
            }
        }
        header('Location: keranjang.php');
        exit;
    }

    if (isset($_POST['hapus'])) {
        $id = (int) $_POST['id'];
        unset($_SESSION['keranjang'][$id]);
        header('Location: keranjang.php');
        exit;
    }

    if (isset($_POST['checkout'])) {
        header('Location: checkout.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - 7Cellectronic</title>
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

        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 32px;
            margin-bottom: 60px;
        }

        .cart-items {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 24px;
            padding: 24px 0;
            border-bottom: 2px solid #f3f4f6;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #faf8f3 0%, #f0ebe3 100%);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .item-details h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .item-category {
            color: var(--gold-primary);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            border-color: var(--gold-primary);
            color: var(--gold-primary);
        }

        .qty-input {
            width: 60px;
            height: 36px;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-weight: 600;
        }

        .qty-input:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .item-subtotal {
            text-align: right;
        }

        .item-subtotal h4 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 8px;
        }

        .item-subtotal p {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 16px;
        }

        .btn-remove {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #ef4444;
            color: white;
        }

        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .summary-label {
            color: var(--gray);
            font-weight: 500;
        }

        .summary-value {
            font-weight: 700;
            color: var(--dark);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 3px solid var(--gold-primary);
            margin-top: 20px;
            margin-bottom: 28px;
        }

        .summary-total .summary-label {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .summary-total .summary-value {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 16px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--gold-primary);
            border: 2px solid var(--gold-primary);
            margin-bottom: 12px;
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

        @media (max-width: 1024px) {
            .cart-wrapper {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 16px;
            }

            .item-subtotal {
                grid-column: 1 / -1;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
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
                    <li><a href="pesanan.php"><i class="fas fa-box"></i> Pesanan</a></li>
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
            <h1><i class="fas fa-shopping-cart me-3"></i>Keranjang Belanja</h1>
            <p>Kelola pesanan Anda dengan mudah dan aman</p>
        </div>
    </div>

    <div class="container">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Keranjang Anda Kosong</h3>
                <p style="color: var(--gray); margin: 12px 0 28px;">Mulai belanja dan temukan smartphone impian Anda</p>
                <a href="katalog.php" class="btn btn-primary" style="max-width: 300px;">
                    <i class="fas fa-store me-2"></i>Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="cart-wrapper">
                    <div class="cart-items">
                        <?php foreach ($items as $item): ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <img src="<?php echo !empty($item['gambar']) ? '../uploads/' . $item['gambar'] : 'https://images.unsplash.com/photo-1592899677712-a5a254503381?w=200&h=200&fit=crop'; ?>"
                                        alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                </div>
                                <div class="item-details">
                                    <h3>
                                        <?php echo htmlspecialchars($item['nama_barang']); ?>
                                    </h3>
                                    <div class="item-category">
                                        <?php echo htmlspecialchars($item['kategori']); ?>
                                    </div>
                                    <div class="item-price">Rp
                                        <?php echo number_format($item['harga_min'] ?? $item['harga'], 0, ',', '.'); ?>
                                    </div>
                                    <div class="qty-control">
                                        <button type="button" class="qty-btn"
                                            onclick="updateQty(<?php echo $item['id']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['qty']; ?>" min="1"
                                            max="<?php echo $item['stok']; ?>" data-max="<?php echo $item['stok']; ?>" class="qty-input"
                                            onchange="this.form.submit()">
                                        <button type="button" class="qty-btn"
                                            onclick="updateQty(<?php echo $item['id']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    <h4>Subtotal</h4>
                                    <p>Rp
                                        <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                    </p>
                                    <button type="submit" name="hapus" value="1" class="btn-remove"
                                        onclick="return confirm('Hapus item ini?')">
                                        <i class="fas fa-trash me-2"></i>Hapus
                                    </button>
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" name="update" class="btn btn-outline">
                            <i class="fas fa-sync-alt me-2"></i>Update Keranjang
                        </button>
                    </div>

                    <div class="cart-summary">
                        <h3 class="summary-title">Ringkasan Pesanan</h3>
                        <div class="summary-row">
                            <span class="summary-label">Total Item</span>
                            <span class="summary-value">
                                <?php echo count($items); ?> produk
                            </span>
                        </div>
                        <div class="summary-total">
                            <span class="summary-label">Total Pembayaran</span>
                            <span class="summary-value">Rp
                                <?php echo number_format($total, 0, ',', '.'); ?>
                            </span>
                        </div>
                        <button type="submit" name="checkout" class="btn btn-primary">
                            <i class="fas fa-lock me-2"></i>Checkout Sekarang
                        </button>
                        <a href="katalog.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left me-2"></i>Lanjut Belanja
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
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

    <script>
    function updateQty(id, change) {
        const input = document.querySelector('input[name="qty[' + id + ']"]');
        const maxStock = parseInt(input.getAttribute('data-max'));
        let newValue = parseInt(input.value) + change;
        
        if (newValue < 1) newValue = 1;
        if (newValue > maxStock) newValue = maxStock;
        
        input.value = newValue;
        input.form.submit();
    }
</script>
</body>

</html>