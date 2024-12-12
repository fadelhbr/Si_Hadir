<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['email']) || !isset($input['otp_code'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and OTP code are required.']);
        exit;
    }

    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $otpCode = trim($input['otp_code']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    global $pdo;
    $query = "SELECT id, id_otp FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found.']);
        exit;
    }

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

    // Success response
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OTP verified successfully. Proceed to reset password.']);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}
