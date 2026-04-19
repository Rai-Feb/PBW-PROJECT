<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status_login'])) { header("Location: ../auth/login.php"); exit; }
if (!isset($_GET['id']) || !isset($_POST['varian_pilihan'])) { header("Location: katalog.php"); exit; }

$id_produk = mysqli_real_escape_string($conn, $_GET['id']);
$varian_terpilih = mysqli_real_escape_string($conn, $_POST['varian_pilihan']);
$user_id = $_SESSION['user_id'];

$query_p = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id_produk'");
$produk = mysqli_fetch_assoc($query_p);

$varian_data = json_decode($produk['varian'], true);
$harga_final = isset($varian_data[$varian_terpilih]) ? $varian_data[$varian_terpilih] : $produk['harga'];

if (isset($_POST['bayar'])) {
    $q_order = mysqli_query($conn, "INSERT INTO orders (user_id, total_harga, status) VALUES ('$user_id', '$harga_final', 'pending')");
    $order_id = mysqli_insert_id($conn);

    mysqli_query($conn, "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan) VALUES ('$order_id', '$id_produk', 1, '$harga_final')");
    mysqli_query($conn, "UPDATE products SET stok = stok - 1 WHERE id = '$id_produk'");

    header("Location: invoice.php?id=$order_id&v=$varian_terpilih");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengiriman & Pembayaran</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .checkout-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 32px; }
        .card-co { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); }
        .co-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid var(--tk-border); padding-bottom: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid var(--tk-border); border-radius: 8px; font-family: inherit; font-size: 14px; outline: none;}
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; align-items: flex-start;}
        .summary-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 800; color: var(--tk-text); border-top: 1px dashed var(--tk-border); padding-top: 16px; margin-top: 16px; }
        .btn-pay { width: 100%; background: var(--tk-green); color: white; padding: 14px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; margin-top: 24px; font-size: 16px; }
    </style>
</head>
<body style="background: var(--tk-surface);">
    <header class="header-main"><a href="katalog.php" class="logo" style="color: var(--tk-green);">7Cellectronic</a></header>
    <div class="container">
        <h1 style="font-size: 24px; font-weight: 800;">Checkout</h1>
        <form method="POST" class="checkout-grid">
            <div class="card-co">
                <div class="co-title">Alamat Pengiriman</div>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" rows="4" required placeholder="Masukkan alamat lengkap..."></textarea>
                </div>
                <div class="co-title" style="margin-top: 32px;">Metode Pembayaran</div>
                <div class="form-group">
                    <select name="metode" required>
                        <option value="Transfer Bank">Transfer Bank (BCA/Mandiri/BNI)</option>
                        <option value="COD">Bayar di Tempat (COD)</option>
                    </select>
                </div>
            </div>
            <div class="card-co" style="height: fit-content;">
                <div class="co-title">Ringkasan Belanja</div>
                <div class="summary-row">
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 700;"><?= htmlspecialchars($produk['nama_barang']) ?></span>
                        <span style="font-size: 12px; color: var(--tk-text-muted); margin-top: 4px;">Varian: <?= $varian_terpilih ?> GB (1x)</span>
                    </div>
                    <span style="font-weight: 600;">Rp<?= number_format($harga_final, 0, ',', '.') ?></span>
                </div>
                <div class="summary-total">
                    <span>Total Tagihan</span>
                    <span style="color: var(--tk-green);">Rp<?= number_format($harga_final, 0, ',', '.') ?></span>
                </div>
                <button type="submit" name="bayar" class="btn-pay">Bayar Sekarang</button>
            </div>
            <input type="hidden" name="varian_pilihan" value="<?= $varian_terpilih ?>">
        </form>
    </div>
</body>
</html>