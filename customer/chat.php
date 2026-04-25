<?php
session_start();
require_once '../config/koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - 7Cellectronic</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--cream);
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 16px 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            gap: 24px;
            list-style: none;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--gold-primary);
        }

        .chat-layout {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .chat-header {
            background: white;
            padding: 20px;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gold-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-header h2 {
            font-size: 1.2rem;
            color: var(--dark);
        }

        .chat-header span {
            font-size: 0.85rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-messages {
            flex: 1;
            background: white;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
        }

        .message.admin {
            background: var(--cream);
            color: var(--dark);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message.customer {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message .time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 4px;
            display: block;
        }

        .chat-input {
            background: white;
            padding: 16px;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 12px;
        }

        .chat-input input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
        }

        .chat-input input:focus {
            border-color: var(--gold-primary);
        }

        .chat-input button {
            padding: 0 24px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .chat-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.4);
        }

        .empty-chat {
            text-align: center;
            color: var(--gray);
            margin: auto;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="katalog.php" class="navbar-brand"><i class="fas fa-bolt"></i> 7Cellectronic</a>
            <ul class="nav-menu">
                <li><a href="katalog.php">Katalog</a></li>
                <li><a href="keranjang.php">Keranjang</a></li>
                <li><a href="pesanan.php">Pesanan</a></li>
                <li><a href="chat.php" class="active"><i class="fas fa-comments"></i> Chat</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="chat-layout">
        <div class="chat-header">
            <div
                style="width: 50px; height: 50px; border-radius: 50%; background: var(--gold-light); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--gold-dark);">
                <i class="fas fa-headset"></i></div>
            <div>
                <h2>Customer Support</h2>
                <span><i class="fas fa-circle" style="font-size: 0.5rem;"></i> Online</span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="empty-chat">
                <i class="fas fa-comments" style="font-size: 3rem; color: #ddd; margin-bottom: 10px;"></i>
                <p>Mulai percakapan dengan admin</p>
            </div>
        </div>

        <form class="chat-input" id="chatForm">
            <input type="text" id="msgInput" placeholder="Ketik pesan..." autocomplete="off" required>
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const msgInput = document.getElementById('msgInput');
        let lastMsgCount = 0;

        function fetchMessages() {
            fetch('../chat_api.php?action=fetch')
                .then(r => r.json())
                .then(data => {
                    if (data.length !== lastMsgCount) {
                        renderMessages(data);
                        lastMsgCount = data.length;
                    }
                });
        }

        function renderMessages(messages) {
            chatMessages.innerHTML = '';
            if (messages.length === 0) {
                chatMessages.innerHTML = '<div class="empty-chat"><i class="fas fa-comments" style="font-size: 3rem; color: #ddd; margin-bottom: 10px;"></i><p>Mulai percakapan dengan admin</p></div>';
                return;
            }
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message ${msg.sender_role}`;
                div.innerHTML = `${msg.message} <span class="time">${new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>`;
                chatMessages.appendChild(div);
            });
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            const msg = msgInput.value.trim();
            if (!msg) return;

            fetch('../chat_api.php?action=send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `message=${encodeURIComponent(msg)}`
            }).then(() => {
                msgInput.value = '';
                fetchMessages();
            });
        });

        fetchMessages();
        setInterval(fetchMessages, 3000);
    </script>
</body>

</html>