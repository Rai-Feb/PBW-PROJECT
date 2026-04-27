<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$customers = mysqli_query($conn, "SELECT DISTINCT u.id, u.nama, u.email, u.is_online, u.last_seen FROM users u JOIN chats c ON u.id = c.user_id WHERE u.role = 'customer' ORDER BY u.is_online DESC, (SELECT MAX(created_at) FROM chats WHERE user_id = u.id) DESC");
$selected_id = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - 7CellX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            font-family: 'Montserrat', sans-serif;
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
            font-weight: 900;
            font-size: 1.2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header i {
            color: var(--gold-primary);
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
            text-decoration: none;
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
            position: relative;
        }

        .customer-avatar .online-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
        }

        .customer-info h4 {
            font-size: 0.95rem;
            color: var(--dark);
            font-weight: 600;
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

        .chat-header .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark);
            font-weight: 700;
        }

        .chat-header h3 {
            font-size: 1.1rem;
            color: var(--dark);
            font-weight: 700;
        }

        .chat-header .status {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-header .status.online {
            color: #10b981;
        }

        .chat-header .status.offline {
            color: #ef4444;
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

        .chat-input button:hover {
            background: var(--gold-primary);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-chat-dots"></i>
            Pesan Customer
        </div>
        <div class="customer-list">
            <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                <a href="?id=<?php echo $c['id']; ?>"
                    class="customer-item <?php echo $selected_id == $c['id'] ? 'active' : ''; ?>">
                    <div class="customer-avatar">
                        <?php echo strtoupper(substr($c['nama'], 0, 1)); ?>
                        <?php if ($c['is_online'] == 1): ?>
                            <span class="online-dot"></span>
                        <?php endif; ?>
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
            $cust = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, is_online, last_seen FROM users WHERE id = $selected_id"));
            ?>
            <div class="chat-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($cust['nama'], 0, 1)); ?>
                </div>
                <div>
                    <h3>
                        <?php echo htmlspecialchars($cust['nama']); ?>
                    </h3>
                    <span class="status <?php echo $cust['is_online'] == 1 ? 'online' : 'offline'; ?>" id="customerStatus">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        <?php echo $cust['is_online'] == 1 ? 'Online' : 'Offline'; ?>
                    </span>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages"></div>

            <form class="chat-input" id="chatForm">
                <input type="text" id="msgInput" placeholder="Balas pesan..." autocomplete="off" required>
                <button type="submit">Kirim</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
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
            const customerStatus = document.getElementById('customerStatus');
            const targetId = <?php echo $selected_id; ?>;
            let lastCount = 0;
            let isActive = 1;

            function formatTime(dateStr) {
                const date = new Date(dateStr);
                const options = {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                return date.toLocaleDateString('id-ID', options);
            }

            function loadChat() {
                fetch('../chat_api.php?action=fetch&target_id=' + targetId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length !== lastCount) {
                            chatMessages.innerHTML = '';
                            data.forEach(msg => {
                                const div = document.createElement('div');
                                div.className = 'message ' + msg.sender_role;
                                div.innerHTML = msg.message + '<span class="time">' + formatTime(msg.created_at) + '</span>';
                                chatMessages.appendChild(div);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            lastCount = data.length;
                        }
                    });
            }

            function checkCustomerStatus() {
                fetch('../chat_api.php?action=check_status&target_id=' + targetId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.is_online == 1) {
                            customerStatus.className = 'status online';
                            customerStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online';
                        } else {
                            customerStatus.className = 'status offline';
                            customerStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Offline';
                        }
                    });
            }

            function updatePresence() {
                fetch('../chat_api.php?action=status&active=' + isActive);
            }

            chatForm.addEventListener('submit', e => {
                e.preventDefault();
                const msg = msgInput.value.trim();
                if (!msg) return;
                fetch('../chat_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message=' + encodeURIComponent(msg) + '&target_id=' + targetId
                }).then(() => {
                    msgInput.value = '';
                    loadChat();
                });
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    isActive = 0;
                } else {
                    isActive = 1;
                }
                updatePresence();
            });

            loadChat();
            checkCustomerStatus();
            updatePresence();
            setInterval(loadChat, 3000);
            setInterval(checkCustomerStatus, 5000);
            setInterval(updatePresence, 10000);
        </script>
    <?php endif; ?>
</body>

</html>