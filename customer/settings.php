<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT username, profile_picture, nama FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);

    if (empty($new_username)) {
        $error = "Username tidak boleh kosong!";
    } else {
        $upload_path = '';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_filename)) {
                    if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                        unlink($upload_dir . $user['profile_picture']);
                    }
                    $upload_path = $new_filename;
                } else {
                    $error = "Gagal upload gambar.";
                }
            } else {
                $error = "Format gambar tidak didukung.";
            }
        }

        if (empty($error)) {
            $stmt_update = mysqli_prepare($conn, "UPDATE users SET username = ?, profile_picture = ? WHERE id = ?");
            $pic_to_save = $upload_path !== '' ? $upload_path : $user['profile_picture'];
            mysqli_stmt_bind_param($stmt_update, "ssi", $new_username, $pic_to_save, $user_id);
            mysqli_stmt_execute($stmt_update);

            $_SESSION['username'] = $new_username;
            $_SESSION['profile_picture'] = $pic_to_save;
            $success = "Profil berhasil diperbarui!";
            $user['username'] = $new_username;
            $user['profile_picture'] = $pic_to_save;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Profil - 7CellX</title>
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
            min-height: 100vh;
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
            font-weight: 900;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
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

        .nav-menu a:hover {
            color: var(--gold-primary);
        }

        .settings-layout {
            flex: 1;
            padding: 40px 0;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .settings-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .settings-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gold-light);
            margin: 0 auto 20px;
            display: block;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gold-light);
            color: var(--gold-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            margin: 0 auto 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .btn-save {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--gold-primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="katalog.php" class="navbar-brand"><i class="bi bi-lightning-charge-fill me-2"></i>7CellX</a>
            <ul class="nav-menu">
                <li><a href="katalog.php"><i class="bi bi-store"></i> Katalog</a></li>
                <li><a href="keranjang.php"><i class="bi bi-cart"></i> Keranjang</a></li>
                <li><a href="pesanan.php"><i class="bi bi-box"></i> Pesanan</a></li>
                <li><a href="chat.php"><i class="bi bi-chat-dots"></i> Chat</a></li>
                <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="settings-layout">
        <div class="settings-card">
            <div class="settings-header">
                <h1>Pengaturan Profil</h1>
                <p style="color: var(--gray);">Ubah username dan foto profil Anda</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="text-align: center; margin-bottom: 24px;">
                    <?php if (!empty($user['profile_picture']) && file_exists('../uploads/profiles/' . $user['profile_picture'])): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>"
                            class="avatar-preview" id="previewImg">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="previewPlaceholder">
                            <?php echo strtoupper(substr($user['username'] ?? $user['nama'], 0, 1)); ?>
                        </div>
                        <img src="" class="avatar-preview" id="previewImg" style="display: none;">
                    <?php endif; ?>
                    <label for="profile_pic"
                        style="display: inline-block; padding: 8px 16px; background: var(--cream); border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--gold-dark); margin-top: 10px;">
                        <i class="bi bi-camera"></i> Ganti Foto
                    </label>
                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" style="display: none;"
                        onchange="previewFile(this)">
                </div>

                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                    value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Masukkan username"
                    required>

                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-2"></i>Simpan Perubahan</button>
            </form>
            <a href="katalog.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Katalog</a>
        </div>
    </div>

    <script>
        function previewFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById('previewImg');
                    const placeholder = document.getElementById('previewPlaceholder');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>