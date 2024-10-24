<?php
session_start(); // Start the session

include 'auth/auth.php'; // Ensure this file connects to the database

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

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

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
                        // Password is correct, start a new session
                        $_SESSION['loggedin'] = true;
                        $_SESSION['username'] = $username; // Store username in session
                        $_SESSION['role'] = $row['role']; // Store role in session
                        $_SESSION['id'] = $row['id']; // Store id in session

                        // Set cookies if "Ingat saya" is checked
                        if (isset($_POST['remember'])) {
                            setcookie('username', $username, time() + (86400 * 30), "/"); // 30 days
                            setcookie('password', $password, time() + (86400 * 30), "/"); // 30 days
                        } else {
                            // Clear cookies if not remembered
                            setcookie('username', "", time() - 3600, "/");
                            setcookie('password', "", time() - 3600, "/");
                        }

                        // Get browser and OS information
                        $device_info = getBrowser();

                        // Generate a random integer ID for logging
                        $random_id = random_int(100000, 999999); // Adjust the range as needed

                        // Insert log entry into log_akses table
                        $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, status) 
                                    VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, 'login')";

                        $stmt_log = $pdo->prepare($sql_log);
                        $stmt_log->bindParam(':random_id', $random_id, PDO::PARAM_INT);
                        $stmt_log->bindParam(':user_id', $row['id'], PDO::PARAM_INT);
                        $stmt_log->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                        $stmt_log->bindParam(':device_info', $device_info, PDO::PARAM_STR);

                        if (!$stmt_log->execute()) {
                            // Display error information
                            $errorInfo = $stmt_log->errorInfo();
                            echo "Error inserting log: " . $errorInfo[2]; // Show error message
                        }

                        // Redirect based on the role
                        if ($row['role'] == 'admin') {
                            header('Location: dashboardAdmin.php'); // Redirect to admin dashboard
                        } else {
                            header('Location: pengumumanKaryawan.php'); // Redirect to employee dashboard
                        }
                        exit;
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
        <form action="index.php" method="post">
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
