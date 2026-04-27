<?php
// customer/checkout.php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil keranjang dari session (atau nanti bisa dari database)
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    redirect('katalog.php?error=keranjang+kosong');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token tidak valid.";
    } else {
        $metode_pembayaran = $_POST['payment_method']; // 'cod' atau 'transfer'
        $total = 0;
        // Hitung total dulu
        foreach ($cart as $id => $qty) {
            $stmt = mysqli_prepare($conn, "SELECT harga, stok FROM products WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($res);
            if ($product && $product['stok'] >= $qty) {
                $total += $product['harga'] * $qty;
            } else {
                $error = "Stok produk tidak mencukupi.";
                break;
            }
        }
        if (!$error) {
            // Mulai transaksi untuk menjaga konsistensi
            mysqli_begin_transaction($conn);
            try {
                // Insert order
                $status = ($metode_pembayaran == 'cod') ? 'pending' : 'menunggu_pembayaran';
                $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total, status, payment_method, created_at) VALUES (?, ?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, "idss", $user_id, $total, $status, $metode_pembayaran);
                mysqli_stmt_execute($stmt);
                $order_id = mysqli_insert_id($conn);

                // Insert order items dan kurangi stok dengan aman
                foreach ($cart as $id => $qty) {
                    // Ambil harga per produk
                    $stmt2 = mysqli_prepare($conn, "SELECT harga, stok FROM products WHERE id = ? FOR UPDATE");
                    mysqli_stmt_bind_param($stmt2, "i", $id);
                    mysqli_stmt_execute($stmt2);
                    $res2 = mysqli_stmt_get_result($stmt2);
                    $product = mysqli_fetch_assoc($res2);
                    if ($product['stok'] < $qty) {
                        throw new Exception("Stok produk id $id tidak cukup");
                    }
                    $subtotal = $product['harga'] * $qty;
                    // Insert order_item
                    $stmt3 = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt3, "iiid", $order_id, $id, $qty, $product['harga']);
                    mysqli_stmt_execute($stmt3);
                    // Update stok dengan kondisi stok >= qty
                    $stmt4 = mysqli_prepare($conn, "UPDATE products SET stok = stok - ? WHERE id = ? AND stok >= ?");
                    mysqli_stmt_bind_param($stmt4, "iii", $qty, $id, $qty);
                    mysqli_stmt_execute($stmt4);
                    if (mysqli_affected_rows($conn) == 0) {
                        throw new Exception("Gagal update stok untuk produk id $id");
                    }
                }
                mysqli_commit($conn);
                // Kosongkan keranjang session
                $_SESSION['cart'] = [];
                $success = "Pesanan berhasil! Order ID: $order_id";
                // Arahkan ke invoice atau halaman sukses
                header("refresh:3;url=pesanan.php");
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Gagal checkout: " . $e->getMessage();
            }
        }
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Checkout</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?> <a href="pesanan.php">Lihat pesanan</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="mb-3">
                    <label>Metode Pembayaran</label><br>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" value="cod" checked> COD (Bayar
                        di Tempat)
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" value="transfer"> Transfer Bank
                        (upload bukti nanti)
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Konfirmasi Pesanan</button>
                <a href="keranjang.php" class="btn btn-secondary">Kembali ke Keranjang</a>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>