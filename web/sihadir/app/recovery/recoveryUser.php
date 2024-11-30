<?php
session_start();
require_once '../auth/auth.php';

if (
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'karyawan' || 
    !isset($_SESSION['reset_email']) || !isset($_SESSION['user_id']) || 
    !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || 
    !isset($_SESSION['username']) // Validasi username
) {
    echo "<script>
        window.onbeforeunload = null;
        window.location.href = '../../login.php';
    </script>";
    exit;
}

$username = htmlspecialchars($_SESSION['username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'karyawan'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('User tidak ditemukan atau bukan karyawan');
        }

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Semua field harus diisi');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Password tidak cocok');
        }

        if (strlen($newPassword) < 8) {
            throw new Exception('Password minimal 8 karakter');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateSuccess = $updateStmt->execute([$hashedPassword, $userId]);

        if (!$updateSuccess) {
            throw new Exception('Gagal mengupdate password');
        }

        $pdo->commit();
        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login kembali.',
            'redirect' => '../../login.php'
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>

<script>
let isRedirecting = false;

// Fungsi untuk redirect yang aman
function safeRedirect(url) {
    isRedirecting = true;
    window.onbeforeunload = null;
    window.location.href = url;
}

// Handle response dari form submit
function handleFormResponse(response) {
    if (response.success && response.redirect) {
        safeRedirect(response.redirect);
    }
    // Handle pesan error jika ada
    if (response.message) {
        alert(response.message);
    }
}

// Modify onbeforeunload
window.onbeforeunload = function() {
    if (!isRedirecting) {
        fetch('../handler/destroy_session_handler.php', {
            method: 'POST',
            credentials: 'same-origin'
        });
    }
};
</script>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
    <link rel="icon" type="image/x-icon" href="assets/icon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #60a5fa;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --background: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.7);
            --hover-bg: rgba(255, 255, 255, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 3rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .label {
            font-size: 0.95rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .input {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: var(--background);
            font-size: 1rem;
            color: var(--text-primary);
            outline: none;
            transition: all 0.3s ease;
        }

        .input:focus {
            border-color: var(--primary);
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        .btn:hover {
            background: var(--primary-light);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            margin-top: 1rem;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1 class="title">Halo, <?= $username ?>!</h1>
            <p class="subtitle">
                Ini adalah menu recovery password Anda. Masukkan password baru dan konfirmasi untuk melanjutkan.
            </p>
        </header>

        <div id="alert" class="alert"></div>

        <form id="recoveryForm" method="POST" class="form">
            <div class="form-group">
                <label for="new_password" class="label">Password Baru</label>
                <input type="password" name="new_password" id="new_password" class="input" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password" class="label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="input" required minlength="8">
            </div>
            <button type="submit" id="submitBtn" class="btn">
                <i class="fas fa-lock"></i>
                <span>Reset Password</span>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('recoveryForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = document.getElementById('recoveryForm');
            const submitBtn = document.getElementById('submitBtn');
            const alert = document.getElementById('alert');

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword.length < 8) {
                alert.textContent = 'Password minimal 8 karakter';
                alert.style.display = 'block';
                alert.className = 'alert alert-error';
                return;
            }

            if (newPassword !== confirmPassword) {
                alert.textContent = 'Password tidak cocok';
                alert.style.display = 'block';
                alert.className = 'alert alert-error';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Processing...';

            // Create FormData object
            const formData = new FormData(form);

            // Send POST request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    alert.textContent = data.message;
                    alert.style.display = 'block';
                    alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');

                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-lock"></i> <span>Reset Password</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert.textContent = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                    alert.style.display = 'block';
                    alert.className = 'alert alert-error';

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> <span>Reset Password</span>';
                });
        });
    </script>
</body>

</html>