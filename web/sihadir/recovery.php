<?php
session_start();
require_once 'app/auth/auth.php';

// Check if user is logged in and has recovery access
if (!isset($_SESSION['recovery']) || !isset($_SESSION['user_id']) || $_SESSION['recovery'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userId = $_SESSION['user_id'];

    try {
        // Validate passwords
        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Semua field harus diisi');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Password tidak cocok');
        }

        if (strlen($newPassword) < 8) {
            throw new Exception('Password minimal 8 karakter');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Check if user exists and is an owner
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'owner'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('User tidak ditemukan atau bukan owner');
        }

        // Delete from log_akses
        $deleteStmt = $pdo->prepare("DELETE FROM log_akses WHERE user_id = ?");
        $deleteStmt->execute([$userId]);

        // Update password in users table
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateSuccess = $updateStmt->execute([$hashedPassword, $userId]);

        if (!$updateSuccess) {
            throw new Exception('Gagal mengupdate password');
        }

        // Commit transaction
        $pdo->commit();

        // Clear recovery session
        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login kembali.'
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
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
    <link rel="icon" type="image/x-icon" href="assets/icon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="title">Password Recovery</h1>
            <p class="subtitle">
                Masukkan password yang baru dan konfirmasi untuk mereset password.
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
    document.getElementById('recoveryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = document.getElementById('submitBtn');
        const alert = document.getElementById('alert');
        
        // Get password values
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Client-side validation
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
        
        // Disable button and show loading state
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
                // Reset form
                form.reset();
                // Redirect to login page after successful update
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                // Re-enable button if there's an error
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> <span>Reset Password</span>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert.textContent = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            alert.style.display = 'block';
            alert.className = 'alert alert-error';
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock"></i> <span>Reset Password</span>';
        });
    });
    </script>
</body>
</html>