<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$brand = $_GET['brand'] ?? '';
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

if ($brand !== '') {
    $query .= " AND kategori = ?";
    $params[] = $brand;
    $types .= "s";
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

$brands_query = mysqli_query($conn, "SELECT DISTINCT kategori FROM products WHERE kategori IS NOT NULL ORDER BY kategori ASC");
$brands_list = [];
while ($b = mysqli_fetch_assoc($brands_query)) {
    $brands_list[] = $b['kategori'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fa;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand i {
            color: #00d4ff;
        }

        .nav-menu {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-menu a {
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a:hover {
            color: #00d4ff;
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 0;
            margin-bottom: 40px;
            color: white;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .filter-bar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-control {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: #00d4ff;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
        }

        .btn-outline {
            background: white;
            color: #00d4ff;
            border: 2px solid #00d4ff;
        }

        .btn-sm {
            padding: 10px 15px;
            font-size: 0.9rem;
        }

        .w-100 {
            width: 100%;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .product-image-wrapper {
            position: relative;
            height: 220px;
            background: #f8f9fa;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-stock {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-body {
            padding: 20px;
        }

        .product-category {
            color: #00d4ff;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 10px;
            height: 44px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            margin: 40px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .footer {
            background: #1a1a2e;
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
                    <li><a href="katalog.php" class="active"><i class="fas fa-store"></i> Katalog</a></li>
                    <li><a href="keranjang.php"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                    <li><a href="pesanan.php"><i class="fas fa-box"></i> Pesanan</a></li>
                    <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-mobile-alt me-3"></i>Katalog Produk</h1>
            <p>Temukan smartphone impian Anda dengan harga terbaik dan kualitas terjamin</p>
        </div>
    </section>

    <div class="container">
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div>
                    <input type="text" name="q" class="form-control" placeholder="Cari produk..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <select name="brand" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Brand</option>
                        <?php foreach ($brands_list as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $brand == $b ? 'selected' : ''; ?>
                                >
                                <?php echo htmlspecialchars($b); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="sort" class="form-control" onchange="this.form.submit()">
                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>⭐ Terbaru</option>
                        <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>💰 Termurah
                        </option>
                        <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>💎 Termahal
                        </option>
                    </select>
                </div>
                <div>
                    <a href="katalog.php" class="btn btn-outline w-100"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Produk tidak ditemukan</h3>
                <p style="color: #6b7280; margin: 10px 0 20px;">Coba ubah kata kunci pencarian Anda</p>
                <a href="katalog.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $produk):
                    $gambar = !empty($produk['gambar']) ? '../uploads/' . $produk['gambar'] : 'https://via.placeholder.com/300x220/667eea/ffffff?text=' . urlencode($produk['nama_barang']);
                    $harga = $produk['harga_min'] ?? $produk['harga'] ?? 0;
                    ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="<?php echo htmlspecialchars($gambar); ?>"
                                alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>" class="product-image">
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
                            <div class="product-actions">
                                <a href="detail.php?id=<?php echo $produk['id']; ?>" class="btn btn-primary btn-sm"
                                    style="flex: 1;">
                                    <i class="fas fa-eye me-2"></i>Detail
                                </a>
                                <form method="POST" action="add_to_cart.php" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $produk['id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-sm w-100">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p><i class="fas fa-bolt me-2"></i><strong>7Cellectronic</strong> - Toko Smartphone Terpercaya</p>
            <p style="opacity: 0.8; font-size: 0.9rem; margin-top: 10px;">© 2024 - Project UAS PBW</p>
        </div>
    </footer>
</body>

</html>