<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../auth/auth.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['email']) || !isset($input['otp_code']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email, OTP code, and new password are required.']);
        exit;
    }

    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $otpCode = trim($input['otp_code']);
    $newPassword = trim($input['new_password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters.']);
        exit;
    }

    try {
        global $pdo;

        // Find user by email
        $queryUser = "SELECT id, id_otp FROM users WHERE email = :email";
        $stmtUser = $pdo->prepare($queryUser);
        $stmtUser->bindParam(':email', $email);
        $stmtUser->execute();
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found.']);
            exit;
        }

        // Verify OTP
        $queryOtp = "SELECT otp_code FROM otp_code WHERE id = :otpId";
        $stmtOtp = $pdo->prepare($queryOtp);
        $stmtOtp->bindParam(':otpId', $user['id_otp']);
        $stmtOtp->execute();
        $otpRecord = $stmtOtp->fetch(PDO::FETCH_ASSOC);

        if (!$otpRecord || $otpRecord['otp_code'] !== $otpCode) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid OTP code.']);
            exit;
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $queryUpdatePassword = "UPDATE users SET password = :password WHERE email = :email";
        $stmtUpdatePassword = $pdo->prepare($queryUpdatePassword);
        $stmtUpdatePassword->bindParam(':password', $hashedPassword);
        $stmtUpdatePassword->bindParam(':email', $email);
        $stmtUpdatePassword->execute();

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}
