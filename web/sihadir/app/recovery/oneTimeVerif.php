<?php
session_start();

// Validate required session variables
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['user_id']) || 
    !isset($_SESSION['user_role']) || !isset($_SESSION['id_otp']) || !isset($_SESSION['username'])) {
    echo "<script>
        window.onbeforeunload = null;
        window.location.href = '../../login.php';
    </script>";
    exit;
}

// Include database connection
$auth_file = '../auth/auth.php';
if (!file_exists($auth_file)) {
    die("Error: auth.php file not found in $auth_file");
}
include $auth_file;

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    // Handle initial OTP send
    if (isset($_POST['initial_send_otp'])) {
        try {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));

            // Update OTP in database
            $updateStmt = $pdo->prepare("UPDATE otp_code SET otp_code = ? WHERE id = ?");
            if (!$updateStmt->execute([$otp, $_SESSION['id_otp']])) {
                throw new Exception("Gagal menyimpan kode OTP.");
            }

            // Send email
            require_once '../handler/email_recovery_handler.php';
            if (!function_exists('sendOTPEmail')) {
                throw new Exception("sendOTPEmail function not found");
            }

            if (!sendOTPEmail($_SESSION['reset_email'], $otp)) {
                throw new Exception("Gagal mengirim email OTP.");
            }

            $_SESSION['should_send_otp'] = false;
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            error_log("Initial OTP Send Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Handle OTP verification
    if (isset($_POST['verify_otp'])) {
        try {
            $otp = "";
            for ($i = 1; $i <= 6; $i++) {
                if (!isset($_POST["otp$i"])) {
                    throw new Exception("Data OTP tidak lengkap.");
                }
                $otp .= $_POST["otp$i"];
            }

            if (strlen($otp) !== 6 || !ctype_digit($otp)) {
                throw new Exception("Mohon masukkan 6 digit kode OTP.");
            }

            $stmt = $pdo->prepare("SELECT otp_code FROM otp_code WHERE id = ? AND otp_code = ?");
            $stmt->execute([$_SESSION['id_otp'], $otp]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['otp_verified'] = true;
                $redirectUrl = $_SESSION['user_role'] === 'owner' ? 'recoveryOwner.php' : 'recoveryUser.php';
                echo json_encode([
                    'success' => true,
                    'redirect' => $redirectUrl
                ]);
            } else {
                throw new Exception("Kode OTP tidak valid.");
            }

        } catch (Exception $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Handle OTP resend
    if (isset($_POST['resend_otp'])) {
        try {
            $otp = sprintf("%06d", mt_rand(0, 999999));

            $updateStmt = $pdo->prepare("UPDATE otp_code SET otp_code = ? WHERE id = ?");
            if (!$updateStmt->execute([$otp, $_SESSION['id_otp']])) {
                throw new Exception("Gagal menyimpan kode OTP baru.");
            }

            require_once '../handler/email_recovery_handler.php';
            if (!function_exists('sendOTPEmail')) {
                throw new Exception("sendOTPEmail function not found");
            }

            if (!sendOTPEmail($_SESSION['reset_email'], $otp)) {
                throw new Exception("Gagal mengirim email OTP baru.");
            }

            echo json_encode([
                'success' => true,
                'message' => 'OTP baru telah dikirim ke email Anda.'
            ]);

        } catch (Exception $e) {
            error_log("OTP Resend Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Function to safely destroy session
function destroySession() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    session_destroy();
}

// Handle session destruction on page unload
if (isset($_POST['destroy_session'])) {
    destroySession();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Verifikasi OTP</title>
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

        .otp-container {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-input {
            width: 45px;
            height: 45px;
            padding: 0;
            font-size: 1.2rem;
            text-align: center;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: var(--background);
            color: var(--text-primary);
            outline: none;
            transition: all 0.3s ease;
            -webkit-text-security: disc;
        }

        .otp-input:focus {
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

        .email-display {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
        }

        .email-display strong {
            color: var(--text-primary);
        }

        .resend-link {
            text-align: center;
            margin-top: 1rem;
        }

        .resend-link button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
        }

        .resend-link button:hover {
            color: var(--primary-light);
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

            .otp-input {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1 class="title">Verifikasi OTP</h1>
            <p class="subtitle">
                Mohon tunggu, kode OTP sedang dikirim ke email Anda...
            </p>
        </header>

        <div class="email-display">
            Email: <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong>
        </div>

        <div id="error-alert" class="alert" style="display: none;"></div>

        <form id="otpForm" class="form">
            <div class="otp-container">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" 
                           class="otp-input" 
                           name="otp<?php echo $i; ?>" 
                           maxlength="1" 
                           pattern="[0-9]" 
                           inputmode="numeric"
                           required>
                <?php endfor; ?>
            </div>

            <button type="submit" name="verify_otp" value="1" class="btn">
                <i class="fas fa-check-circle"></i>
                <span>Verifikasi OTP</span>
            </button>
        </form>

        <div class="resend-link">
            <button type="button" id="resendButton" disabled>
                Kirim ulang kode OTP (<span id="countdown">60</span>)
            </button>
        </div>

        <div class="back-link">
            <a href="forgotPassword.php"><i class="fas fa-arrow-left"></i> Kembali ke verifikasi email</a>
        </div>
    </div>

    <script>
        let isRedirecting = false;
        let countdownInterval;

        function safeRedirect(url) {
            isRedirecting = true;
            window.onbeforeunload = null;
            window.location.href = url;
        }

        function showError(message) {
            const errorAlert = document.getElementById('error-alert');
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 5000);
        }

        function updateSubtitle(text) {
            document.querySelector('.subtitle').textContent = text;
        }

        function startResendCountdown() {
            const resendButton = document.getElementById('resendButton');
            const countdownElement = document.getElementById('countdown');
            let timeLeft = 60;

            resendButton.disabled = true;
            
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            countdownInterval = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    resendButton.disabled = false;
                    resendButton.textContent = 'Kirim ulang kode OTP';
                }
            }, 1000);
        }

        // Send initial OTP
        function sendInitialOTP() {
            fetch('oneTimeVerif.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'initial_send_otp=1',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSubtitle('Masukkan 6 digit kode OTP yang telah dikirim ke email Anda');
                    startResendCountdown();
                } else {
                    showError(data.message || 'Gagal mengirim kode OTP');
                    updateSubtitle('Terjadi kesalahan saat mengirim kode OTP');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan dalam pengiriman OTP');
                updateSubtitle('Terjadi kesalahan saat mengirim kode OTP');
            });
        }

        // Auto-focus and navigation for OTP inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Send initial OTP when page loads
            sendInitialOTP();

            const inputs = document.querySelectorAll('.otp-input');
            inputs[0].focus();

            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    if (e.target.value.length === 1) {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    }
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value) {
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    }
                });
            });
        });

        // Handle form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('verify_otp', '1');
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            fetch('oneTimeVerif.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect) {
                    safeRedirect(data.redirect);
                } else if (data.message) {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan dalam proses verifikasi OTP');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
        });

        // Handle resend OTP
        document.getElementById('resendButton').addEventListener('click', function() {
            this.disabled = true;
            updateSubtitle('Mengirim ulang kode OTP...');

            fetch('oneTimeVerif.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'resend_otp=1',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSubtitle('Kode OTP baru telah dikirim ke email Anda');
                    startResendCountdown();
                } else {
                    showError(data.message || 'Gagal mengirim kode OTP baru');
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan dalam pengiriman ulang OTP');
                this.disabled = false;
            });
        });

        // Handle page unload
        window.onbeforeunload = function() {
            if (!isRedirecting) {
                fetch('../handler/destroy_session_handler.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
            }
        };
    </script>
</body>

</html>