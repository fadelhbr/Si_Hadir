<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if files exist
$required_files = [
    '../../vendor/autoload.php',
    '../../mail/PHPMailer.php',
    '../../mail/SMTP.php',
    '../../mail/Exception.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else if (file_exists('../' . $file)) {
        require_once '../' . $file;
    } else {
        error_log("Required file not found: $file");
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
        exit;
    }
}

function sendOTPEmail($toEmail, $otpCode)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sihadir.service@gmail.com';
        $mail->Password = 'iduk xkqe erys redy'; // Pastikan ini aman
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
                
                <div style='margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;'>
                    <p style='color: #e74c3c; font-size: 14px; margin: 0;'>⚠️ Penting:</p>
                    <ul style='color: #7F8C8D; font-size: 14px; margin: 10px 0; padding-left: 20px;'>
                        <li>Kode ini hanya berlaku sekali</li>
                        <li>Jangan bagikan kode ini kepada siapapun</li>
                        <li>Jika Anda tidak meminta kode ini, abaikan email ini</li>
                    </ul>
                </div>
                
                <div style='margin-top: 30px; text-align: center; color: #7F8C8D; font-size: 12px;'>
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    <p>Si Hadir by teamone</p>
                </div>
            </div>
        ";

        // Plain text version
        $mail->AltBody = "
Halo!

Anda telah meminta kode verifikasi untuk mengatur ulang password akun Si Hadir Anda.

Kode verifikasi Anda adalah: " . $otpCode . "

Penting:
- Kode ini hanya berlaku selama 15 menit
- Jangan bagikan kode ini kepada siapapun
- Jika Anda tidak meminta kode ini, abaikan email ini

Email ini dikirim secara otomatis, mohon tidak membalas email ini.

© 2024 Si Hadir. All rights reserved.
        ";

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
    if (!isset($input['email']) || !isset($input['otpCode'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input. Email and OTP code are required.']);
        exit;
    }

    // Extract parameters
    $email = $input['email'];
    $otpCode = $input['otpCode'];

    // Sanitize inputs
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    // Send OTP email
    try {
        $result = sendOTPEmail($email, $otpCode);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(['message' => 'OTP email sent successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send OTP email']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}