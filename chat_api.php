<?php
session_start();
require_once 'config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($action === 'fetch') {
    $target_id = ($role === 'admin') ? (int) ($_GET['target_id'] ?? 0) : $user_id;
    if ($target_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id, sender_role, message, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt, "i", $target_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    exit;
}

if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    $target_id = ($role === 'admin') ? (int) ($_POST['target_id'] ?? 0) : $user_id;

    if (empty($message) || $target_id <= 0) {
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO chats (user_id, sender_role, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $target_id, $role, $message);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>