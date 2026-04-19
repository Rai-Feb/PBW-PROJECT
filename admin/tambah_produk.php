<?php
session_start();
if (!isset($_SESSION['status_login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
include '../config/koneksi.php';

$show_alert = false;

if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $varian_data = array();
    $harga_termurah = 9999999999;

    if (isset($_POST['varian_cek'])) {
        foreach ($_POST['varian_cek'] as $v) {
            $harga_v = $_POST['varian_harga'][$v];
            $varian_data[$v] = $harga_v;
            if ($harga_v < $harga_termurah) {
                $harga_termurah = $harga_v;
            }
        }
    }
    
    $harga = ($harga_termurah == 9999999999) ? 0 : $harga_termurah;
    $varian_json = json_encode($varian_data);
    $nama_file = "default.jpg";

    if ($_FILES['gambar']['name'] != '') {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_file = time() . "_" . uniqid() . "." . $ext;
        $tmp_file = $_FILES['gambar']['tmp_name'];
        move_uploaded_file($tmp_file, "../assets/img/" . $nama_file);
    }

    $insert = mysqli_query($conn, "INSERT INTO products (nama_barang, kategori, harga, stok, deskripsi, varian, gambar) VALUES ('$nama', '$kategori', '$harga', '$stok', '$deskripsi', '$varian_json', '$nama_file')");

    if ($insert) {
        $show_alert = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - ACE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --tk-green: #03AC0E; --tk-green-light: #E2F5ED; --tk-text: #31353B; --tk-text-muted: #8D96AA; --tk-border: #E5E7E9; --tk-surface: #F3F4F5; }
        body { background: var(--tk-surface); font-family: 'Open Sans', sans-serif; color: var(--tk-text); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 40px 0; }
        .form-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); width: 100%; max-width: 600px; }
        .input-group { margin-bottom: 24px; }
        .input-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        .input-group input[type="text"], .input-group input[type="number"], .input-group select, .input-group textarea { width: 100%; padding: 12px 16px; border: 1px solid var(--tk-border); border-radius: 8px; font-size: 14px; outline: none; font-family: inherit; }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus { border-color: var(--tk-green); }
        .file-upload-wrapper { border: 2px dashed var(--tk-border); padding: 20px; border-radius: 8px; text-align: center; background: #FAFAFA; cursor: pointer; position: relative; }
        .file-upload-wrapper input[type="file"] { opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer; }
        .variant-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .variant-card { border: 1px solid var(--tk-border); border-radius: 8px; padding: 8px; transition: 0.2s; }
        .variant-label { cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; }
        .price-input { display: none; margin-top: 8px; width: 100%; padding: 8px !important; font-size: 12px !important; }
        .btn-wrapper { display: flex; gap: 16px; margin-top: 32px; }
        .btn { flex: 1; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 14px; text-align: center; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: var(--tk-green); color: white; }
        .btn-secondary { background: white; color: var(--tk-text); border: 1px solid var(--tk-border); }
    </style>
</head>
<body>
    <?php if ($show_alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ title: 'Berhasil!', text: 'Produk sukses ditambahkan.', icon: 'success', confirmButtonColor: '#03AC0E', allowOutsideClick: false })
            .then((result) => { if (result.isConfirmed) window.location.href = 'produk.php'; });
        });
    </script>
    <?php endif; ?>

    <div class="form-card">
        <h2 style="margin-bottom: 32px; font-weight: 800; font-size: 22px;">Tambah Produk HP</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label>Foto Produk Utama</label>
                <div class="file-upload-wrapper">
                    <div style="font-weight: 700; color: var(--tk-green); margin-bottom: 4px;">Pilih Gambar Utama</div>
                    <input type="file" name="gambar" accept="image/*">
                </div>
            </div>
            <div class="input-group">
                <label>Nama Produk</label>
                <input type="text" name="nama" required>
            </div>
            <div style="display: flex; gap: 16px;">
                <div class="input-group" style="flex: 1;">
                    <label>Brand</label>
                    <select name="kategori" required>
                        <option value="Samsung">Samsung</option>
                        <option value="iPhone">iPhone</option>
                        <option value="Xiaomi">Xiaomi</option>
                        <option value="Oppo">Oppo</option>
                        <option value="Infinix">Infinix</option>
                    </select>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Total Stok Gudang</label>
                    <input type="number" name="stok" required>
                </div>
            </div>
            
            <div class="input-group">
                <label>Atur Varian RAM/ROM & Harga</label>
                <div class="variant-grid">
                    <?php 
                    $opsi = ['2/16', '3/32', '4/64', '4/128', '6/128', '8/128', '8/256', '12/256', '12/512'];
                    foreach($opsi as $index => $op): ?>
                    <div class="variant-card" id="card_<?= $index ?>">
                        <label class="variant-label">
                            <input type="checkbox" name="varian_cek[]" value="<?= $op ?>" onchange="togglePrice(this, <?= $index ?>)">
                            <?= $op ?> GB
                        </label>
                        <input type="number" name="varian_harga[<?= $op ?>]" id="harga_<?= $index ?>" class="price-input" placeholder="Rp...">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="input-group">
                <label>Spesifikasi Lengkap</label>
                <textarea name="deskripsi" rows="4" required></textarea>
            </div>
            
            <div class="btn-wrapper">
                <a href="produk.php" class="btn btn-secondary">Batal</a>
                <button type="submit" name="tambah" class="btn btn-primary">Publikasikan</button>
            </div>
        </form>
    </div>

    <script>
        function togglePrice(checkbox, index) {
            const input = document.getElementById('harga_' + index);
            const card = document.getElementById('card_' + index);
            if(checkbox.checked) {
                input.style.display = 'block';
                input.required = true;
                card.style.borderColor = 'var(--tk-green)';
                card.style.backgroundColor = 'var(--tk-green-light)';
            } else {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
                card.style.borderColor = 'var(--tk-border)';
                card.style.backgroundColor = 'transparent';
            }
        }
    </script>
</body>
</html>