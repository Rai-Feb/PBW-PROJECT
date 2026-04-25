<?php
session_start();
require_once '../config/koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$customers = mysqli_query($conn, "SELECT DISTINCT u.id, u.nama, u.email FROM users u JOIN chats c ON u.id = c.user_id ORDER BY (SELECT MAX(created_at) FROM chats WHERE user_id = u.id) DESC");
$selected_id = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - 7Cellectronic</title>
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
        }

        .sidebar {
            width: 320px;
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-list {
            flex: 1;
            overflow-y: auto;
        }

        .customer-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-item:hover,
        .customer-item.active {
            background: var(--gold-light);
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark);
            font-weight: 700;
        }

        .customer-info h4 {
            font-size: 0.95rem;
            color: var(--dark);
        }

        .customer-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--cream);
        }

        .chat-header {
            background: white;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .message.admin {
            background: var(--gold-primary);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message.customer {
            background: white;
            color: var(--dark);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .message .time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 4px;
            display: block;
        }

        .chat-input {
            background: white;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }

        .chat-input input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            outline: none;
        }

        .chat-input input:focus {
            border-color: var(--gold-primary);
        }

        .chat-input button {
            padding: 0 20px;
            background: var(--gold-dark);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray);
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header"><i class="fas fa-comments" style="color: var(--gold-primary);"></i> Pesan Customer
        </div>
        <div class="customer-list">
            <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                <a href="?id=<?php echo $c['id']; ?>"
                    class="customer-item <?php echo $selected_id == $c['id'] ? 'active' : ''; ?>">
                    <div class="customer-avatar">
                        <?php echo strtoupper(substr($c['nama'], 0, 1)); ?>
                    </div>
                    <div class="customer-info">
                        <h4>
                            <?php echo htmlspecialchars($c['nama']); ?>
                        </h4>
                        <p>
                            <?php echo htmlspecialchars($c['email']); ?>
                        </p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="chat-area">
        <?php if ($selected_id > 0):
            $cust = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id = $selected_id"));
            ?>
            <div class="chat-header">
                <div class="customer-avatar">
                    <?php echo strtoupper(substr($cust['nama'], 0, 1)); ?>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; color: var(--dark);">
                        <?php echo htmlspecialchars($cust['nama']); ?>
                    </h3>
                    <span style="font-size: 0.85rem; color: #10b981;"><i class="fas fa-circle"
                            style="font-size: 0.5rem;"></i> Active</span>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages"></div>

            <form class="chat-input" id="chatForm">
                <input type="text" id="msgInput" placeholder="Balas pesan..." autocomplete="off" required>
                <button type="submit">Kirim</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd; margin-bottom: 15px;"></i>
                <h3>Pilih Customer</h3>
                <p>Klik nama customer di sidebar untuk memulai chat</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($selected_id > 0): ?>
        <script>
            const chatMessages = document.getElementById('chatMessages');
            const chatForm = document.getElementById('chatForm');
            const msgInput = document.getElementById('msgInput');
            const targetId = <?php echo $selected_id; ?>;
            let lastCount = 0;

            function loadChat() {
                fetch(`../chat_api.php?action=fetch&target_id=${targetId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length !== lastCount) {
                            chatMessages.innerHTML = '';
                            data.forEach(msg => {
                                const div = document.createElement('div');
                                div.className = `message ${msg.sender_role}`;
                                div.innerHTML = `${msg.message} <span class="time">${new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>`;
                                chatMessages.appendChild(div);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            lastCount = data.length;
                        }
                    });
            }

            chatForm.addEventListener('submit', e => {
                e.preventDefault();
                const msg = msgInput.value.trim();
                if (!msg) return;
                fetch('../chat_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `message=${encodeURIComponent(msg)}&target_id=${targetId}`
                }).then(() => { msgInput.value = ''; loadChat(); });
            });

            loadChat();
            setInterval(loadChat, 3000);
        </script>
    <?php endif; ?>
</body>

</html>