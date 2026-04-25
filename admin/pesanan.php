<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Proses update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int) $_POST['order_id'];
    $new_status = $_POST['status'];

    // Update status di database
    $query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($stmt);

    // Kirim notifikasi ke customer (simulasi)
    $_SESSION['success'] = "Pesanan #$order_id berhasil diupdate menjadi <strong>" . ucfirst($new_status) . "</strong>";
    header("Location: pesanan.php");
    exit;
}

// Ambil semua pesanan
$pesanan = mysqli_query($conn, "
    SELECT o.*, u.nama as customer_name, u.email
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            padding: 30px;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .badge-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3 text-center border-bottom border-white border-opacity-25">
                    <h4 class="fw-bold mb-0"><i class="fas fa-mobile-alt me-2"></i>7Cellectronic</h4>
                </div>
                <nav class="nav flex-column mt-3">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a class="nav-link" href="produk.php"><i class="fas fa-box"></i> Kelola Produk</a>
                    <a class="nav-link active" href="pesanan.php"><i class="fas fa-shopping-cart"></i> Kelola
                        Pesanan</a>
                    <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i>
                        Logout</a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="fw-bold mb-4"><i class="fas fa-shopping-cart me-2"></i>Kelola Pesanan</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="content-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($pesanan)): ?>
                                    <tr>
                                        <td><strong>#
                                                <?= $order['id'] ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td>Rp
                                            <?= number_format($order['total_harga'], 0, ',', '.') ?>
                                        </td>
                                        <td><span class="badge-status status-<?= $order['status'] ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#modalStatus<?= $order['id'] ?>">
                                                <i class="fas fa-edit me-1"></i>Update Status
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Update Status -->
                                    <div class="modal fade" id="modalStatus<?= $order['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Status Pesanan #
                                                            <?= $order['id'] ?>
                                                        </h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Pesanan</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending (Menunggu Pembayaran)
                                                                </option>
                                                                <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>Paid (Dibayar - Diproses)</option>
                                                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped (Sedang Dikirim)</option>
                                                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered (Selesai)</option>
                                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled (Dibatalkan)</option>
                                                            </select>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>Customer akan mendapat
                                                            notifikasi status pesanan ini
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_status"
                                                            class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>