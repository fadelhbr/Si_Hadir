<?php
session_start();

// Check if user is logged in and has correct role and verification
if (
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner' ||
    !isset($_SESSION['reset_email']) || !isset($_SESSION['user_id']) ||
    !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true ||
    !isset($_SESSION['username']) // Add username check
) {
    echo "<script>
        window.onbeforeunload = null;
        window.location.href = 'login.php';
    </script>";
    exit;
}

include 'app/auth/auth.php';

// Initialize variables
$username = $email = $password = $confirm_password = $nama_lengkap = $no_telp = $jenis_kelamin = "";
$username_err = $email_err = $password_err = $confirm_password_err = $nama_lengkap_err = $no_telp_err = $jenis_kelamin_err = "";
$success_msg = $error_msg = "";

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $username = $user_data['username'];
        $email = $user_data['email'];
        $nama_lengkap = $user_data['nama_lengkap'];
        $no_telp = $user_data['no_telp'];
        $jenis_kelamin = $user_data['jenis_kelamin'];
    }
} catch (PDOException $e) {
    $error_msg = "Error: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    // Handle device reset request
    if (isset($_POST['reset_device'])) {
        try {
            $pdo->beginTransaction();

            // Delete all log_akses entries for this user
            $stmt = $pdo->prepare("DELETE FROM log_akses WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $pdo->commit();
            session_destroy();

            echo json_encode(['success' => true, 'message' => 'Device berhasil direset']);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal mereset device: ' . $e->getMessage()]);
            exit;
        }
    } else {
        // Regular form submission
        $username = trim($_POST["username"]);
        $email = trim($_POST["email"]);
        $nama_lengkap = trim($_POST["nama_lengkap"]);
        $no_telp = trim($_POST["no_telp"]);
        $jenis_kelamin = trim($_POST["jenis_kelamin"]);
        $password = trim($_POST["password"] ?? '');
        $confirm_password = trim($_POST["confirm-password"] ?? '');

        $errors = [];

        // Validate username
        $username = trim($_POST["username"]);
        if (empty($username)) {
            $username_err = "Mohon masukkan username.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $username_err = "Username sudah digunakan.";
            }
        }

        // Validate email
        $email = trim($_POST["email"]);
        if (empty($email)) {
            $email_err = "Mohon masukkan email.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $email_err = "Email sudah digunakan.";
            }
        }

        // Validate nama lengkap
        $nama_lengkap = trim($_POST["nama_lengkap"]);
        if (empty($nama_lengkap)) {
            $nama_lengkap_err = "Mohon masukkan nama lengkap.";
        }

        // Validate no telp
        $no_telp = trim($_POST["no_telp"]);
        if (empty($no_telp)) {
            $no_telp_err = "Mohon masukkan nomor telepon.";
        }

        // Validate jenis kelamin
        $jenis_kelamin = trim($_POST["jenis_kelamin"]);
        if (empty($jenis_kelamin)) {
            $jenis_kelamin_err = "Mohon pilih jenis kelamin.";
        }

        // Validate password if provided
        $password = trim($_POST["password"] ?? '');
        $confirm_password = trim($_POST["confirm-password"] ?? '');
        $update_password = false;

        if (!empty($password) || !empty($confirm_password)) {
            $update_password = true;
            if (strlen($password) < 6) {
                $password_err = "Password harus memiliki minimal 6 karakter.";
            } elseif (empty($confirm_password)) {
                $confirm_password_err = "Mohon konfirmasi password.";
            } elseif ($password !== $confirm_password) {
                $confirm_password_err = "Password tidak cocok.";
            }
        }

        // If no errors, proceed with update
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $sql = "UPDATE users SET 
                    username = ?,
                    email = ?,
                    nama_lengkap = ?,
                    no_telp = ?,
                    jenis_kelamin = ?";
                $params = [
                    trim($_POST["username"]),
                    trim($_POST["email"]),
                    trim($_POST["nama_lengkap"]),
                    trim($_POST["no_telp"]),
                    trim($_POST["jenis_kelamin"])
                ];

                if ($update_password) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ?";
                $params[] = $_SESSION['user_id'];

                $stmt = $pdo->prepare($sql);

                if ($stmt->execute($params)) {
                    $pdo->commit();
                    session_destroy();
                    $response['success'] = true;
                    $response['message'] = 'Data berhasil diperbarui';
                } else {
                    throw new PDOException("Gagal mengeksekusi query update");
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $response['message'] = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        } else {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }
}
?>
<script>
    let isRedirecting = false;

    function safeRedirect(url) {
        isRedirecting = true;
        window.onbeforeunload = null;
        window.location.href = url;
    }

    window.onbeforeunload = function () {
        if (!isRedirecting) {
            fetch('destroy_session.php', {
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

        .reset-device-button {
            width: 100%;
            padding: 1rem 2rem;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .reset-device-button:hover {
            background: #ef4444;
        }

        .button-group {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .button-disabled {
            opacity: 0.7;
            cursor: not-allowed;
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
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>
    <div class="register-container">
        <h1 class="register-title">Menu Recovery</h1>
        <p class="register-subtitle">Anda dapat mengupdate informasi akun owner dan mereset perangkat yang terdaftar.
            Setelah mengupdate data atau mereset device, Anda akan diminta untuk login kembali.</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form id="recoveryForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

            
            <div class="input-group <?php echo !empty($nama_lengkap_err) ? 'error' : ''; ?>">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                    value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>
                <?php if (!empty($nama_lengkap_err)): ?>
                    <span class="error-message"><?php echo $nama_lengkap_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($jenis_kelamin_err) ? 'error' : ''; ?>">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="" disabled>Pilih Jenis Kelamin</option>
                        <option value="laki" <?php echo $jenis_kelamin == "laki" ? "selected" : ""; ?>>Laki-laki</option>
                        <option value="perempuan" <?php echo $jenis_kelamin == "perempuan" ? "selected" : ""; ?>>Perempuan
                        </option>
                    </select>
                    <?php if (!empty($jenis_kelamin_err)): ?>
                        <span class="error-message"><?php echo $jenis_kelamin_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($no_telp_err) ? 'error' : ''; ?>">
                    <label for="no_telp">Nomor Telepon</label>
                    <input type="tel" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($no_telp); ?>"
                        required>
                    <?php if (!empty($no_telp_err)): ?>
                        <span class="error-message"><?php echo $no_telp_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($username_err) ? 'error' : ''; ?>">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>"
                        required>
                    <?php if (!empty($username_err)): ?>
                        <span class="error-message"><?php echo $username_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($email_err) ? 'error' : ''; ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                        required>
                    <?php if (!empty($email_err)): ?>
                        <span class="error-message"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="input-group <?php echo !empty($password_err) ? 'error' : ''; ?>">
                    <label for="password">Password Baru (Opsional)</label>
                    <input type="password" id="password" name="password">
                    <?php if (!empty($password_err)): ?>
                        <span class="error-message"><?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group <?php echo !empty($confirm_password_err) ? 'error' : ''; ?>">
                    <label for="confirm-password">Konfirmasi Password</label>
                    <input type="password" id="confirm-password" name="confirm-password">
                    <?php if (!empty($confirm_password_err)): ?>
                        <span class="error-message"><?php echo $confirm_password_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="register-button" id="saveButton">
                    <i class="fas fa-save"></i>
                    <span>Simpan Perubahan</span>
                </button>

                <button type="button" class="reset-device-button" id="resetButton">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Reset Perangkat</span>
                </button>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('recoveryForm');
        const resetButton = document.getElementById('resetButton');
        const saveButton = document.getElementById('saveButton');

        function showLoading() {
            document.querySelector('.loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.querySelector('.loading-overlay').style.display = 'none';
        }

        function disableButtons() {
            saveButton.disabled = true;
            resetButton.disabled = true;
            saveButton.classList.add('button-disabled');
            resetButton.classList.add('button-disabled');
        }

        function enableButtons() {
            saveButton.disabled = false;
            resetButton.disabled = false;
            saveButton.classList.remove('button-disabled');
            resetButton.classList.remove('button-disabled');
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.input-group').forEach(el => el.classList.remove('error'));
        }

        function displayErrors(errors) {
            clearErrors();
            Object.keys(errors).forEach(key => {
                const inputElement = document.querySelector(`[name="${key.replace('_err', '')}"]`);
                if (inputElement) {
                    const inputGroup = inputElement.closest('.input-group');
                    if (inputGroup) {
                        inputGroup.classList.add('error');
                        const errorSpan = document.createElement('span');
                        errorSpan.className = 'error-message';
                        errorSpan.textContent = errors[key];
                        inputGroup.appendChild(errorSpan);
                    }
                }
            });
        }

        function handleSubmit(e, isDeviceReset = false) {
            e.preventDefault();
            
            if (isSubmitting) return;
            isSubmitting = true;

            showLoading();
            disableButtons();
            clearErrors();

            const formData = new FormData(form);
            if (isDeviceReset) {
                formData.append('reset_device', '1');
            }

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    safeRedirect('login.php');
                } else {
                    if (data.errors) {
                        displayErrors(data.errors);
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat memproses permintaan.');
                    }
                    enableButtons();
                }
                hideLoading();
                isSubmitting = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                enableButtons();
                hideLoading();
                isSubmitting = false;
            });
        }

        let isSubmitting = false;

        form.addEventListener('submit', (e) => handleSubmit(e, false));

        resetButton.addEventListener('click', (e) => {
            if (confirm('Apakah Anda yakin ingin mereset perangkat? Anda harus login ulang setelah ini.')) {
                handleSubmit(e, true);
            }
        });
    });
</script>

</body>

</html>