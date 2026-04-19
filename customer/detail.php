<?php
session_start();
include '../config/koneksi.php';

if (!isset($_GET['id'])) header("Location: katalog.php");
$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id'");
$produk = mysqli_fetch_assoc($query);

if (!$produk) header("Location: katalog.php");

$varian_data = json_decode($produk['varian'], true);
if(!$varian_data || !is_array($varian_data)) {
    $varian_data = ['Standar' => $produk['harga']];
}

$varian_keys = array_keys($varian_data);
$varian_pertama = $varian_keys[0];
$harga_pertama = $varian_data[$varian_pertama];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($produk['nama_barang']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .detail-container { display: flex; gap: 40px; margin-top: 32px; background: white; padding: 32px; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); }
        .img-section { flex: 1; border-radius: 12px; overflow: hidden; border: 1px solid var(--tk-border); position: sticky; top: 90px; height: fit-content; }
        .img-section img { width: 100%; height: auto; display: block; object-fit: cover; aspect-ratio: 1/1; }
        .info-section { flex: 1.5; display: flex; flex-direction: column; }
        .p-title { font-size: 24px; font-weight: 700; margin-bottom: 12px; line-height: 1.4; }
        .p-price { font-size: 32px; font-weight: 800; color: var(--tk-text); margin-bottom: 24px; transition: color 0.3s; }
        .variant-section { margin-bottom: 24px; }
        .variant-title { font-size: 14px; font-weight: 700; margin-bottom: 12px; }
        .variant-list { display: flex; flex-wrap: wrap; gap: 12px; }
        .v-pill { padding: 10px 20px; border: 1px solid var(--tk-border); border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; background: white; color: var(--tk-text); }
        .v-pill.active { border-color: var(--tk-green); background: #E2F5ED; color: var(--tk-green); }
        .spec-box { border-top: 1px solid var(--tk-border); padding: 24px 0; margin-top: 16px; margin-bottom: 24px; }
        .spec-title { font-weight: 800; font-size: 16px; margin-bottom: 12px; }
        .spec-text { font-size: 14px; line-height: 1.8; color: var(--tk-text-muted); }
        .action-box { display: flex; gap: 16px; margin-top: 24px; }
        .btn-buy { background: var(--tk-green); color: white; padding: 14px 24px; border-radius: 8px; font-weight: 700; flex: 1; text-align: center; border: none; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body style="background: var(--tk-surface);">
    <header class="header-main"><a href="katalog.php" class="logo" style="color: var(--tk-green);">7Cellectronic</a></header>
    <div class="container">
        <div class="detail-container">
            <div class="img-section">
                <img src="../assets/img/<?= !empty($produk['gambar']) ? $produk['gambar'] : 'default.jpg' ?>" onerror="this.src='https://placehold.co/600x600'">
            </div>
            <div class="info-section">
                <div style="color: var(--tk-green); font-weight: 800; font-size: 14px; margin-bottom: 8px; text-transform: uppercase;"><?= htmlspecialchars($produk['kategori']) ?></div>
                <div class="p-title"><?= htmlspecialchars($produk['nama_barang']) ?></div>
                <div class="p-price" id="display_price">Rp<?= number_format($harga_pertama, 0, ',', '.') ?></div>
                
                <div class="variant-section">
                    <div class="variant-title">Pilih Varian RAM/ROM:</div>
                    <div class="variant-list">
                        <?php foreach($varian_keys as $index => $v): ?>
                            <div class="v-pill <?= $index === 0 ? 'active' : '' ?>" onclick="selectVariant(this, '<?= $v ?>')"><?= $v ?> GB</div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="font-size: 14px; font-weight: 600; color: var(--tk-text-muted);">Sisa Stok: <span style="color: var(--tk-text);"><?= $produk['stok'] ?> Unit</span></div>

                <div class="spec-box">
                    <div class="spec-title">Deskripsi Produk</div>
                    <div class="spec-text"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></div>
                </div>
                
                <form action="checkout.php?id=<?= $produk['id'] ?>" method="POST" class="action-box">
                    <input type="hidden" name="varian_pilihan" id="hidden_variant" value="<?= $varian_pertama ?>">
                    <?php if(isset($_SESSION['status_login'])): ?>
                        <button type="submit" class="btn-buy">Beli Langsung</button>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-buy" style="background: var(--tk-border); color: var(--tk-text-muted); pointer-events: none;">Login untuk Membeli</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script>
        const variantData = <?= json_encode($varian_data) ?>;
        
        function selectVariant(element, varian) {
            document.querySelectorAll('.v-pill').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('hidden_variant').value = varian;
            
            const newPrice = variantData[varian];
            document.getElementById('display_price').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(newPrice);
        }
    </script>
</body>
</html>