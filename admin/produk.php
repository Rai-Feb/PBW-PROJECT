<?php
session_start();
if (!isset($_SESSION['status_login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
include '../config/koneksi.php';

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = mysqli_query($conn, "SELECT gambar FROM products WHERE id = '$id'");
    $data_gambar = mysqli_fetch_assoc($cek);
    
    if ($data_gambar['gambar'] != 'default.jpg' && file_exists("../assets/img/" . $data_gambar['gambar'])) {
        unlink("../assets/img/" . $data_gambar['gambar']);
    }
    
    mysqli_query($conn, "DELETE FROM products WHERE id = '$id'");
    header("Location: produk.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inventaris Produk - ACE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --tk-green: #03AC0E; --tk-text: #31353B; --tk-text-muted: #8D96AA; --tk-border: #E5E7E9; --tk-surface: #F3F4F5; --tk-red: #FF5C5C; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', sans-serif; }
        body { background: var(--tk-surface); color: var(--tk-text); }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: white; border-right: 1px solid var(--tk-border); padding: 24px 16px; }
        .main-content { flex: 1; padding: 32px; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--tk-text); text-decoration: none; border-radius: 8px; font-weight: 600; margin-bottom: 8px; }
        .nav-item.active { background: #E2F5ED; color: var(--tk-green); }
        .nav-item:hover:not(.active) { background: var(--tk-surface); }
        .data-card { background: white; border-radius: 12px; border: 1px solid var(--tk-border); padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .table-tk { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table-tk th { text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--tk-border); color: var(--tk-text-muted); font-size: 13px; font-weight: 600; }
        .table-tk td { padding: 16px; border-bottom: 1px solid var(--tk-border); font-size: 14px; vertical-align: middle; }
        .product-cell { display: flex; align-items: center; gap: 16px; }
        .thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--tk-border); }
        .badge-kategori { background: var(--tk-surface); color: var(--tk-text); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .btn-action { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700; }
        .btn-add { background: var(--tk-green); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div style="font-size: 24px; font-weight: 800; color: var(--tk-green); margin-bottom: 32px; letter-spacing: -1px; padding-left: 16px;">tokoelectro</div>
            <a href="index.php" class="nav-item">Wawasan Toko</a>
            <a href="produk.php" class="nav-item active">Produk</a>
            <a href="../auth/logout.php" class="nav-item" style="margin-top: 50px; color: var(--tk-red);">Keluar</a>
        </aside>
        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="font-size: 24px; font-weight: 800;">Daftar Produk</h1>
                <a href="tambah_produk.php" class="btn-add">+ Tambah Produk</a>
            </div>
            <div class="data-card">
                <table class="table-tk">
                    <thead>
                        <tr>
                            <th>INFO PRODUK</th>
                            <th>KATEGORI</th>
                            <th>HARGA</th>
                            <th>STOK</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($query)) {
                        ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <img src="../assets/img/<?= !empty($row['gambar']) ? $row['gambar'] : 'default.jpg' ?>" class="thumb" onerror="this.src='https://placehold.co/100x100?text=IMG'">
                                    <span style="font-weight: 700;"><?= htmlspecialchars($row['nama_barang']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge-kategori"><?= htmlspecialchars($row['kategori']) ?></span></td>
                            <td style="font-weight: 700;">Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td><?= $row['stok'] ?></td>
                            <td>
                                <a href="edit_produk.php?id=<?= $row['id'] ?>" class="btn-action" style="background: white; border: 1px solid var(--tk-border); color: var(--tk-text);">Edit</a>
                                <a href="produk.php?hapus=<?= $row['id'] ?>" class="btn-action" style="color: var(--tk-red); margin-left: 8px;" onclick="return confirm('Hapus produk ini secara permanen?')">Hapus</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>