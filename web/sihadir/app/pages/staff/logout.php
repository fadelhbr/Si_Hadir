<?php
session_start();

// Pastikan file koneksi database di-include
require_once '../../../app/auth/auth.php';

// Function untuk mencatat error ke file log
function logError($message) {
    $log_file = 'error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    error_log($log_message, 3, $log_file);
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Import the device fingerprinting functions
function getBrowser() {
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

function getDeviceFingerprint() {
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

$error_message = '';
$success_message = '';

// Process logout if confirmation received
if (isset($_POST['logout']) && $_POST['logout'] == 'yes') {
    try {
        // Verifikasi koneksi database
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new Exception("Koneksi database tidak tersedia");
        }

        // Get user ID from the session
        if (!isset($_SESSION['id'])) {
            throw new Exception("User ID tidak ditemukan dalam session");
        }
        
        $user_id = $_SESSION['id'];
        
        // Get device information
        $device_info_legacy = getBrowser();
        $device_info = getDeviceFingerprint();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Generate random ID with verification
        do {
            $random_id = random_int(100000, 999999);
            $check_sql = "SELECT COUNT(*) FROM log_akses WHERE id = :random_id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->bindParam(':random_id', $random_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $exists = $check_stmt->fetchColumn();
        } while ($exists > 0);

        // Prepare log entry
        $sql_log = "INSERT INTO log_akses (
            id, user_id, waktu, ip_address, device_info, device_hash, 
            device_details, status
        ) VALUES (
            :random_id, :user_id, NOW(), :ip_address, :device_info,
            :device_hash, :device_details, 'logout'
        )";

        $stmt_log = $pdo->prepare($sql_log);
        
        // Execute dengan explicit parameter binding
        $result = $stmt_log->execute([
            ':random_id' => $random_id,
            ':user_id' => $user_id,  // Pastikan user_id diambil dari session
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':device_info' => $device_info_legacy,  // Pastikan ini tidak null
            ':device_hash' => $device_info['hash'],  // Pastikan ini tidak null
            ':device_details' => $device_info['details']  // Pastikan ini tidak null
        ]);
        
        if (!$result) {
            throw new Exception("Gagal mencatat log logout");
        }

        // Commit transaction
        $pdo->commit();

        // Clear session data
        session_unset();
        session_destroy();
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Redirect to login page
        header("Location: ../../../login.php");
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        logError("Database error during logout: " . $e->getMessage());
        $error_message = "Database error: " . $e->getMessage(); // Tampilkan pesan kesalahan yang lebih rinci
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        logError("General error during logout: " . $e->getMessage());
        $error_message = "Terjadi kesalahan saat proses logout: " . $e->getMessage();
    }
}

// Close the database connection safely
if (isset($pdo)) {
    unset($pdo);
}

// HTML tetap sama seperti sebelumnya, tapi tambahkan display error message
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Logout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Istok+Web&display=swap" rel="stylesheet">
    <style>
        /* Add your CSS styling here */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Istok Web', sans-serif;
            font-size: 14px;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .logout-container {
            background-color: #ffffff;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
            text-align: center;
        }

        .logout-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 32px;
            color: rgba(0, 0, 0, 0.8);
            margin-bottom: 10px;
        }

        .logout-message {
            font-size: 16px;
            color: #555;
            margin-bottom: 35px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .logout-button, .cancel-button {
            width: 150px;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-button {
            background-color: #28a745;
            color: #fff;
        }

        .logout-button:hover {
            background-color: #218838;
        }

        .cancel-button {
            background-color: #dc3545;
            color: #fff;
        }

        .cancel-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="logout-container">
    <h1 class="logout-title">Konfirmasi</h1>
    <p class="logout-message">Apakah Anda yakin ingin keluar?</p>

    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="button-group">
        <form action="" method="post">
            <input type="hidden" name="logout" value="yes">
            <button type="submit" class="logout-button">Ya, Logout</button>
        </form>
        <a href="pengumumanKaryawan.php">
            <button class="cancel-button">Tidak, Kembali</button>
        </a>
    </div>
</div>


</body>
</html>
