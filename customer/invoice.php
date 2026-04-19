<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status_login']) || !isset($_GET['id'])) { header("Location: katalog.php"); exit; }

$order_id = mysqli_real_escape_string($conn, $_GET['id']);
$varian_terpilih = isset($_GET['v']) ? $_GET['v'] : 'Standar';
$user_id = $_SESSION['user_id'];

if (isset($_GET['batal'])) {
    mysqli_query($conn, "UPDATE orders SET status = 'dibatalkan' WHERE id = '$order_id' AND user_id = '$user_id'");
    $q_detail = mysqli_query($conn, "SELECT product_id FROM order_details WHERE order_id = '$order_id'");
    while($row = mysqli_fetch_assoc($q_detail)){
        $pid = $row['product_id'];
        mysqli_query($conn, "UPDATE products SET stok = stok + 1 WHERE id = '$pid'");
    }
    header("Location: invoice.php?id=$order_id&v=$varian_terpilih");
    exit;
}

$q_order = mysqli_query($conn, "SELECT o.*, od.product_id, p.nama_barang FROM orders o JOIN order_details od ON o.id = od.order_id JOIN products p ON od.product_id = p.id WHERE o.id = '$order_id' AND o.user_id = '$user_id'");
$order = mysqli_fetch_assoc($q_order);
if(!$order) { header("Location: katalog.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: var(--tk-surface); display: flex; justify-content: center; padding: 40px 20px; font-family: 'Open Sans', sans-serif;}
        .invoice-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; }
        .icon-success { width: 64px; height: 64px; background: #E2F5ED; color: var(--tk-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; font-weight: bold;}
        .icon-cancel { background: #FFEAEA; color: var(--tk-red); }
        .inv-title { font-size: 24px; font-weight: 800; margin-bottom: 8px; color: var(--tk-text);}
        .inv-box { border: 1px solid var(--tk-border); border-radius: 8px; padding: 20px; text-align: left; margin: 32px 0; }
        .inv-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .btn-kembali { display: inline-block; background: var(--tk-green); color: white; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-decoration: none; margin-top: 16px; width: 100%;}
        .btn-batal { display: inline-block; color: var(--tk-red); font-size: 14px; font-weight: 700; text-decoration: none; margin-top: 16px; border: 1px solid var(--tk-red); padding: 10px 20px; border-radius: 8px; width: 100%;}
    </style>
</head>
<body>
    <div class="invoice-card">
        <?php if($order['status'] == 'dibatalkan'): ?>
            <div class="icon-success icon-cancel">✕</div>
            <div class="inv-title">Pesanan Dibatalkan</div>
            <p style="color: var(--tk-text-muted); font-size: 14px;">Sistem telah membatalkan pesanan ini dan stok telah dikembalikan ke etalase.</p>
        <?php else: ?>
            <div class="icon-success">✓</div>
            <div class="inv-title">Pesanan Berhasil!</div>
            <p style="color: var(--tk-text-muted); font-size: 14px;">Terima kasih telah berbelanja di 7Cellectronic.</p>
        <?php endif; ?>

        <div class="inv-box">
            <div class="inv-row">
                <span style="color: var(--tk-text-muted);">ID Pesanan</span>
                <span style="font-weight: 700; color: var(--tk-text);">INV-7CELL-<?= $order['id'] ?></span>
            </div>
            <div class="inv-row">
                <span style="color: var(--tk-text-muted);">Produk</span>
                <span style="font-weight: 600; color: var(--tk-text); text-align: right;"><?= $order['nama_barang'] ?><br><span style="font-size: 12px; color: var(--tk-green);">Varian: <?= htmlspecialchars($varian_terpilih) ?> GB</span></span>
            </div>
            <div class="inv-row" style="border-top: 1px dashed var(--tk-border); padding-top: 12px; margin-top: 12px;">
                <span style="color: var(--tk-text-muted);">Total Tagihan</span>
                <span style="font-weight: 800; color: var(--tk-green); font-size: 18px;">Rp<?= number_format($order['total_harga'], 0, ',', '.') ?></span>
            </div>
        </div>

        <a href="katalog.php" class="btn-kembali">Kembali Belanja</a>
        <?php if($order['status'] !== 'dibatalkan'): ?>
            <a href="invoice.php?id=<?= $order['id'] ?>&v=<?= $varian_terpilih ?>&batal=true" class="btn-batal" onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">Batalkan Pesanan</a>
        <?php endif; ?>
    </div>
</body>
</html>