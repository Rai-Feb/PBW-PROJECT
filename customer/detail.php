<?php
session_start();
require_once '../config/koneksi.php';

$product_id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$produk) {
    header('Location: katalog.php');
    exit;
}

$user_email = '';
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_query = mysqli_query($conn, "SELECT email FROM users WHERE id = " . (int) $_SESSION['user_id']);
    if ($user_query) {
        $user_data = mysqli_fetch_assoc($user_query);
        $user_email = $user_data['email'] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $qty = (int) ($_POST['qty'] ?? 1);

    // Validasi stok
    if ($qty > $produk['stok']) {
        $qty = $produk['stok'];
    }
    if ($qty < 1)
        $qty = 1;

    if (!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }

    if (isset($_SESSION['keranjang'][$product_id])) {
        $_SESSION['keranjang'][$product_id] += $qty;
    } else {
        $_SESSION['keranjang'][$product_id] = $qty;
    }

    header('Location: keranjang.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($produk['nama_barang']); ?> - 7Cellectronic
    </title>
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

        .product-detail {
            padding: 60px 0;
        }

        .product-wrapper {
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        .product-image-wrapper {
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--cream) 100%);
            border-radius: 20px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
        }

        .product-image {
            max-width: 100%;
            max-height: 450px;
            object-fit: contain;
        }

        .product-info h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 16px;
        }

        .product-category {
            color: var(--gold-primary);
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 32px;
        }

        .stock-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f0fdf4;
            border-radius: 12px;
            margin-bottom: 32px;
        }

        .stock-info i {
            color: #10b981;
            font-size: 1.3rem;
        }

        .stock-info span {
            font-weight: 600;
            color: var(--dark);
        }

        .variant-section {
            margin-bottom: 32px;
        }

        .variant-label {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            display: block;
        }

        .variant-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .variant-btn {
            padding: 14px 28px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .variant-btn:hover,
        .variant-btn.active {
            border-color: var(--gold-primary);
            background: var(--gold-light);
            color: var(--dark);
        }

        .qty-section {
            margin-bottom: 40px;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .qty-btn {
            width: 44px;
            height: 44px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .qty-btn:hover {
            border-color: var(--gold-primary);
            color: var(--gold-primary);
        }

        .qty-input {
            width: 80px;
            height: 44px;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .qty-input:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .description-section {
            margin-bottom: 40px;
        }

        .description-section h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f3f4f6;
        }

        .description-section p {
            color: var(--gray);
            line-height: 1.8;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
        }

        .btn {
            padding: 18px 36px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1.05rem;
            flex: 1;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);
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

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0 30px;
            text-align: center;
            margin-top: 80px;
        }

        @media (max-width: 1024px) {
            .product-wrapper {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .product-image-wrapper {
                min-height: 350px;
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
                    <li><a href="pesanan.php"><i class="fas fa-box"></i> Pesanan</a></li>
                </ul>
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <div class="user-email">
                            <?php echo htmlspecialchars($user_email); ?>
                        </div>
                        <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                <?php else: ?>
                    <div class="user-info">
                        <a href="../auth/login.php" class="logout-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="product-detail">
        <div class="container">
            <div class="product-wrapper">
                <div class="product-image-wrapper">
                    <?php
                    $imagePath = '../assets/img/' . htmlspecialchars($produk['gambar']);
                    // Cek apakah file gambar benar-benar ada (opsional, agar tidak muncul broken image)
                    if (!empty($produk['gambar']) && file_exists($imagePath)):
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>"
                            class="product-image">
                    <?php else: ?>
                        <img src="../assets/img/placeholder.jpg" alt="Gambar tidak tersedia" class="product-image">
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <div class="product-category">
                        <?php echo htmlspecialchars($produk['kategori']); ?>
                    </div>
                    <h1>
                        <?php echo htmlspecialchars($produk['nama_barang']); ?>
                    </h1>
                    <div class="product-price">Rp
                        <?php echo number_format($produk['harga_min'] ?? $produk['harga'], 0, ',', '.'); ?>
                    </div>

                    <div class="stock-info">
                        <i class="fas fa-check-circle"></i>
                        <span>Sisa Stok:
                            <?php echo $produk['stok']; ?> Unit
                        </span>
                    </div>

                    <div class="variant-section">
                        <label class="variant-label">Pilih Varian RAM/ROM:</label>
                        <div class="variant-options">
                            <button type="button" class="variant-btn active">8/256 GB</button>
                            <button type="button" class="variant-btn">12/256 GB</button>
                            <button type="button" class="variant-btn">12/512 GB</button>
                        </div>
                    </div>

                    <div class="qty-section">
                        <label class="variant-label">Jumlah:</label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="updateQty(-1)"><i
                                    class="fas fa-minus"></i></button>
                            <input type="number" name="qty" id="qtyInput" value="1" min="1"
                                max="<?php echo $produk['stok']; ?>" class="qty-input">
                            <button type="button" class="qty-btn" onclick="updateQty(1)"><i
                                    class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="description-section">
                        <h3>Deskripsi Produk</h3>
                        <p>
                            <?php echo htmlspecialchars($produk['deskripsi'] ?? 'Produk berkualitas dengan garansi resmi. Spesifikasi lengkap dan fitur terbaru untuk pengalaman terbaik.'); ?>
                        </p>
                    </div>

                    <div class="action-buttons">
                        <?php if ($is_logged_in): ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="qty" id="formQty" value="1">
                                <button type="submit" class="btn btn-primary" <?php echo $produk['stok'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                </button>
                            </form>
                            <a href="checkout.php?id=<?php echo $product_id; ?>" class="btn btn-outline" <?php echo $produk['stok'] <= 0 ? 'style="display:none"' : ''; ?>>
                                <i class="fas fa-bolt"></i> Beli Sekarang
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-disabled" style="flex: 1;">
                                <i class="fas fa-lock"></i> Login untuk Membeli
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

    <script>
        function updateQty(change) {
            const input = document.getElementById('qtyInput');
            const formQty = document.getElementById('formQty');
            let newValue = parseInt(input.value) + change;
            const maxStock = <?php echo $produk['stok']; ?>;
            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;
            input.value = newValue;
            if (formQty) formQty.value = newValue;
        }

        document.querySelectorAll('.variant-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>

</html>