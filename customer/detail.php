<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$product_id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    header('Location: katalog.php');
    exit;
}

$varians = json_decode($produk['varian'], true);
$selected_harga = $produk['harga_min'];
$selected_label = 'Standard';

if (is_array($varians) && count($varians) > 0) {
    $selected_harga = $varians[0]['harga'];
    $selected_label = $varians[0]['ram'] . '/' . $varians[0]['rom'];
}

$is_logged_in = isset($_SESSION['user_id']);
$user_email = '';
if ($is_logged_in) {
    $user_query = mysqli_query($conn, "SELECT email FROM users WHERE id = " . (int)$_SESSION['user_id']);
    if ($user_query) {
        $user_data = mysqli_fetch_assoc($user_query);
        $user_email = $user_data['email'] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $qty = (int)($_POST['qty'] ?? 1);
    if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];
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
    <title><?php echo htmlspecialchars($produk['nama_barang']); ?> - 7CellX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --gold-primary: #d4af37; --gold-light: #f4e5c2; --gold-dark: #aa8c2c; --cream: #faf8f3; --dark: #1a1a1a; --gray: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: var(--cream); }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 16px 0; position: sticky; top: 0; z-index: 1000; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 32px; }
        .navbar-content { display: flex; align-items: center; justify-content: space-between; }
        .navbar-brand { font-size: 1.8rem; font-weight: 900; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-menu { display: flex; gap: 24px; list-style: none; }
        .nav-menu a { text-decoration: none; color: var(--gray); font-weight: 600; font-size: 0.95rem; transition: all 0.3s; display: flex; align-items: center; gap: 8px; }
        .nav-menu a:hover { color: var(--gold-primary); }
        .user-section { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; padding-left: 24px; border-left: 2px solid #e5e7eb; }
        .user-email { font-size: 0.85rem; color: var(--gray); font-weight: 500; }
        .logout-link { color: var(--gold-primary); text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .logout-link:hover { color: var(--gold-dark); }
        .product-detail { padding: 40px 0; }
        .product-wrapper { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start; }
        .product-image-wrapper { background: linear-gradient(135deg, var(--gold-light) 0%, var(--cream) 100%); border-radius: 20px; padding: 40px; display: flex; align-items: center; justify-content: center; width: 100%; aspect-ratio: 1 / 1; overflow: hidden; border: 3px solid var(--gold-light); }
        .product-image { max-width: 90%; max-height: 90%; object-fit: contain; transition: transform 0.3s; border-radius: 12px; }
        .product-image:hover { transform: scale(1.05); }
        .product-info { padding-top: 10px; }
        .product-category { color: var(--gold-primary); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .product-title { font-size: 2rem; font-weight: 900; color: var(--dark); margin-bottom: 16px; line-height: 1.2; }
        .product-price { font-size: 2.5rem; font-weight: 900; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 24px; }
        .stock-info { display: flex; align-items: center; gap: 12px; padding: 14px 20px; background: #f0fdf4; border-radius: 12px; margin-bottom: 32px; width: fit-content; }
        .stock-info i { color: #10b981; }
        .stock-info span { font-weight: 600; color: #064e3b; }
        .variant-section { margin-bottom: 32px; }
        .variant-label { font-weight: 700; color: var(--dark); margin-bottom: 16px; display: block; }
        .variant-options { display: flex; gap: 12px; flex-wrap: wrap; }
        .variant-btn { padding: 12px 24px; border: 2px solid #e5e7eb; background: white; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-weight: 600; font-size: 0.95rem; color: var(--gray); }
        .variant-btn:hover { border-color: var(--gold-primary); color: var(--gold-primary); }
        .variant-btn.active { border-color: var(--gold-primary); background: var(--gold-light); color: var(--dark); box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2); }
        .qty-section { margin-bottom: 32px; display: flex; align-items: center; gap: 20px; }
        .qty-control { display: flex; align-items: center; border: 2px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .qty-btn { width: 44px; height: 44px; border: none; background: white; cursor: pointer; font-size: 1.2rem; color: var(--dark); transition: 0.2s; }
        .qty-btn:hover { background: #f3f4f6; }
        .qty-input { width: 60px; height: 44px; border: none; text-align: center; font-weight: 700; font-size: 1.1rem; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .qty-input:focus { outline: none; }
        .total-section { background: var(--cream); padding: 20px; border-radius: 16px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; border: 2px solid var(--gold-light); }
        .total-label { font-weight: 700; color: var(--dark); font-size: 1.1rem; }
        .total-value { font-weight: 900; color: var(--gold-dark); font-size: 1.4rem; }
        .action-buttons { display: flex; gap: 16px; margin-top: 30px; }
        .btn { padding: 16px 36px; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 12px; font-size: 1.05rem; flex: 1; }
        .btn-primary { background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(212,175,55,0.4); }
        .btn-outline { background: white; color: var(--gold-primary); border: 2px solid var(--gold-primary); }
        .btn-outline:hover { background: var(--gold-primary); color: white; }
        .description-section { margin-top: 40px; padding-top: 30px; border-top: 1px solid #e5e7eb; }
        .description-section h3 { font-size: 1.2rem; font-weight: 700; color: var(--dark); margin-bottom: 12px; }
        .description-section p { color: var(--gray); line-height: 1.7; }
        .footer { background: var(--dark); color: white; padding: 40px 0 30px; text-align: center; margin-top: 80px; }
        @media (max-width: 1024px) { .product-wrapper { grid-template-columns: 1fr; gap: 40px; padding: 24px; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="katalog.php" class="navbar-brand"><i class="bi bi-lightning-charge-fill"></i>7CellX</a>
                <ul class="nav-menu">
                    <li><a href="katalog.php"><i class="bi bi-store"></i> Katalog</a></li>
                    <li><a href="keranjang.php"><i class="bi bi-cart"></i> Keranjang</a></li>
                    <li><a href="pesanan.php"><i class="bi bi-box"></i> Pesanan</a></li>
                    <li><a href="chat.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                </ul>
                <?php if($is_logged_in): ?>
                <div class="user-section">
                    <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
                    <a href="../auth/logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
                <?php else: ?>
                <div class="user-section">
                    <a href="../auth/login.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Login</a>
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
                    $gambar_detail = '';
                    if (!empty($produk['gambar'])) {
                        $full_path = __DIR__ . '/../uploads/' . $produk['gambar'];
                        if (file_exists($full_path)) {
                            $gambar_detail = '../uploads/' . $produk['gambar'];
                        } else {
                            $gambar_detail = 'https://placehold.co/600x600/f4e5c2/d4af37?text=7CellX';
                        }
                    } else {
                        $gambar_detail = 'https://placehold.co/600x600/f4e5c2/d4af37?text=7CellX';
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($gambar_detail); ?>" alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>" class="product-image">
                </div>
                
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($produk['kategori']); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($produk['nama_barang']); ?></h1>
                    <div class="product-price" id="current-price">Rp <?php echo number_format($selected_harga, 0, ',', '.'); ?></div>
                    <div class="stock-info"><i class="bi bi-check-circle"></i><span>Sisa Stok: <?php echo $produk['stok']; ?> Unit</span></div>
                    
                    <div class="variant-section">
                        <label class="variant-label">Pilih Varian RAM/ROM:</label>
                        <div class="variant-options" id="variant-buttons">
                            <?php 
                            if (is_array($varians) && count($varians) > 0):
                                foreach ($varians as $index => $varian):
                                    $activeClass = ($index == 0) ? 'active' : '';
                                    ?>
                                    <button type="button" class="variant-btn <?php echo $activeClass; ?>" 
                                            onclick="updatePrice(<?php echo $varian['harga']; ?>, this)"
                                            data-label="<?php echo $varian['ram']; ?>/<?php echo $varian['rom']; ?>">
                                        <?php echo $varian['ram']; ?>/<?php echo $varian['rom']; ?> GB
                                    </button>
                                <?php endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                    
                    <div class="qty-section">
                        <label class="variant-label" style="margin:0;">Jumlah:</label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                            <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?php echo $produk['stok']; ?>" class="qty-input" readonly>
                            <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                        </div>
                    </div>

                    <div class="total-section">
                        <span class="total-label">Total Harga</span>
                        <span class="total-value" id="total-price">Rp <?php echo number_format($selected_harga, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="description-section">
                        <h3>Deskripsi Produk</h3>
                        <p><?php echo htmlspecialchars($produk['deskripsi'] ?? 'Produk berkualitas tinggi dengan garansi resmi.'); ?></p>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if($is_logged_in): ?>
                            <form method="POST" style="flex: 1;" id="addToCartForm">
                                <input type="hidden" name="qty" id="formQty" value="1">
                                <input type="hidden" name="variant_price" id="formVariantPrice" value="<?php echo $selected_harga; ?>">
                                <input type="hidden" name="variant_label" id="formVariantLabel" value="<?php echo htmlspecialchars($selected_label); ?>">
                                <button type="submit" class="btn btn-primary" <?php echo $produk['stok'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                </button>
                            </form>
                            <a id="btnBeliSekarang" href="checkout.php?product_id=<?php echo $product_id; ?>&qty=1&variant_price=<?php echo $selected_harga; ?>&variant_label=<?php echo urlencode($selected_label); ?>" class="btn btn-outline" <?php echo $produk['stok'] <= 0 ? 'style="display:none"' : ''; ?>>
                                <i class="bi bi-bolt"></i> Beli Sekarang
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-primary" style="flex: 1; background: #e5e7eb; color: #9ca3af; cursor: not-allowed;">
                                <i class="bi bi-lock"></i> Login untuk Membeli
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div style="font-size: 1.8rem; font-weight: 900; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 10px; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="bi bi-lightning-charge-fill"></i>7CellX
            </div>
            <p style="opacity: 0.8;">Premium Smartphone Store</p>
            <p style="margin-top: 16px; opacity: 0.6;">© 2024 - Project UAS PBW</p>
        </div>
    </footer>

    <script>
        let currentUnitPrice = <?php echo $selected_harga; ?>;
        const qtyInput = document.getElementById('qtyInput');
        const formQty = document.getElementById('formQty');
        const formVariantPrice = document.getElementById('formVariantPrice');
        const formVariantLabel = document.getElementById('formVariantLabel');
        const btnBeli = document.getElementById('btnBeliSekarang');

        function updatePrice(harga, btnElement) {
            currentUnitPrice = harga;
            document.getElementById('current-price').innerHTML = 'Rp ' + harga.toLocaleString('id-ID');
            formVariantPrice.value = harga;
            formVariantLabel.value = btnElement.getAttribute('data-label');
            document.querySelectorAll('.variant-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
            calculateTotal();
            updateCheckoutLink();
        }

        function updateQty(change) {
            let newValue = parseInt(qtyInput.value) + change;
            const maxStock = <?php echo $produk['stok']; ?>;
            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;
            qtyInput.value = newValue;
            formQty.value = newValue;
            calculateTotal();
            updateCheckoutLink();
        }

        function calculateTotal() {
            const qty = parseInt(qtyInput.value) || 1;
            const total = currentUnitPrice * qty;
            document.getElementById('total-price').innerHTML = 'Rp ' + total.toLocaleString('id-ID');
        }

        function updateCheckoutLink() {
            if (!btnBeli) return;
            const qty = qtyInput.value;
            const label = formVariantLabel.value;
            btnBeli.href = 'checkout.php?product_id=<?php echo $product_id; ?>&qty=' + qty + '&variant_price=' + currentUnitPrice + '&variant_label=' + encodeURIComponent(label);
        }

        document.querySelectorAll('.variant-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>