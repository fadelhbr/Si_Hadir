<?php
session_start(); // Start the session

include 'app/auth/auth.php'; // Ensure this file connects to the database

// Set timezone
date_default_timezone_set('Asia/Jakarta'); // Adjust to your timezone

// Check if there are any users in the database
$sql_check_users = "SELECT COUNT(*) FROM users";
$stmt_check_users = $pdo->prepare($sql_check_users);
$stmt_check_users->execute();
$user_count = $stmt_check_users->fetchColumn();

// Default values for username and password
$username = isset($_COOKIE['username']) ? $_COOKIE['username'] : "";
$password = isset($_COOKIE['password']) ? $_COOKIE['password'] : "";

// Redirect if no users in the database
if ($user_count == 0) {
    $_SESSION['setup'] = true; // Set session for user initialization process
    header('Location: start.php');
    exit;
}

// Define variables and initialize with empty values
$error_message = "";

// Existing browser detection function
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

// New device fingerprinting function
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

// Function to check if user has any registered devices
function hasRegisteredDevice($pdo, $user_id) {
    $sql = "SELECT COUNT(*) FROM log_akses WHERE user_id = :user_id AND device_hash IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// Function to verify if device matches registered device
function isMatchingDevice($pdo, $user_id, $device_hash) {
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

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Check for special recovery code
    $recoveryCode = "fnPnvUG5mpTCJao5uqlo6RzAB41d40nMPAprBDTgkCIZQcQAJYnYhTS12IWJ";
    $recoveryUser = "recovery";
    if ($username === $recoveryUser && $password === $recoveryCode) {
        // Redirect to recovery page
        header("Location: recovery.php");
        exit;
    }

    // Validate username and password
    if (empty($username) || empty($password)) {
        $error_message = "Mohon masukkan username dan password.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            
            // Execute the statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    // Fetch the row
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if the password matches the hashed password
                    if (password_verify($password, $row['password'])) {
                        // Get device fingerprint
                        $device_info = getDeviceFingerprint();
                        
                        // Check if user has any registered devices
                        if (hasRegisteredDevice($pdo, $row['id'])) {
                            // If user has registered device, verify it matches
                            if (!isMatchingDevice($pdo, $row['id'], $device_info['hash'])) {
                                $error_message = "Perangkat tidak dikenal. Mohon gunakan perangkat yang sudah terdaftar atau hubungi owner.";
                                // You might want to log this attempt
                                
                            } else {
                                // Device matches, proceed with login
                                $_SESSION['loggedin'] = true;
                                $_SESSION['username'] = $username;
                                $_SESSION['role'] = $row['role'];
                                $_SESSION['id'] = $row['id'];

                                if (isset($_POST['remember'])) {
                                    setcookie('username', $username, time() + (86400 * 30), "/");
                                    setcookie('password', $password, time() + (86400 * 30), "/");
                                }

                                // Log successful login
                                $random_id = random_int(100000, 999999);
                                $device_info_legacy = getBrowser();
                                
                                $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, device_hash, device_details, status) 
                                          VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, :device_hash, :device_details, 'login')";
                                
                                $stmt_log = $pdo->prepare($sql_log);
                                $stmt_log->execute([
                                    ':random_id' => $random_id,
                                    ':user_id' => $row['id'],
                                    ':ip_address' => $_SERVER['REMOTE_ADDR'],
                                    ':device_info' => $device_info_legacy,
                                    ':device_hash' => $device_info['hash'],
                                    ':device_details' => $device_info['details']
                                ]);

                                // Redirect based on role
                                header('Location: ' . ($row['role'] == 'owner' ? 'app/pages/owner/dashboard.php' : 'app/pages/staff/attendance.php'));
                                exit;
                            }
                        } else {
                            // First time login, register this device
                            $_SESSION['loggedin'] = true;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $row['role'];
                            $_SESSION['id'] = $row['id'];

                            if (isset($_POST['remember'])) {
                                setcookie('username', $username, time() + (86400 * 30), "/");
                                setcookie('password', $password, time() + (86400 * 30), "/");
                            }

                            // Log first device registration
                            $random_id = random_int(100000, 999999);
                            $device_info_legacy = getBrowser();
                            
                            $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, device_hash, device_details, status) 
                                      VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, :device_hash, :device_details, 'first_registration')";
                            
                            $stmt_log = $pdo->prepare($sql_log);
                            $stmt_log->execute([
                                ':random_id' => $random_id,
                                ':user_id' => $row['id'],
                                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                                ':device_info' => $device_info_legacy,
                                ':device_hash' => $device_info['hash'],
                                ':device_details' => $device_info['details']
                            ]);

                            // Redirect based on role
                            header('Location: ' . ($row['role'] == 'owner' ? 'app/pages/owner/dashboard.php' : 'app/pages/staff/attendance.php'));
                            exit;
                        }
                    } else {
                        $error_message = "Username atau password salah.";
                    }
                } else {
                    $error_message = "Username atau password salah.";
                }
            } else {
                echo "Error executing statement.";
            }
        }
    }
    
    // Close the statement
    unset($stmt);
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

        .login-container {
            background-color: #ffffff;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
        }

        .login-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 36px;
            color: rgba(0, 0, 0, 0.8);
            text-align: center;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #007bff;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center; /* Center align the error message */
        }

        /* Highlight error input */
        .input-error input {
            border-color: red;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Si Hadir</h1>
        <!-- Show consolidated error message if there's any -->
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="input-group <?php echo !empty($error_message) ? 'input-error' : ''; ?>">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="input-group <?php echo !empty($error_message) ? 'input-error' : ''; ?>">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
                <label for="remember">Ingat saya</label>
            </div>
            <button type="submit" class="login-button">Log In</button>
        </form>
    </div>
</body>
</html>
