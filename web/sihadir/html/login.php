<?php
// Start the session
session_start();

include 'auth.php'; // Pastikan file ini terkoneksi dengan database

// Define variables and initialize with empty values
$nama = $password = "";
$nama_err = $password_err = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate nama (username)
    if (empty(trim($_POST["nama"]))) {
        $nama_err = "Mohon masukkan nama.";
    } else {
        $nama = trim($_POST["nama"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Check credentials
    if (empty($nama_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT nama, password, role FROM Karyawan WHERE nama = :nama";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement
            $stmt->bindParam(":nama", $nama, PDO::PARAM_STR);
            
            // Execute the statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    // Fetch the row
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if the password matches (without hashing)
                    if ($password == $row['password']) {
                        // Password is correct, start a new session
                        $_SESSION['loggedin'] = true;
                        $_SESSION['nama'] = $nama;
                        $_SESSION['role'] = $row['role']; // Save role in session

                        // Redirect based on the role
                        if ($row['role'] == 'admin') {
                            header('Location: dashboardAdmin.php'); // Redirect to admin dashboard
                        } else {
                            header('Location: dashboardKaryawan.php'); // Redirect to karyawan dashboard
                        }
                        exit;
                    } else {
                        $password_err = "Nama atau password salah";
                    }
                } else {
                    $nama_err = "Nama atau password salah";
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

        .forgot-password {
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: #007bff;
            text-decoration: none;
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
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Si Hadir</h1>
        <!-- Show error message if there's any -->
        <?php if (!empty($error_msg)): ?>
            <p class="error"><?php echo $error_msg; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="input-group">
                <label for="nama">Username</label>
                <input type="text" id="nama" name="nama" required>
                <span class="error"><?php echo $nama_err; ?></span>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya</label>
            </div>
            <div class="forgot-password">
                <a href="register.html">Register</a>
            </div>
            <button type="submit" class="login-button">Log In</button>
        </form>
    </div>
</body>
</html>
