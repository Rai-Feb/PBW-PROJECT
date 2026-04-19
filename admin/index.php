<?php
session_start();
if (!isset($_SESSION['status_login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
include '../config/koneksi.php';

$q_prod = mysqli_query($conn, "SELECT COUNT(*) as t FROM products");
$total_produk = mysqli_fetch_assoc($q_prod)['t'];

$q_usr = mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='customer'");
$total_customer = mysqli_fetch_assoc($q_usr)['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - tokoelectro</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tk-green: #03AC0E;
            --tk-green-dark: #00880B;
            --tk-text: #31353B;
            --tk-text-muted: #8D96AA;
            --tk-border: #E5E7E9;
            --tk-bg: #FFFFFF;
            --tk-surface: #F3F4F5;
            --tk-red: #FF5C5C;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Open Sans', sans-serif;
        }

        body {
            background-color: var(--tk-surface);
            color: var(--tk-text);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background: var(--tk-bg);
            border-right: 1px solid var(--tk-border);
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 24px;
            font-weight: 800;
            color: var(--tk-green);
            border-bottom: 1px solid var(--tk-border);
            letter-spacing: -1px;
        }

        .sidebar-menu {
            padding: 24px 16px;
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--tk-text);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            transition: all 0.2s;
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .nav-item:hover {
            background: var(--tk-surface);
        }

        .nav-item.active {
            background: #E2F5ED;
            color: var(--tk-green);
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--tk-border);
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: 70px;
            background: var(--tk-bg);
            border-bottom: 1px solid var(--tk-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .avatar {
            width: 36px;
            height: 36px;
            background: var(--tk-green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .content {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--tk-bg);
            border: 1px solid var(--tk-border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .metric-title {
            font-size: 13px;
            color: var(--tk-text-muted);
            font-weight: 600;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .metric-trend {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .trend-up { color: var(--tk-green); }
        .trend-down { color: var(--tk-red); }

        .dashboard-section {
            background: var(--tk-bg);
            border: 1px solid var(--tk-border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
        }

        .table-modern {
            width: 100%;
            border-collapse: collapse;
        }

        .table-modern th {
            text-align: left;
            padding: 12px 16px;
            font-size: 13px;
            color: var(--tk-text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--tk-border);
        }

        .table-modern td {
            padding: 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--tk-border);
            font-weight: 600;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-success { background: #E2F5ED; color: var(--tk-green); }
        .status-pending { background: #FFF4CE; color: var(--tk-yellow); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">7Cellectronic</div>
        <div class="sidebar-menu">
            <a href="index.php" class="nav-item active">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Wawasan Toko
            </a>
            <a href="produk.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                Produk
            </a>
            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                Pesanan
            </a>
        </div>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item" style="color: var(--tk-red);">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Keluar
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-title">Ringkasan Statistik</div>
            <div class="topbar-actions">
                <div class="admin-profile">
                    <div class="avatar">R</div>
                    <?= $_SESSION['nama']; ?>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Pendapatan Kotor</span>
                        <div class="metric-icon" style="background: #E2F5ED; color: var(--tk-green);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                    </div>
                    <div class="metric-value">Rp 45.2M</div>
                    <div class="metric-trend trend-up">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                        +12.5% bulan ini
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Produk Aktif</span>
                        <div class="metric-icon" style="background: #FFF4CE; color: var(--tk-yellow);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        </div>
                    </div>
                    <div class="metric-value"><?= $total_produk; ?></div>
                    <div class="metric-trend trend-up">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                        +2 produk baru
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Total Pelanggan</span>
                        <div class="metric-icon" style="background: #F3F4F5; color: var(--tk-text);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="metric-value"><?= $total_customer; ?></div>
                    <div class="metric-trend trend-down">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                        -1.2% bulan ini
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <div class="section-title">Pesanan Terbaru</div>
                    <a href="#" style="color: var(--tk-green); text-decoration: none; font-size: 14px; font-weight: 600;">Lihat Semua</a>
                </div>
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total Belanja</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="color: var(--tk-green);">INV/202604/001</td>
                            <td>Budi Santoso</td>
                            <td style="color: var(--tk-text-muted); font-weight: 400;">16 Apr 2026</td>
                            <td>Rp 15.000.000</td>
                            <td><span class="status-badge status-success">Selesai</span></td>
                        </tr>
                        <tr>
                            <td style="color: var(--tk-green);">INV/202604/002</td>
                            <td>Andi Pratama</td>
                            <td style="color: var(--tk-text-muted); font-weight: 400;">15 Apr 2026</td>
                            <td>Rp 450.000</td>
                            <td><span class="status-badge status-pending">Diproses</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>