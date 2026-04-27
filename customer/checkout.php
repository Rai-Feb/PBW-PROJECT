<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = '';
$user_query = mysqli_query($conn, "SELECT nama FROM users WHERE id = $user_id");
if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
    $user_name = $user_data['nama'] ?? 'Customer';
}

$product_id = (int)($_GET['product_id'] ?? 0);
$qty = (int)($_GET['qty'] ?? 1);
$variant_price = (int)($_GET['variant_price'] ?? 0);
$variant_label = $_GET['variant_label'] ?? 'Standard';

$items = [];
$total_harga = 0;

if ($product_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($produk) {
        if ($variant_price == 0) $variant_price = $produk['harga_min'];
        $total_harga = $variant_price * $qty;
        $items[] = [
            'product_id' => $produk['id'],
            'nama' => $produk['nama_barang'],
            'varian' => $variant_label,
            'qty' => $qty,
            'harga' => $variant_price,
            'subtotal' => $total_harga
        ];
    }
} else if (!empty($_SESSION['keranjang'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['keranjang'])));
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids)");
    
    while ($p = mysqli_fetch_assoc($result)) {
        $q = $_SESSION['keranjang'][$p['id']];
        $h = $p['harga_min'];
        $sub = $h * $q;
        $total_harga += $sub;
        $items[] = [
            'product_id' => $p['id'],
            'nama' => $p['nama_barang'],
            'varian' => 'Standard',
            'qty' => $q,
            'harga' => $h,
            'subtotal' => $sub
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    $alamat = trim($_POST['alamat']);
    $payment_method = $_POST['payment_method'];
    $payment_detail = trim($_POST['payment_detail'] ?? '');
    $paid_amount = (int)($_POST['paid_amount'] ?? 0);
    $catatan = trim($_POST['catatan']);

    if (empty($alamat) || empty($payment_method)) {
        $error_msg = "Alamat dan Metode Pembayaran wajib diisi!";
    } else if ($paid_amount < $total_harga) {
        $error_msg = "Pembayaran ditolak! Nominal yang Anda masukkan (Rp " . number_format($paid_amount, 0, ',', '.') . ") kurang dari total tagihan (Rp " . number_format($total_harga, 0, ',', '.') . ")";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $status = ($payment_method === 'cod') ? 'pending' : 'paid';
            
            $stmt_order = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_harga, alamat, payment_method, payment_detail, status, catatan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_order, "idsssss", $user_id, $total_harga, $alamat, $payment_method, $payment_detail, $status, $catatan);
            mysqli_stmt_execute($stmt_order);
            $order_id = mysqli_insert_id($conn);

            foreach ($items as $item) {
                $stmt_detail = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, varian) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detail, "iiids", $order_id, $item['product_id'], $item['qty'], $item['harga'], $item['varian']);
                mysqli_stmt_execute($stmt_detail);
                mysqli_query($conn, "UPDATE products SET stok = stok - {$item['qty']} WHERE id = {$item['product_id']}");
            }

            mysqli_commit($conn);
            if ($product_id == 0) unset($_SESSION['keranjang']);
            header("Location: invoice.php?id=$order_id&status=success");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "Gagal memproses pesanan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --gold-primary: #d4af37; --gold-light: #f4e5c2; --gold-dark: #aa8c2c; --cream: #faf8f3; --dark: #1a1a1a; --gray: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--cream); }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 16px 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 32px; }
        .navbar-brand { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .page-header { padding: 40px 0 20px; }
        .page-header h1 { font-size: 2rem; font-weight: 800; color: var(--dark); }
        .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; margin-bottom: 60px; }
        .card { background: white; border-radius: 20px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card-title { font-size: 1.3rem; font-weight: 700; color: var(--dark); margin-bottom: 24px; border-bottom: 2px solid #f3f4f6; padding-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: var(--gold-primary); }
        .payment-option { border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .payment-option:hover, .payment-option.selected { border-color: var(--gold-primary); background: var(--gold-light); }
        .payment-option input { display: none; }
        .payment-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .payment-icon { width: 40px; height: 40px; background: var(--gold-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-size: 1.2rem; }
        .payment-name { font-weight: 700; color: var(--dark); font-size: 1rem; }
        .payment-detail { font-size: 0.85rem; color: var(--gray); margin-left: 52px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--gray); }
        .summary-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--gold-primary); font-size: 1.3rem; font-weight: 800; color: var(--dark); }
        .btn-checkout { width: 100%; padding: 18px; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; margin-top: 20px; }
        .btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(212,175,55,0.4); }
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white; border-radius: 20px 20px 0 0; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="katalog.php" class="navbar-brand"><i class="bi bi-lightning-charge-fill me-2"></i>7CellX</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
            <p style="color: var(--gray);">Lengkapi data pengiriman dan pembayaran</p>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($items)): ?>
        <form method="POST" id="checkoutForm">
            <div class="checkout-grid">
                <div>
                    <div class="card">
                        <div class="card-title"><i class="bi bi-geo-alt me-2"></i>Alamat Pengiriman</div>
                        <div class="mb-3">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Jalan, No. Rumah, RT/RW, Kelurahan..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Contoh: Taruh di depan pintu">
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title"><i class="bi bi-wallet2 me-2"></i>Metode Pembayaran</div>
                        
                        <label class="payment-option" onclick="selectPayment(this, 'bca')">
                            <input type="radio" name="payment_method" value="BCA" required>
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-bank"></i></div>
                                <div class="payment-name">Transfer BCA</div>
                            </div>
                            <div class="payment-detail">Rek: 1234567890 a.n. 7CellX Store</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'mandiri')">
                            <input type="radio" name="payment_method" value="Mandiri">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-bank2"></i></div>
                                <div class="payment-name">Transfer Mandiri</div>
                            </div>
                            <div class="payment-detail">Rek: 0987654321 a.n. 7CellX Store</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'bni')">
                            <input type="radio" name="payment_method" value="BNI">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-bank"></i></div>
                                <div class="payment-name">Transfer BNI</div>
                            </div>
                            <div class="payment-detail">Rek: 1122334455 a.n. 7CellX Store</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'bri')">
                            <input type="radio" name="payment_method" value="BRI">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-bank2"></i></div>
                                <div class="payment-name">Transfer BRI</div>
                            </div>
                            <div class="payment-detail">Rek: 5566778899 a.n. 7CellX Store</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'gopay')">
                            <input type="radio" name="payment_method" value="GoPay">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-wallet"></i></div>
                                <div class="payment-name">GoPay</div>
                            </div>
                            <div class="payment-detail">No: 081234567890 a.n. 7CellX</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'ovo')">
                            <input type="radio" name="payment_method" value="OVO">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-wallet2"></i></div>
                                <div class="payment-name">OVO</div>
                            </div>
                            <div class="payment-detail">No: 081234567890 a.n. 7CellX</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'dana')">
                            <input type="radio" name="payment_method" value="DANA">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-wallet"></i></div>
                                <div class="payment-name">DANA</div>
                            </div>
                            <div class="payment-detail">No: 081234567890 a.n. 7CellX</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'shopeepay')">
                            <input type="radio" name="payment_method" value="ShopeePay">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-bag"></i></div>
                                <div class="payment-name">ShopeePay</div>
                            </div>
                            <div class="payment-detail">No: 081234567890 a.n. 7CellX</div>
                        </label>

                        <label class="payment-option" onclick="selectPayment(this, 'cod')">
                            <input type="radio" name="payment_method" value="COD">
                            <div class="payment-header">
                                <div class="payment-icon"><i class="bi bi-cash"></i></div>
                                <div class="payment-name">Bayar di Tempat (COD)</div>
                            </div>
                            <div class="payment-detail">Bayar saat barang diterima</div>
                        </label>

                        <input type="hidden" name="payment_detail" id="payment_detail">
                    </div>
                </div>

                <div>
                    <div class="card">
                        <div class="card-title"><i class="bi bi-receipt me-2"></i>Ringkasan Pesanan</div>
                        <?php foreach($items as $item): ?>
                            <div class="summary-item">
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($item['nama']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--gray);">Varian: <?php echo htmlspecialchars($item['varian']); ?> (<?php echo $item['qty']; ?>x)</div>
                                </div>
                                <div style="font-weight: 600;">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="summary-item">
                            <span>Ongkos Kirim</span>
                            <span style="color: #10b981; font-weight: 600;">Gratis</span>
                        </div>
                        <div class="summary-total">
                            <span>Total Bayar</span>
                            <span style="color: var(--gold-dark);">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>
                        <button type="button" class="btn-checkout" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="bi bi-lock me-2"></i>Bayar Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="card text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                <h3>Keranjang Kosong</h3>
                <p style="color: var(--gray); margin: 10px 0 20px;">Belum ada produk yang dipilih.</p>
                <a href="katalog.php" class="btn btn-primary">Belanja Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Konfirmasi Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Total tagihan: <strong>Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Masukkan Nominal Pembayaran</label>
                        <input type="number" id="paidAmount" class="form-control" placeholder="Contoh: <?php echo $total_harga; ?>" min="<?php echo $total_harga; ?>">
                        <small class="text-muted">Minimal: Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></small>
                    </div>
                    <div id="paymentInfo" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()" style="background: var(--gold-primary); border: none;">Konfirmasi Bayar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedPayment = '';
        
        function selectPayment(el, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
            selectedPayment = method;
            document.getElementById('payment_detail').value = el.querySelector('.payment-detail').textContent;
        }

        function processPayment() {
            const paidAmount = parseInt(document.getElementById('paidAmount').value) || 0;
            const totalAmount = <?php echo $total_harga; ?>;
            
            if (paidAmount < totalAmount) {
                alert('Pembayaran ditolak!\n\nNominal yang Anda masukkan: Rp ' + paidAmount.toLocaleString('id-ID') + '\nTotal tagihan: Rp ' + totalAmount.toLocaleString('id-ID') + '\n\nSilakan masukkan nominal yang sesuai.');
                return;
            }

            const form = document.getElementById('checkoutForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'paid_amount';
            input.value = paidAmount;
            form.appendChild(input);
            
            form.submit();
        }
    </script>
</body>
</html>