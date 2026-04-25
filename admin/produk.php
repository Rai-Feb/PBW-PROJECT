<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header('Location: produk.php');
    exit;
}

$page_title = 'Kelola Produk';
include 'layout_header.php';

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1><i class="fas fa-box me-3"></i>Kelola Produk</h1>
        <p style="color: var(--gray); margin-top: 8px;">Kelola semua produk di toko Anda</p>
    </div>
    <a href="tambah_produk.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Produk
    </a>
</div>

<div class="content-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        ID</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Produk</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Kategori</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Varian</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Stok</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Status</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = mysqli_fetch_assoc($products)):
                    $varian = json_decode($product['varian'] ?? '[]', true);
                    $varian_count = is_array($varian) ? count($varian) : 0;
                    ?>
                    <tr>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;"><strong>#
                                <?php echo $product['id']; ?>
                            </strong></td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if (!empty($product['gambar'])): ?>
                                    <img src="../uploads/<?php echo $product['gambar']; ?>"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);">
                                        <?php echo htmlspecialchars($product['nama_barang']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        <?php if ($varian_count > 0): ?>
                                            <?php echo $varian_count; ?> varian
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <?php echo htmlspecialchars($product['kategori']); ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <?php if ($varian_count > 0): ?>
                                <div style="font-size: 0.85rem; color: var(--gray);">
                                    <?php foreach (array_slice($varian, 0, 2) as $v): ?>
                                        <div>
                                            <?php echo $v['ram']; ?>/
                                            <?php echo $v['rom']; ?> GB - Rp
                                            <?php echo number_format($v['harga'], 0, ',', '.'); ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($varian_count > 2): ?>
                                        <div style="color: var(--gold-primary);">+
                                            <?php echo ($varian_count - 2); ?> lainnya
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--gray);">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <?php echo $product['stok']; ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <?php if ($product['stok'] <= 0): ?>
                                <span
                                    style="padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: #fee2e2; color: #991b1b;">Habis</span>
                            <?php elseif ($product['stok'] <= 5): ?>
                                <span
                                    style="padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: #fef3c7; color: #92400e;">Menipis</span>
                            <?php else: ?>
                                <span
                                    style="padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: #d1fae5; color: #065f46;">Tersedia</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <a href="edit_produk.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm"
                                style="margin-right: 8px;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="produk.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Hapus produk ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</body>

</html>