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
    $query .= " AND (nama_barang LIKE ? OR kategori LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if ($brand !== '') {
    $query .= " AND kategori = ?";
    $params[] = $brand;
    $types .= "s";
}

switch($sort) {
    case 'termurah':
        $query .= " ORDER BY harga_min ASC, nama_barang ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY harga_min DESC, nama_barang DESC";
        break;
    case 'terlaris':
        $query .= " ORDER BY terjual DESC, nama_barang ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC, nama_barang ASC";
}

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

$brands_query = mysqli_query($conn, "SELECT DISTINCT kategori FROM products WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
$brands_list = [];
while($b = mysqli_fetch_assoc($brands_query)) {
    $brands_list[] = $b['kategori'];
}

$brand_display = $brand ? ucwords(str_replace('_', ' ', $brand)) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 700; color: #0d6efd !important; }
        .product-card { transition: transform 0.2s, box-shadow 0.2s; border: none; border-radius: 12px; overflow: hidden; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .product-img { height: 220px; object-fit: cover; background: #f8f9fa; }
        .product-title { font-size: 14px; font-weight: 600; height: 40px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .product-price { font-size: 16px; font-weight: 700; color: #198754; }
        .filter-section { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; margin: 30px 0; }
        .empty-state i { font-size: 64px; color: #dee2e6; margin-bottom: 20px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="katalog.php">
            <i class="fas fa-bolt text-primary"></i> 7Cellectronic
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="katalog.php"><i class="fas fa-store"></i> Katalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="keranjang.php"><i class="fas fa-shopping-cart"></i> Keranjang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pesanan.php"><i class="fas fa-box"></i> Pesanan</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php">Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-mobile-alt text-primary"></i> Katalog Produk
            </h2>
            <p class="text-muted">Temukan gadget impian Anda dengan harga terbaik</p>
        </div>
    </div>

    <div class="filter-section">
        <form method="GET" action="katalog.php" class="row g-3">
            <div class="col-lg-5 col-md-6">
                <label class="form-label fw-semibold"><i class="fas fa-search"></i> Cari Produk</label>
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Cari iPhone, Samsung, Xiaomi..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4">
                <label class="form-label fw-semibold"><i class="fas fa-tags"></i> Brand</label>
                <select name="brand" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Brand</option>
                    <?php foreach($brands_list as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $brand == $b ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-lg-3 col-md-4">
                <label class="form-label fw-semibold"><i class="fas fa-sort"></i> Urutkan</label>
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="terbaru" <?= $sort == 'terbaru' ? 'selected' : '' ?>>⭐ Terbaru</option>
                    <option value="termurah" <?= $sort == 'termurah' ? 'selected' : '' ?>>💰 Termurah</option>
                    <option value="termahal" <?= $sort == 'termahal' ? 'selected' : '' ?>>💎 Termahal</option>
                    <option value="terlaris" <?= $sort == 'terlaris' ? 'selected' : '' ?>>🔥 Terlaris</option>
                </select>
            </div>
            
            <div class="col-lg-1 col-md-12 d-flex align-items-end">
                <a href="katalog.php" class="btn btn-outline-secondary w-100" title="Reset Filter">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <?php if($search || $brand): ?>
    <div class="mb-3">
        <span class="text-muted">
            Menampilkan: 
            <?php if($search): ?>
                <span class="badge bg-primary"><?= htmlspecialchars($search) ?></span>
            <?php endif; ?>
            <?php if($brand): ?>
                <span class="badge bg-success"><?= htmlspecialchars($brand_display) ?></span>
            <?php endif; ?>
            <span class="badge bg-secondary"><?= count($products) ?> produk</span>
        </span>
        <a href="katalog.php" class="btn btn-sm btn-link">Clear all</a>
    </div>
    <?php endif; ?>

    <?php if(empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h4 class="fw-bold mb-2">
                <?php if($search): ?>
                    Produk "<?= htmlspecialchars($search) ?>" tidak ditemukan
                <?php elseif($brand): ?>
                    Tidak ada produk untuk brand <?= htmlspecialchars($brand_display) ?>
                <?php else: ?>
                    Belum ada produk tersedia
                <?php endif; ?>
            </h4>
            <p class="text-muted mb-4">
                <?php if($search || $brand): ?>
                    Coba ubah kata kunci atau hapus filter untuk melihat lebih banyak produk
                <?php else: ?>
                    Produk akan segera hadir. Stay tuned!
                <?php endif; ?>
            </p>
            <a href="katalog.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i> Lihat Semua Produk
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach($products as $produk): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card h-100">
                        <div class="position-relative">
                            <?php
                            $gambar = !empty($produk['gambar']) ? '../uploads/' . $produk['gambar'] : 'https://via.placeholder.com/300x220?text=' . urlencode($produk['nama_barang']);
                            $gambar_path = !empty($produk['gambar']) ? '../uploads/' . $produk['gambar'] : '';
                            $gambar_final = file_exists(__DIR__ . '/../uploads/' . $produk['gambar']) ? '../uploads/' . $produk['gambar'] : 'https://via.placeholder.com/300x220?text=No+Image';
                            ?>
                            <img src="<?= htmlspecialchars($gambar_final) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($produk['nama_barang']) ?>">
                            
                            <?php if($produk['stok'] <= 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-danger m-2">Habis</span>
                            <?php elseif($produk['stok'] <= 5): ?>
                                <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Sisa <?= $produk['stok'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="product-title mb-2" title="<?= htmlspecialchars($produk['nama_barang']) ?>">
                                <?= htmlspecialchars($produk['nama_barang']) ?>
                            </h5>
                            
                            <p class="text-muted small mb-2">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($produk['kategori']) ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="product-price mb-2">
                                    Rp <?= number_format($produk['harga_min'] ?? $produk['harga'] ?? 0, 0, ',', '.') ?>
                                </div>
                                
                                <?php if($produk['stok'] > 0): ?>
                                    <a href="detail.php?id=<?= $produk['id'] ?>" class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-eye me-1"></i> Lihat Detail
                                    </a>
                                    <form method="POST" action="add_to_cart.php" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?= $produk['id'] ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-ban me-1"></i> Stok Habis
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5 mb-3">
            <p class="text-muted">
                Menampilkan <strong><?= count($products) ?></strong> dari <strong><?= count($products) ?></strong> produk
            </p>
        </div>
    <?php endif; ?>
</div>

<footer class="bg-white border-top mt-5 py-4">
    <div class="container text-center">
        <p class="mb-0 text-muted">
            <i class="fas fa-bolt text-primary"></i> <strong>7Cellectronic</strong> - Toko Elektronik Terpercaya
        </p>
        <small class="text-muted">© 2024 - Project UAS PBW</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= $_SESSION['success'] ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?= $_SESSION['error'] ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>