<?php
// Start session
session_start();

// Cek apakah session 'setup' telah diset, dan jika tidak, redirect ke halaman login atau dashboard
if (!isset($_SESSION['setup']) || $_SESSION['setup'] !== true) {
    header('Location: login.php');
    exit;
}

include 'app/auth/auth.php';

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = $nama_lengkap = $no_telp = $role = $jenis_kelamin = "";
$username_err = $email_err = $password_err = $confirm_password_err = $nama_lengkap_err = $no_telp_err = $role_err = $jenis_kelamin_err = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = :username";
        if ($stmt = $pdo->prepare($sql)) {
            $username_post = trim($_POST["username"]);
            $stmt->bindParam(":username", $username_post, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $username_err = "Username sudah digunakan.";
            } else {
                $username = $username_post;
            }
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($stmt = $pdo->prepare($sql)) {
            $email_post = trim($_POST["email"]);
            $stmt->bindParam(":email", $email_post, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $email_err = "Email sudah digunakan.";
            } else {
                $email = $email_post;
            }
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password harus memiliki minimal 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
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
        $nama_lengkap = trim($_POST["nama_lengkap"]);
    }

    // Validate jenis kelamin
    if (empty(trim($_POST["jenis_kelamin"]))) {
        $jenis_kelamin_err = "Mohon pilih jenis kelamin.";
    } else {
        $jenis_kelamin = trim($_POST["jenis_kelamin"]);
    }

    // Validate no telepon
    if (empty(trim($_POST["no_telp"]))) {
        $no_telp_err = "Mohon masukkan nomor telepon.";
    } else {
        $no_telp = trim($_POST["no_telp"]);
        if (!preg_match('/^[0-9]+$/', $no_telp)) {
            $no_telp_err = "Nomor telepon hanya boleh mengandung angka.";
        }
    }

    // Validate role
    $role = "owner";

    // Check for errors before inserting into database
    if (
        empty($username_err) && empty($email_err) && empty($password_err) &&
        empty($confirm_password_err) && empty($nama_lengkap_err) &&
        empty($no_telp_err) && empty($jenis_kelamin_err)
    ) {

        // Generate random ID (6 digits)
        $random_id = rand(100000, 999999);

        // Prepare an insert statement
        $sql = "INSERT INTO users (id, username, password, nama_lengkap, email, role, no_telp, jenis_kelamin) 
                VALUES (:id, :username, :password, :nama_lengkap, :email, :role, :no_telp, :jenis_kelamin)";

        if ($stmt = $pdo->prepare($sql)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Bind variables to the prepared statement
            $stmt->bindParam(":id", $random_id, PDO::PARAM_INT);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(":nama_lengkap", $nama_lengkap, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":role", $role, PDO::PARAM_STR);
            $stmt->bindParam(":no_telp", $no_telp, PDO::PARAM_STR);
            $stmt->bindParam(":jenis_kelamin", $jenis_kelamin, PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                // Destroy session
                session_destroy();

                // Redirect to login page
                header("location: login.php");
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
            --error: #dc2626;
            --error-light: #fee2e2;
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

        .register-container {
            width: 100%;
            max-width: 580px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 3rem;
        }

        .register-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 1rem;
        }

        .register-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            text-align: center;
            max-width: 400px;
            margin: 0 auto 2rem;
            line-height: 1.6;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-group.error label {
            color: var(--error);
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--background);
            color: var(--text-primary);
            outline: none;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .input-group.error input,
        .input-group.error select {
            border-color: var(--error);
            background-color: var(--error-light);
        }

        .input-group.error input:focus,
        .input-group.error select:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .register-button {
            width: 100%;
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .register-button:hover {
            background: var(--primary-light);
        }

        .input-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.25rem center;
            background-size: 16px;
            padding-right: 3rem;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .register-container {
                padding: 2rem;
            }

            .register-title {
                font-size: 1.75rem;
            }

            .register-subtitle {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h1 class="register-title">Si Hadir</h1>
        <p class="register-subtitle">Buat akun untuk mulai menggunakan aplikasi</p>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="input-group <?php echo !empty($nama_lengkap_err) ? 'error' : ''; ?>">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                    value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>"
                    required>
                <?php if (!empty($nama_lengkap_err)): ?>
                    <span class="error-message"><?php echo $nama_lengkap_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($jenis_kelamin_err) ? 'error' : ''; ?>">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="" disabled <?php echo empty($_POST['jenis_kelamin']) ? 'selected' : ''; ?>>
                            Pilih Jenis Kelamin
                        </option>
                        <option value="laki" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == "laki") ? "selected" : ""; ?>>
                            Laki-laki
                        </option>
                        <option value="perempuan" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == "perempuan") ? "selected" : ""; ?>>
                            Perempuan
                        </option>
                    </select>
                    <?php if (!empty($jenis_kelamin_err)): ?>
                        <span class="error-message"><?php echo $jenis_kelamin_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($no_telp_err) ? 'error' : ''; ?>">
                    <label for="no_telp">Nomor Telepon</label>
                    <input type="text" id="no_telp" name="no_telp"
                        pattern="[0-9]+" maxlength="15"
                        value="<?php echo isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''; ?>"
                        required oninput="validateNoTelp(this)">
                        <span id="error-message" style="display: none; color: red; font-size: 12px; margin-top: 5px;"></span>
                    <?php if (!empty($no_telp_err)): ?>
                        <span class="error-message" style="color: red;"><?php echo $no_telp_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($username_err) ? 'error' : ''; ?>">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required>
                    <?php if (!empty($username_err)): ?>
                        <span class="error-message"><?php echo $username_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($email_err) ? 'error' : ''; ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <?php if (!empty($email_err)): ?>
                        <span class="error-message"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($password_err) ? 'error' : ''; ?>">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                        value="<?php echo isset($password) ? htmlspecialchars($password) : ''; ?>" required>
                    <?php if (!empty($password_err)): ?>
                        <span class="error-message"><?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($confirm_password_err) ? 'error' : ''; ?>">
                    <label for="confirm-password">Konfirmasi Password</label>
                    <input type="password" id="confirm-password" name="confirm-password"
                        value="<?php echo isset($confirm_password) ? htmlspecialchars($confirm_password) : ''; ?>"
                        required>
                    <?php if (!empty($confirm_password_err)): ?>
                        <span class="error-message"><?php echo $confirm_password_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="register-button">
                <i class="fas fa-user-plus"></i>
                <span>Daftar Sekarang</span>
            </button>
        </form>
    </div>

    <!-- ALERT KOLOM NOMOR TELPON (HANYA BERISI ANGKA) -->
    <script>
        function validateNoTelp(input) {
            const errorMessage = document.getElementById('error-message');
            if (/[^0-9]/.test(input.value)) {
                errorMessage.style.display = 'inline';
                errorMessage.textContent = "Nomor telepon hanya boleh mengandung angka.";
                input.value = input.value.replace(/[^0-9]/g, '');
            } else {
                errorMessage.style.display = 'none';
            }
        }
    </script>

</body>
</html>