<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int) $_POST['order_id'];
    $new_status = $_POST['status'];

    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($stmt);

    header('Location: pesanan.php');
    exit;
}

$pesanan = mysqli_query($conn, "
    SELECT o.*, u.nama as customer_name, u.email
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #f4e5c2;
            --gold-dark: #aa8c2c;
            --cream: #faf8f3;
            --dark: #1a1a1a;
            --gray: #6b7280;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--cream);
            display: flex;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--gold-dark) 0%, var(--gold-primary) 100%);
            min-height: 100vh;
            padding: 30px 20px;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
        }

        .page-header {
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px 12px;
            background: var(--cream);
            font-weight: 700;
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 18px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--gray);
        }

        tr:hover td {
            background: var(--cream);
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            margin-bottom: 24px;
        }

        .modal-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: var(--dark);
            flex: 1;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-bolt"></i>
            7Cellectronic
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
            <li><a href="pesanan.php" class="active"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart me-3"></i>Kelola Pesanan</h1>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table>
                    <thead>
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
                                        <?php echo $order['id']; ?>
                                    </strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                                <td>Rp
                                    <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                                </td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span></td>
                                <td>
                                    <button class="btn btn-primary"
                                        onclick="openModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                        <i class="fas fa-edit me-2"></i>Update Status
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Status Pesanan</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div class="form-group">
                    <label class="form-label">Status Pesanan</label>
                    <select name="status" id="modalStatus" class="form-select" required>
                        <option value="pending">Pending (Menunggu Pembayaran)</option>
                        <option value="paid">Paid (Dibayar - Diproses)</option>
                        <option value="shipped">Shipped (Sedang Dikirim)</option>
                        <option value="delivered">Delivered (Selesai)</option>
                        <option value="cancelled">Cancelled (Dibatalkan)</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" name="update_status" class="btn btn-primary" style="flex: 2;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        setInterval(function () {
            location.reload();
        }, 30000);
    </script>
</body>

</html>