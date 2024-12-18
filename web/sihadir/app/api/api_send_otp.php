<?php
session_start();  // Mulai sesi untuk menyimpan OTP

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include file untuk koneksi ke database
require_once '../auth/auth.php';  // Pastikan file ini ada untuk koneksi ke DB

require_once __DIR__ . '/../../mail/PHPMailer.php';
require_once __DIR__ . '/../../mail/SMTP.php';
require_once __DIR__ . '/../../mail/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Fungsi untuk menghasilkan kode OTP 6 digit
function generateOtp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Fungsi untuk mengirimkan OTP melalui email
function sendOTPEmail($toEmail, $otpCode)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '*****';
        $mail->Password = '*****'; // Pastikan ini aman
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('sihadir.service@gmail.com', 'Si Hadir');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Si Hadir';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
                <p style='color: #2C3E50; font-size: 16px; margin-bottom: 20px;'>Halo!</p>
                <p style='color: #2C3E50; font-size: 16px; margin-bottom: 20px;'>Anda telah meminta kode verifikasi untuk mengatur ulang password akun Si Hadir Anda. Berikut adalah kode verifikasi Anda:</p>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;'>
                    <h1 style='font-size: 32px; letter-spacing: 5px; color: #2C3E50; margin: 0;'>" . htmlspecialchars($otpCode) . "</h1>
                </div>
            </div>
        ";

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email tidak dapat dikirim. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid input. Email is required.'
        ]);
        exit;
    }

    // Extract parameters
    $email = $input['email'];

    // Sanitize input
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email tidak valid'
        ]);
        exit;
    }

    // Cari id pengguna berdasarkan email
    global $pdo;
    $query = "SELECT id, id_otp FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Email tidak terdaftar'
        ]);
        exit;
    }

    $userId = $user['id'];
    $otpId = $user['id_otp'];

    // Generate OTP
    $otpCode = generateOtp();

    if ($otpId) {
        // Update OTP jika id_otp sudah ada
        $queryUpdateOtp = "UPDATE otp_code SET otp_code = :otpCode WHERE id = :otpId";
        $stmtUpdateOtp = $pdo->prepare($queryUpdateOtp);
        $stmtUpdateOtp->bindParam(':otpCode', $otpCode);
        $stmtUpdateOtp->bindParam(':otpId', $otpId);

        if (!$stmtUpdateOtp->execute()) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update OTP'
            ]);
            exit;
        }
    } else {
        // Jika id_otp belum ada, buat OTP baru
        $queryOtp = "INSERT INTO otp_code (otp_code) VALUES (:otpCode)";
        $stmtOtp = $pdo->prepare($queryOtp);
        $stmtOtp->bindParam(':otpCode', $otpCode);

        if (!$stmtOtp->execute()) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to store OTP'
            ]);
            exit;
        }

        // Dapatkan ID OTP yang baru saja dimasukkan
        $otpId = $pdo->lastInsertId();

        // Perbarui kolom id_otp di tabel users
        $queryUpdateUser = "UPDATE users SET id_otp = :otpId, updated_at = NOW() WHERE id = :userId";
        $stmtUpdateUser = $pdo->prepare($queryUpdateUser);
        $stmtUpdateUser->bindParam(':otpId', $otpId);
        $stmtUpdateUser->bindParam(':userId', $userId);

        if (!$stmtUpdateUser->execute()) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update user with OTP'
            ]);
            exit;
        }
    }

    // Kirim OTP melalui email
    $result = sendOTPEmail($email, $otpCode);

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'OTP Berhasil Dikirim'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Gagal Mengirim OTP'
        ]);
    }
    exit;
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed'
    ]);
    exit;
}
