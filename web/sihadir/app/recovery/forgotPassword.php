<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$auth_file = '../auth/auth.php';
if (!file_exists($auth_file)) {
    die("Error: auth.php file not found in $auth_file");
}
include $auth_file;

// Initialize variables
$email = "";
$error_message = "";
$show_error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify'])) {
    try {
        $email = trim($_POST["email"]);
        
        if (empty($email)) {
            throw new Exception("Mohon masukkan email.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        // Check if email exists and get user data
        $stmt = $pdo->prepare("SELECT id, role, id_otp, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Email tidak terdaftar dalam sistem.");
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user['id'];
        $userRole = $user['role'];
        $idOtp = $user['id_otp'];
        $username = $user['username'];

        // Store in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['id_otp'] = $idOtp;
        $_SESSION['username'] = $username; // Menyimpan username di session
        $_SESSION['should_send_otp'] = true; // Flag untuk menandai perlu kirim OTP

        // Menonaktifkan destroy session dan melakukan redirect
        echo "<script>
            window.onbeforeunload = null;
            window.location.href = 'oneTimeVerif.php';
        </script>";
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $show_error = true;
        error_log("Forgot Password Error: " . $e->getMessage());
    }
}
?>

<!-- Script untuk handle destroy session dan redirect -->
<script>
let isRedirecting = false;

function safeRedirect(url) {
    isRedirecting = true;
    window.onbeforeunload = null;
    window.location.href = url;
}

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
    <title>Si Hadir - Verifikasi Email</title>
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
            margin-bottom: 2rem;
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
            margin-top: 1.5rem;
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
            margin-bottom: 1.5rem;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .container {
                padding: 2rem;
            }

            .title {
                font-size: 1.75rem;
            }

            .subtitle {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1 class="title">Lupa Password</h1>
            <p class="subtitle">
                Masukkan email Anda untuk menerima kode verifikasi
            </p>
        </header>

        <?php if (!empty($error_message)): ?>
            <div class="alert" id="error-alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <script>
                // Automatically hide the error message after 5 seconds
                var errorAlert = document.getElementById('error-alert');
                if (errorAlert) {
                    setTimeout(function () {
                        errorAlert.style.display = 'none';
                    }, 5000);
                }
            </script>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form">
            <div class="form-group">
                <label for="email" class="label">Email</label>
                <input type="email" id="email" name="email" class="input" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <button type="submit" name="verify" value="1" class="btn">
                <i class="fas fa-paper-plane"></i>
                <span>Kirim Kode Verifikasi</span>
            </button>
        </form>

        <div class="back-link">
            <a href="../../login.php"><i class="fas fa-arrow-left"></i> Kembali ke halaman login</a>
        </div>
    </div>
</body>

</html>