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
    if ($role === 'admin') {
        $target_id = (int) ($_GET['target_id'] ?? 0);
        if ($target_id <= 0) {
            echo json_encode([]);
            exit;
        }
        $stmt = mysqli_prepare($conn, "SELECT id, sender_role, message, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
        mysqli_stmt_bind_param($stmt, "i", $target_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, sender_role, message, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    }
    exit;
}

if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');

    if ($role === 'admin') {
        $target_id = (int) ($_POST['target_id'] ?? 0);
        if (empty($message) || $target_id <= 0) {
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO chats (user_id, sender_role, message) VALUES (?, 'admin', ?)");
        mysqli_stmt_bind_param($stmt, "is", $target_id, $message);
    } else {
        if (empty($message)) {
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO chats (user_id, sender_role, message) VALUES (?, 'customer', ?)");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
    }

    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'status') {
    $is_active = isset($_GET['active']) ? (int) $_GET['active'] : 0;
    $last_seen = date('Y-m-d H:i:s');

    if ($role === 'admin') {
        mysqli_query($conn, "UPDATE users SET last_seen = '$last_seen', is_online = $is_active WHERE id = $user_id");
    } else {
        mysqli_query($conn, "UPDATE users SET last_seen = '$last_seen', is_online = $is_active WHERE id = $user_id");
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check_status') {
    if ($role === 'admin') {
        $target_id = (int) ($_GET['target_id'] ?? 0);
        $result = mysqli_query($conn, "SELECT is_online, last_seen FROM users WHERE id = $target_id");
        $data = mysqli_fetch_assoc($result);
        echo json_encode($data);
    } else {
        echo json_encode(['is_online' => 1, 'last_seen' => date('Y-m-d H:i:s')]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>