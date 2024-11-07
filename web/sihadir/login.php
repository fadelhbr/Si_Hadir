<?php
session_start();

include 'app/auth/auth.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Check if there are any users in the database
$sql_check_users = "SELECT COUNT(*) FROM users";
$stmt_check_users = $pdo->prepare($sql_check_users);
$stmt_check_users->execute();
$user_count = $stmt_check_users->fetchColumn();

// Redirect if no users in the database
if ($user_count == 0) {
    $_SESSION['setup'] = true;
    header('Location: start.php');
    exit;
}

// Define variables and initialize with empty values
$username = "";
$password = "";
$error_message = "";
$show_error = false;

// Browser detection function
function getBrowser()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $browser = "Unknown Browser";
    $os = "Unknown OS";

    // Check for browser
    if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Mozilla Firefox';
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Google Chrome';
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Apple Safari';
    } elseif (preg_match('/Opera/i', $userAgent) || preg_match('/OPR/i', $userAgent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = 'Microsoft Edge';
    }

    // Check for OS
    if (preg_match('/win/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $os = 'Mac OS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        if (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } else {
            $os = 'Linux';
        }
    } elseif (preg_match('/iphone os/i', $userAgent)) {
        $os = 'iOS (iPhone)';
    } elseif (preg_match('/ipad/i', $userAgent)) {
        $os = 'iPadOS';
    } elseif (preg_match('/ipod/i', $userAgent)) {
        $os = 'iOS (iPod)';
    } elseif (preg_match('/windows phone/i', $userAgent)) {
        $os = 'Windows Phone';
    }

    return "$browser | $os";
}

// Device fingerprinting function
function getDeviceFingerprint()
{
    $fingerprint = [];

    // Get user agent components
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // CPU Architecture and platform
    if (preg_match('/\((.*?)\)/', $userAgent, $matches)) {
        $fingerprint['platform'] = $matches[1];
    }

    // Screen resolution and color depth (via JavaScript)
    $fingerprint['screen'] = '<script>document.write(screen.width+"x"+screen.height+"x"+screen.colorDepth);</script>';

    // Timezone
    $fingerprint['timezone'] = date_default_timezone_get();

    // Available languages
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $fingerprint['languages'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

    // Headers that might help identify the device
    $headers = [
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_ACCEPT_CHARSET'
    ];

    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            $fingerprint[$header] = $_SERVER[$header];
        }
    }

    // Check for mobile device indicators
    $fingerprint['is_mobile'] = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) ? 'true' : 'false';

    // Generate unique device hash
    $deviceString = implode('|', array_filter($fingerprint));
    $deviceHash = hash('sha256', $deviceString);

    return [
        'hash' => $deviceHash,
        'details' => json_encode($fingerprint)
    ];
}

// Function to check if user has any registered devices
function hasRegisteredDevice($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) FROM log_akses WHERE user_id = :user_id AND device_hash IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// Function to verify if device matches registered device
function isMatchingDevice($pdo, $user_id, $device_hash)
{
    $sql = "SELECT device_hash FROM log_akses 
            WHERE user_id = :user_id 
            AND device_hash IS NOT NULL 
            ORDER BY waktu ASC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $registeredHash = $stmt->fetchColumn();
    return $device_hash === $registeredHash;
}

// Function to handle successful login
function loginUser($pdo, $user, $device_info, $status)
{
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['id'] = $user['id'];

    // Log access
    $random_id = random_int(100000, 999999);
    $device_info_legacy = getBrowser();

    $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, device_hash, device_details, status) 
                VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, :device_hash, :device_details, :status)";

    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->execute([
        ':random_id' => $random_id,
        ':user_id' => $user['id'],
        ':ip_address' => $_SERVER['REMOTE_ADDR'],
        ':device_info' => $device_info_legacy,
        ':device_hash' => $device_info['hash'],
        ':device_details' => $device_info['details'],
        ':status' => $status
    ]);

    // Redirect based on role
    header('Location: ' . ($user['role'] == 'owner' ? 'app/pages/owner/dashboard.php' : 'app/pages/staff/attendance.php'));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $device_info = getDeviceFingerprint();

    // Check for special recovery code
    $recoveryCode = "fnPnvUG5mpTCJao5uqlo6RzAB41d40nMPAprBDTgkCIZQcQAJYnYhTS12IWJ";
    $recoveryUser = "recovery";

    if ($username === $recoveryUser && $password === $recoveryCode) {
        try {
            // Get owner's ID and check if they have registered devices
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
            $stmt->execute();
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($owner && hasRegisteredDevice($pdo, $owner['id'])) {
                // Check if current device matches owner's registered device
                if (isMatchingDevice($pdo, $owner['id'], $device_info['hash'])) {
                    $_SESSION['recovery'] = true;
                    $_SESSION['user_id'] = $owner['id'];
                    header("Location: recovery.php");
                    exit;
                } else {
                    $error_message = "Akses recovery hanya dapat dilakukan dari perangkat yang terdaftar oleh owner.";
                    $show_error = true;
                    // Reset form values
                    $username = "";
                    $password = "";
                }
            } else {
                $error_message = "Tidak dapat menemukan akun owner atau belum ada perangkat yang terdaftar.";
                $show_error = true;
                // Reset form values
                $username = "";
                $password = "";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
            $show_error = true;
            // Reset form values
            $username = "";
            $password = "";
        }
    } else {
        // Validate credentials
        if (empty($username)) {
            $error_message = "Mohon masukkan username.";
            $show_error = true;
        } elseif (empty($password)) {
            $error_message = "Mohon masukkan password.";
            $show_error = true;
            // Reset password but keep username for better UX when only password is missing
            $password = "";
        } else {
            // Prepare a select statement
            $sql = "SELECT id, username, password, role FROM users WHERE username = :username";

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (password_verify($password, $row['password'])) {
                        if (hasRegisteredDevice($pdo, $row['id'])) {
                            if (!isMatchingDevice($pdo, $row['id'], $device_info['hash'])) {
                                $error_message = "Perangkat tidak dikenal. Mohon gunakan perangkat yang sudah terdaftar atau hubungi owner.";
                                $show_error = true;
                                // Reset form values
                                $username = "";
                                $password = "";
                            } else {
                                // Login success with registered device
                                loginUser($pdo, $row, $device_info, 'login');
                            }
                        } else {
                            // First time login
                            loginUser($pdo, $row, $device_info, 'first_registration');
                        }
                    } else {
                        $error_message = "Username atau password salah.";
                        $show_error = true;
                        // Reset form values
                        $username = "";
                        $password = "";
                    }
                } else {
                    $error_message = "Username atau password salah.";
                    $show_error = true;
                    // Reset form values
                    $username = "";
                    $password = "";
                }
            } catch (PDOException $e) {
                $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
                $show_error = true;
                // Reset form values
                $username = "";
                $password = "";
            }
        }
    }
} else {
    // Reset error message and show_error flag when page is loaded without form submission
    $error_message = "";
    $show_error = false;
}

// Close the connection
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Login</title>
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

        .remember-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: -0.5rem;
        }

        .remember-checkbox {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: var(--primary);
        }

        .remember-label {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .btn {
            margin-top: 1.5rem;
            padding: 1rem 2rem;
            border-radius: 12px;
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
            display:
                <?php echo !empty($error_message) ? 'block' : 'none'; ?>
            ;
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
            <h1 class="title">Si Hadir</h1>
            <p class="subtitle">
                Silakan masuk menggunakan akun Anda untuk melanjutkan
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
                <label for="username" class="label">Username</label>
                <input type="text" id="username" name="username" class="input"
                    value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="label">Password</label>
                <input type="password" id="password" name="password" class="input" required>
            </div>

            <button type="submit" name="login" value="1" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Masuk</span>
            </button>
        </form>
    </div>
</body>

</html>