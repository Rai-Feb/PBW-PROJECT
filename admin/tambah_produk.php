<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $kategori = trim($_POST['kategori']);
    $stok = (int) $_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);
    $varian_json = trim($_POST['varian_json'] ?? '[]');

    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/' . $new_filename;

            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0777, true);
            }

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                $gambar = $new_filename;
            } else {
                $error = "Gagal mengupload gambar.";
            }
        } else {
            $error = "Format gambar tidak didukung.";
        }
    }

    if (empty($error)) {
        $decoded = json_decode($varian_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded)) {
            $error = "Minimal satu varian RAM/ROM harus diisi.";
        } else {
            $harga_min = min(array_column($decoded, 'harga'));
            $harga_max = max(array_column($decoded, 'harga'));

            $stmt = mysqli_prepare($conn, "INSERT INTO products (nama_barang, kategori, harga_min, harga_max, stok, deskripsi, gambar, varian, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "ssiiisss", $nama_barang, $kategori, $harga_min, $harga_max, $stok, $deskripsi, $gambar, $varian_json);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: produk.php');
                exit;
            } else {
                $error = "Gagal menambahkan produk.";
            }
        }
    }
}

$page_title = 'Tambah Produk';
include 'layout_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plus-circle me-3"></i>Tambah Produk Baru</h1>
    <p style="color: var(--gray); margin-top: 8px;">Lengkapi form di bawah untuk menambahkan produk</p>
</div>

<div class="content-card">
    <?php if ($success): ?>
        <div
            style="padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div
            style="padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444;">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">Nama Produk
                    <span style="color: #ef4444;">*</span></label>
                <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Samsung Galaxy S25 FE"
                    required
                    style="width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">Kategori/Brand
                    <span style="color: #ef4444;">*</span></label>
                <select name="kategori" required
                    style="width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                    <option value="">Pilih Kategori</option>
                    <option value="Samsung">Samsung</option>
                    <option value="iPhone">iPhone</option>
                    <option value="Xiaomi">Xiaomi</option>
                    <option value="Oppo">Oppo</option>
                    <option value="Vivo">Vivo</option>
                    <option value="Realme">Realme</option>
                    <option value="Infinix">Infinix</option>
                    <option value="iQOO">iQOO</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">Stok Total
                    <span style="color: #ef4444;">*</span></label>
                <input type="number" name="stok" placeholder="50" min="0" required
                    style="width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">Upload Gambar
                    <span style="color: #ef4444;">*</span></label>
                <input type="file" name="gambar" accept="image/*" required
                    style="width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px; grid-column: 1 / -1;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">Deskripsi
                    Produk</label>
                <textarea name="deskripsi" placeholder="Jelaskan spesifikasi, fitur, dan keunggulan produk..." rows="4"
                    style="width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; min-height: 120px; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 20px; grid-column: 1 / -1;">
                <div style="background: var(--cream); padding: 24px; border-radius: 16px; border: 1px solid #e5e7eb;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <label style="font-weight: 600; color: var(--dark); font-size: 1rem;">Varian RAM/ROM & Harga
                            <span style="color: #ef4444;">*</span></label>
                        <span style="font-size: 0.85rem; color: var(--gray);">Minimal 1 varian</span>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(3, 1fr) 60px; gap: 12px; margin-bottom: 16px; font-weight: 700; color: var(--dark); font-size: 0.9rem;">
                        <span>RAM (GB)</span>
                        <span>ROM (GB)</span>
                        <span>Harga (Rp)</span>
                        <span></span>
                    </div>

                    <div id="variant-container"></div>

                    <button type="button" id="add-variant-btn"
                        style="background: white; color: var(--gold-primary); border: 2px dashed var(--gold-primary); padding: 12px; border-radius: 12px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 12px; transition: all 0.3s;">
                        <i class="fas fa-plus me-2"></i> Tambah Varian
                    </button>
                </div>
                <input type="hidden" name="varian_json" id="varian-json-input">
            </div>
        </div>

        <div style="display: flex; gap: 16px; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f3f4f6;">
            <button type="submit" class="btn btn-primary" style="flex: 2;">
                <i class="fas fa-save"></i> Simpan Produk
            </button>
            <a href="produk.php" class="btn btn-secondary"
                style="flex: 1; background: white; color: var(--dark); border: 2px solid #e5e7eb; text-align: center;">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>

<script>
    const container = document.getElementById('variant-container');
    const addBtn = document.getElementById('add-variant-btn');
    const hiddenInput = document.getElementById('varian-json-input');
    let variantCount = 0;

    function createVariantRow() {
        variantCount++;
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.style.cssText = 'display: grid; grid-template-columns: repeat(3, 1fr) 60px; gap: 12px; margin-bottom: 12px; background: white; padding: 12px; border-radius: 10px; border: 1px solid #e5e7eb; animation: fadeIn 0.3s ease;';
        row.innerHTML = `
        <input type="text" class="v-ram" placeholder="8" required style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
        <input type="text" class="v-rom" placeholder="256" required style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
        <input type="number" class="v-harga" placeholder="7000000" min="0" required style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
        <button type="button" class="btn-remove" onclick="removeVariant(this)" style="background: #fee2e2; color: #ef4444; border: none; width: 40px; height: 40px; border-radius: 10px; cursor: pointer; transition: all 0.3s;">
            <i class="fas fa-trash"></i>
        </button>
    `;
        container.appendChild(row);
        syncVariants();
    }

    function removeVariant(btn) {
        if (container.children.length > 1) {
            btn.closest('.variant-row').remove();
            syncVariants();
        } else {
            alert('Minimal harus ada 1 varian!');
        }
    }

    function syncVariants() {
        const rows = document.querySelectorAll('.variant-row');
        const variants = [];
        rows.forEach(row => {
            variants.push({
                ram: row.querySelector('.v-ram').value || '0',
                rom: row.querySelector('.v-rom').value || '0',
                harga: parseInt(row.querySelector('.v-harga').value) || 0
            });
        });
        hiddenInput.value = JSON.stringify(variants);
    }

    container.addEventListener('input', syncVariants);
    addBtn.addEventListener('click', createVariantRow);

    createVariantRow();
</script>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn-remove:hover {
        background: #ef4444;
        color: white;
    }

    .btn-secondary:hover {
        background: var(--cream);
        border-color: var(--gold-primary);
    }
</style>

</main>
</body>

</html>