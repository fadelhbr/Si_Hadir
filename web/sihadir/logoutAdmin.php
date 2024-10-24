<?php
// Include the authentication file to connect to the database
include 'auth/auth.php'; // Ensure this file connects to the database

// Set timezone
date_default_timezone_set('Asia/Jakarta'); // Adjust to your timezone

// Function to get browser and OS information
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
        // Check if it's Android, since it reports as Linux
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

// Proses logout jika konfirmasi diterima
if (isset($_POST['logout']) && $_POST['logout'] == 'yes') {
    session_start();

    // Get user ID from the session
    $user_id = $_SESSION['id']; // Using the session variable set during login
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $device_info = getBrowser(); // Get browser and OS information
    $status = 'logout';
    $waktu = date('Y-m-d H:i:s');

    // Generate a unique random 6-digit integer ID for logging
    do {
        $random_id = random_int(100000, 999999);
        
        // Check if the random ID already exists
        $check_sql = "SELECT COUNT(*) FROM log_akses WHERE id = :random_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':random_id', $random_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $exists = $check_stmt->fetchColumn();
    } while ($exists > 0); // Repeat until a unique ID is found

    try {
        // Insert log into log_akses table using PDO
        $stmt = $pdo->prepare("INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$random_id, $user_id, $waktu, $ip_address, $device_info, $status]);

        // Menghapus semua session
        $_SESSION = array();

        // Menghancurkan session
        session_destroy();

        // Redirect ke halaman login
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// No need to close PDO connection, it will be closed automatically at the end of the script
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
        <div class="button-group">
            <!-- Tombol logout -->
            <form action="" method="post">
                <input type="hidden" name="logout" value="yes">
                <button type="submit" class="logout-button">Ya, Logout</button>
            </form>
            <!-- Tombol batal kembali ke dashboard -->
            <a href="dashboardAdmin.php">
                <button class="cancel-button">Tidak, Kembali</button>
            </a>
        </div>
    </div>

</body>
</html>
