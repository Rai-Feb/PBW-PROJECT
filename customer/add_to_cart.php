<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $qty = (int)($_POST['qty'] ?? 1);
    
    if (!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }
    
    if (isset($_SESSION['keranjang'][$product_id])) {
        $_SESSION['keranjang'][$product_id] += $qty;
    } else {
        $_SESSION['keranjang'][$product_id] = $qty;
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'keranjang.php'));
    exit;
}

header('Location: katalog.php');
exit;
?>