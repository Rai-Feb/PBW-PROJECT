<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';

$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $query .= " AND (nama_barang LIKE ? OR kategori LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

switch ($sort) {
    case 'termurah':
        $query .= " ORDER BY harga_min ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY harga_min DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

$user_email = '';
$user_query = mysqli_query($conn, "SELECT email FROM users WHERE id = " . (int) $_SESSION['user_id']);
if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
    $user_email = $user_data['email'] ?? '';
}

$mockup_images = [
    'https://images.unsplash.com/photo-1592899677712-a5a254503381?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1567581935884-3349723552ca?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1512054502232-120bbc5a0d32?w=400&h=400&fit=crop'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>7CellX - Premium Smartphone Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: var(--cream);
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 16px 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .navbar-content {
            display: flex;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 32px;
            margin-left: auto;
        }

        .nav-menu {
            display: flex;
            gap: 24px;
            list-style: none;
            align-items: center;
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
        }

        .nav-menu a:hover {
            color: var(--gold-primary);
        }

        .user-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            padding-left: 24px;
            border-left: 2px solid #e5e7eb;
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }

        .logout-link {
            color: var(--gold-primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logout-link:hover {
            color: var(--gold-dark);
        }

        .hero {
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--cream) 100%);
            padding: 80px 0;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-text h1 span {
            color: var(--gold-primary);
        }

        .hero-text p {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .hero-image {
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            filter: drop-shadow(0 20px 40px rgba(212, 175, 55, 0.2));
        }

        .search-section {
            background: white;
            padding: 24px 32px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 50px;
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .search-wrapper {
            flex: 1;
            position: relative;
        }

        .search-wrapper i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold-primary);
        }

        .search-input {
            width: 100%;
            padding: 16px 24px 16px 52px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
        }

        .sort-select {
            padding: 16px 24px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            min-width: 220px;
            font-weight: 600;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 32px;
            margin-bottom: 80px;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s;
            border: 1px solid #f3f4f6;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(212, 175, 55, 0.2);
        }

        .product-image-wrapper {
            position: relative;
            height: 300px;
            background: linear-gradient(135deg, #faf8f3 0%, #f0ebe3 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.4s;
            border-radius: 16px;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .badge-stock {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .product-body {
            padding: 24px;
        }

        .product-category {
            color: var(--gold-primary);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 14px;
            line-height: 1.4;
            height: 50px;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.6rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .btn-detail {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 120px 20px;
            background: white;
            border-radius: 24px;
            margin: 40px 0;
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
            padding: 50px 0 30px;
            text-align: center;
            margin-top: 100px;
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 900;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 16px;
            }

            .nav-right {
                flex-direction: column;
                gap: 16px;
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .user-section {
                border-left: none;
                padding-left: 0;
                align-items: center;
            }

            .hero-text h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="katalog.php" class="navbar-brand">
                    <i class="bi bi-lightning-charge-fill"></i>
                    7CellX
                </a>
                <div class="nav-right">
                    <ul class="nav-menu">
                        <li><a href="keranjang.php"><i class="bi bi-cart"></i> Keranjang</a></li>
                        <li><a href="pesanan.php"><i class="bi bi-box"></i> Pesanan</a></li>
                        <li><a href="chat.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                    </ul>
                    <div class="user-section">
                        <div class="user-email">
                            <?php echo htmlspecialchars($user_email); ?>
                        </div>
                        <a href="../auth/logout.php" class="logout-link">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>NEW SMARTPHONE <span>COMPARE MODELS</span></h1>
                    <p>Temukan smartphone premium dengan teknologi terbaru. Kualitas terbaik, harga kompetitif, dan
                        garansi resmi untuk kenyamanan Anda.</p>
                    <a href="#products" class="btn-primary">
                        <i class="bi bi-bag"></i> Belanja Sekarang
                    </a>
                </div>
                <div class="hero-image">
                    <img src="https://images.unsplash.com/photo-1592899677712-a5a254503381?w=600&h=400&fit=crop&q=80"
                        alt="Smartphone Premium"
                        onerror="this.onerror=null; this.src='https://placehold.co/600x400/f4e5c2/d4af37?text=7CellX+Premium+Phones';">
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="search-section">
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" placeholder="Cari smartphone, tablet, atau aksesoris..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    onchange="window.location='?q='+this.value+'&sort=<?php echo $sort; ?>'">
            </div>
            <select class="sort-select"
                onchange="window.location='?q=<?php echo urlencode($search); ?>&sort='+this.value">
                <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>⭐ Produk Terbaru</option>
                <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>💰 Harga Termurah</option>
                <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>💎 Harga Termahal</option>
            </select>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>Produk tidak ditemukan</h3>
                <p style="color: var(--gray); margin: 12px 0 28px;">Coba gunakan kata kunci pencarian yang berbeda</p>
                <a href="katalog.php" class="btn-primary">Lihat Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="products-grid" id="products">
                <?php
                $mockup_index = 0;
                foreach ($products as $produk):
                    $gambar = '';
                    if (!empty($produk['gambar'])) {
                        $full_path = __DIR__ . '/../uploads/' . $produk['gambar'];
                        if (file_exists($full_path)) {
                            $gambar = '../uploads/' . $produk['gambar'];
                        } else {
                            $gambar = $mockup_images[$mockup_index % count($mockup_images)];
                        }
                    } else {
                        $gambar = $mockup_images[$mockup_index % count($mockup_images)];
                    }
                    $mockup_index++;
                    $harga = $produk['harga_min'] ?? $produk['harga'] ?? 0;
                    ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="<?php echo htmlspecialchars($gambar); ?>"
                                alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>" class="product-image"
                                onerror="this.onerror=null; this.src='https://placehold.co/400x400/f4e5c2/d4af37?text=7CellX';">
                            <?php if ($produk['stok'] <= 0): ?>
                                <span class="badge-stock badge-danger">Stok Habis</span>
                            <?php elseif ($produk['stok'] <= 5): ?>
                                <span class="badge-stock badge-warning">Sisa
                                    <?php echo $produk['stok']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-stock badge-success">Tersedia</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <div class="product-category">
                                <?php echo htmlspecialchars($produk['kategori']); ?>
                            </div>
                            <h5 class="product-title">
                                <?php echo htmlspecialchars($produk['nama_barang']); ?>
                            </h5>
                            <div class="product-price">Rp
                                <?php echo number_format($harga, 0, ',', '.'); ?>
                            </div>
                            <a href="detail.php?id=<?php echo $produk['id']; ?>" class="btn-detail">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-brand">
                <i class="bi bi-lightning-charge-fill"></i>
                7CellX
            </div>
            <p style="opacity: 0.8;">Premium Smartphone Store - Kualitas Terbaik untuk Anda</p>
            <p style="margin-top: 16px; opacity: 0.6;">© 2024 - Project UAS PBW</p>
        </div>
    </footer>
</body>

</html>