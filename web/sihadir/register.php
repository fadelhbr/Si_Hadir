<?php
// Start session
session_start();

// Cek apakah session 'setup' telah diset, dan jika tidak, redirect ke halaman login atau dashboard
if (!isset($_SESSION['setup']) || $_SESSION['setup'] !== true) {
    header('Location: index.php'); // Atau redirect ke halaman lain, misalnya dashboard jika login berhasil
    exit;
}

include 'auth/auth.php'; // Pastikan file ini terkoneksi dengan database

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = $nama_lengkap = $no_telp = $role = "";
$username_err = $email_err = $password_err = $confirm_password_err = $role_err = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = :username";
        if ($stmt = $pdo->prepare($sql)) {
            $username_post = trim($_POST["username"]); // Temporary variable
            // Bind variables to the prepared statement
            $stmt->bindParam(":username", $username_post, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $username_err = "Username sudah digunakan.";
            } else {
                $username = $username_post; // Retain valid username
            }
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($stmt = $pdo->prepare($sql)) {
            $email_post = trim($_POST["email"]); // Temporary variable
            $stmt->bindParam(":email", $email_post, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $email_err = "Email sudah digunakan.";
            } else {
                $email = $email_post; // Retain valid email
            }
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password harus memiliki minimal 6 karakter.";
    } else {
        $password = trim($_POST["password"]); // Retain valid password
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm-password"]))) {
        $confirm_password_err = "Mohon konfirmasi password.";
    } else {
        $confirm_password = trim($_POST["confirm-password"]);
        if ($password !== $confirm_password) {
            $confirm_password_err = "Password tidak cocok.";
        }
    }

    // Validate nama lengkap
    if (empty(trim($_POST["nama_lengkap"]))) {
        $nama_lengkap_err = "Mohon masukkan nama lengkap.";
    } else {
        $nama_lengkap = trim($_POST["nama_lengkap"]); // Retain valid nama lengkap
    }

    // Validate no telepon
    if (empty(trim($_POST["no_telp"]))) {
        $no_telp_err = "Mohon masukkan nomor telepon.";
    } else {
        $no_telp = trim($_POST["no_telp"]); // Retain valid no telepon
    }

    // Validate role
    $role = "admin"; // Role is always 'admin'

    // Check for errors before inserting into database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_lengkap_err) && empty($no_telp_err)) {
        // Generate random ID (6 digits)
        $random_id = rand(100000, 999999);

        // Prepare an insert statement
        $sql = "INSERT INTO users (id, username, password, nama_lengkap, email, role, no_telp) VALUES (:id, :username, :password, :nama_lengkap, :email, :role, :no_telp)";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Bind variables to the prepared statement
            $stmt->bindParam(":id", $random_id, PDO::PARAM_INT);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(":nama_lengkap", $nama_lengkap, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":role", $role, PDO::PARAM_STR); // Role is now always 'admin'
            $stmt->bindParam(":no_telp", $no_telp, PDO::PARAM_STR);
        
            // Execute the statement
            if ($stmt->execute()) {
                // Destroy session
                session_destroy();

                // Redirect to login page
                header("location: index.php");
                exit;
            } else {
                echo "Terjadi kesalahan: " . print_r($stmt->errorInfo(), true);
            }
        }
    }
    // Close statement
    unset($stmt);
}
// Close connection
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Registrasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Istok+Web&display=swap" rel="stylesheet">
    <style>
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
            min-height: 100vh;
            padding: 20px;
        }
        
        .register-container {
            background-color: #ffffff;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .register-title {
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
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .register-button {
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
        
        .register-button:hover {
            background-color: #0056b3;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        /* Highlight fields with errors */
        .input-group input.error {
            border-color: red;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1 class="register-title">Si Hadir</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" class="<?php echo !empty($username_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $username_err; ?></span>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="<?php echo !empty($email_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="<?php echo !empty($password_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="input-group">
                <label for="confirm-password">Konfirmasi Password</label>
                <input type="password" id="confirm-password" name="confirm-password" class="<?php echo !empty($confirm_password_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="input-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" class="<?php echo !empty($nama_lengkap_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $nama_lengkap_err; ?></span>
            </div>
            <div class="input-group">
                <label for="no_telp">Nomor Telepon</label>
                <input type="text" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($no_telp); ?>" class="<?php echo !empty($no_telp_err) ? 'error' : ''; ?>" required>
                <span class="error"><?php echo $no_telp_err; ?></span>
            </div>
            <button type="submit" class="register-button">Daftar</button>
        </form>
    </div>
</body>
</html>
