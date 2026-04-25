<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../customer/katalog.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'customer')");
        mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $hashed_password);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: login.php');
            exit;
        } else {
            $error = "Email sudah terdaftar!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .login-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header h1 i {
            color: #00d4ff;
        }

        .login-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1a1a2e;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .input-group input {
            padding: 14px 20px 14px 45px;
            width: 100%;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
        }

        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="login-header">
            <h1><i class="fas fa-bolt"></i> 7Cellectronic</h1>
            <p>Buat akun baru untuk berbelanja</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama" placeholder="Nama lengkap" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="nama@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Ulangi password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-user-plus"></i> Daftar
            </button>
        </form>

        <div class="login-footer">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>

</html>