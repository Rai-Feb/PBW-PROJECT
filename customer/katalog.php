<?php
session_start();
include '../config/koneksi.php';

$brand_aktif = isset($_GET['brand']) ? $_GET['brand'] : 'Semua';
$sort_aktif = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

$where = "";
if ($brand_aktif !== 'Semua') {
    $b = mysqli_real_escape_string($conn, $brand_aktif);
    $where = "WHERE kategori = '$b'";
}

$order = "ORDER BY id DESC";
if ($sort_aktif === 'termurah') $order = "ORDER BY harga ASC";
if ($sort_aktif === 'termahal') $order = "ORDER BY harga DESC";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>7Cellectronic | Toko HP Terpercaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .brand-nav { display: flex; gap: 12px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px; }
        .brand-pill { padding: 8px 20px; border-radius: 24px; border: 1px solid var(--tk-border); font-size: 13px; font-weight: 600; color: var(--tk-text); text-decoration: none; background: white; white-space: nowrap; transition: 0.2s; }
        .brand-pill.active { background: var(--tk-green); color: white; border-color: var(--tk-green); }
        .filter-select { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--tk-border); font-size: 13px; font-weight: 600; outline: none; cursor: pointer; }
        .clickable-card { display: block; text-decoration: none; color: inherit; transition: transform 0.2s; }
        .clickable-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <header class="header-main">
        <a href="katalog.php" class="logo" style="color: var(--tk-green);">7Cellectronic</a>
        <div class="search-container">
            <input type="text" placeholder="Cari smartphone idamanmu...">
        </div>
        <div class="header-actions">
            <?php if(isset($_SESSION['status_login'])): ?>
                <div style="font-size: 14px; font-weight: 700;">Halo, <?= $_SESSION['nama']; ?></div>
                <a href="../auth/logout.php" style="color: var(--tk-red); font-size: 13px; font-weight: 700; text-decoration: none; margin-left: 15px;">Keluar</a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">
        <div class="brand-nav">
            <?php 
            $brands = ['Semua', 'Samsung', 'iPhone', 'Xiaomi', 'Oppo', 'Infinix'];
            foreach($brands as $b) {
                $active = ($brand_aktif === $b) ? 'active' : '';
                echo "<a href='?brand=$b&sort=$sort_aktif' class='brand-pill $active'>$b</a>";
            }
            ?>
        </div>

        <div class="section-header">
            <div class="section-title">Katalog Produk</div>
            <select class="filter-select" onchange="window.location.href=this.value;">
                <option value="?brand=<?= $brand_aktif ?>&sort=terbaru" <?= $sort_aktif == 'terbaru' ? 'selected' : '' ?>>Paling Baru</option>
                <option value="?brand=<?= $brand_aktif ?>&sort=termurah" <?= $sort_aktif == 'termurah' ? 'selected' : '' ?>>Harga Termurah</option>
                <option value="?brand=<?= $brand_aktif ?>&sort=termahal" <?= $sort_aktif == 'termahal' ? 'selected' : '' ?>>Harga Tertinggi</option>
            </select>
        </div>

        <div class="product-grid">
            <?php
            $query = mysqli_query($conn, "SELECT * FROM products $where $order");
            while ($row = mysqli_fetch_assoc($query)) { 
            ?>
            <a href="detail.php?id=<?= $row['id'] ?>" class="product-card clickable-card">
                <div class="discount-badge">Promo</div>
                <img src="../assets/img/<?= !empty($row['gambar']) ? $row['gambar'] : 'default.jpg' ?>" class="product-img" onerror="this.src='https://placehold.co/400x400?text=HP'">
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($row['nama_barang']) ?></div>
                    <div class="product-price">Rp<?= number_format($row['harga'], 0, ',', '.') ?></div>
                    <div class="rating-sold"><span class="star">★</span> 5.0 | Sisa: <?= $row['stok'] ?></div>
                </div>
            </a>
            <?php } ?>
        </div>
    </main>
</body>
</html>