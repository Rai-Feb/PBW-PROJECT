<?php
session_start();
if (!isset($_SESSION['status_login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id'");
$row = mysqli_fetch_assoc($data);

if (isset($_POST['edit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $nama_file = $row['gambar'];

    if ($_FILES['gambar']['name'] != '') {
        if ($nama_file != 'default.jpg' && file_exists("../assets/img/" . $nama_file)) {
            unlink("../assets/img/" . $nama_file);
        }
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_file = time() . "_" . uniqid() . "." . $ext;
        $tmp_file = $_FILES['gambar']['tmp_name'];
        move_uploaded_file($tmp_file, "../assets/img/" . $nama_file);
    }

    $update = mysqli_query($conn, "UPDATE products SET nama_barang='$nama', kategori='$kategori', harga='$harga', stok='$stok', gambar='$nama_file' WHERE id='$id'");

    if ($update) {
        header("Location: produk.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - ACE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --tk-green: #03AC0E; --tk-text: #31353B; --tk-text-muted: #8D96AA; --tk-border: #E5E7E9; --tk-surface: #F3F4F5; }
        body { background: var(--tk-surface); font-family: 'Open Sans', sans-serif; color: var(--tk-text); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 40px 0; }
        .form-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); width: 100%; max-width: 550px; }
        .input-group { margin-bottom: 24px; }
        .input-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        .input-group input[type="text"], .input-group input[type="number"], .input-group select { width: 100%; padding: 12px 16px; border: 1px solid var(--tk-border); border-radius: 8px; font-size: 14px; outline: none; }
        .input-group input:focus, .input-group select:focus { border-color: var(--tk-green); }
        .file-upload-wrapper { border: 2px dashed var(--tk-border); padding: 20px; border-radius: 8px; text-align: center; background: #FAFAFA; cursor: pointer; position: relative; }
        .file-upload-wrapper input[type="file"] { opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer; }
        .btn-wrapper { display: flex; gap: 16px; margin-top: 32px; }
        .btn { flex: 1; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 14px; text-align: center; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: var(--tk-green); color: white; }
        .btn-secondary { background: white; color: var(--tk-text); border: 1px solid var(--tk-border); }
    </style>
</head>
<body>
    <div class="form-card">
        <h2 style="margin-bottom: 32px; font-weight: 800; font-size: 22px;">Edit Informasi Produk</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label>Foto Produk (Kosongkan jika tidak ingin diubah)</label>
                <div style="margin-bottom: 12px; display: flex; gap: 16px; align-items: center;">
                    <img src="../assets/img/<?= $row['gambar'] ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 1px solid var(--tk-border);">
                    <div class="file-upload-wrapper" style="flex: 1; padding: 12px;">
                        <div style="font-weight: 700; color: var(--tk-text); font-size: 13px;">Ganti Gambar Baru</div>
                        <input type="file" name="gambar" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="input-group">
                <label>Nama Produk</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($row['nama_barang']) ?>" required>
            </div>
            <div style="display: flex; gap: 16px;">
                <div class="input-group" style="flex: 1;">
                    <label>Kategori</label>
                    <select name="kategori" required>
                        <option value="HP" <?= $row['kategori'] == 'HP' ? 'selected' : '' ?>>Smartphone & HP</option>
                        <option value="Komputer" <?= $row['kategori'] == 'Komputer' ? 'selected' : '' ?>>Komputer & Laptop</option>
                        <option value="Sparepart" <?= $row['kategori'] == 'Sparepart' ? 'selected' : '' ?>>Aksesoris & Sparepart</option>
                    </select>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Stok Tersedia</label>
                    <input type="number" name="stok" value="<?= $row['stok'] ?>" min="0" required>
                </div>
            </div>
            <div class="input-group">
                <label>Harga Jual (Rp)</label>
                <input type="number" name="harga" value="<?= $row['harga'] ?>" min="1000" required>
            </div>
            <div class="btn-wrapper">
                <a href="produk.php" class="btn btn-secondary">Batalkan</a>
                <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>