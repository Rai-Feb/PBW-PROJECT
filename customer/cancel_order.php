<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    $check = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id AND user_id = $user_id");
    $order = mysqli_fetch_assoc($check);
    
    if ($order && $order['status'] == 'pending') {
        $details = mysqli_query($conn, "SELECT product_id, qty FROM order_details WHERE order_id = $order_id");
        
        while ($item = mysqli_fetch_assoc($details)) {
            mysqli_query($conn, "UPDATE products SET stok = stok + {$item['qty']} WHERE id = {$item['product_id']}");
        }
        
        mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
        
        $_SESSION['success'] = 'Pesanan berhasil dibatalkan';
    }
}

header('Location: pesanan.php');
exit;
?>