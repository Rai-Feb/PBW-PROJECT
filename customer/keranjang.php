<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
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
        $id = (int)$_POST['id'];
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
    <title>Keranjang - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .cart-item { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .cart-img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .summary-box { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="katalog.php">7Cellectronic</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="katalog.php">Katalog</a>
            <a class="nav-link active" href="keranjang.php">Keranjang</a>
            <a class="nav-link" href="pesanan.php">Pesanan</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h2>
    
    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <p class="text-muted">Keranjang Anda kosong</p>
            <a href="katalog.php" class="btn btn-primary">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php 
                                    $gambar = !empty($item['gambar']) ? '../uploads/' . $item['gambar'] : 'https://via.placeholder.com/100x100?text=No+Image';
                                    ?>
                                    <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>" class="cart-img">
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['nama_barang']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($item['kategori']) ?></small>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stok'] ?>" class="form-control" style="width: 80px;">
                                </div>
                                <div class="col-md-2">
                                    <strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="submit" name="hapus" class="btn btn-sm btn-danger" onclick="return confirm('Hapus item?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="update" class="btn btn-warning">Update Keranjang</button>
                </div>
                
                <div class="col-lg-4">
                    <div class="summary-box">
                        <h5 class="mb-3">Ringkasan</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Item</span>
                            <span><?= count($items) ?> produk</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total Harga</strong>
                            <strong class="text-primary">Rp <?= number_format($total, 0, ',', '.') ?></strong>
                        </div>
                        <button type="submit" name="checkout" class="btn btn-primary w-100 btn-lg">Checkout</button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>