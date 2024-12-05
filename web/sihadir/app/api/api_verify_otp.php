<?php
session_start();  // Start session for OTP verification

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include file for database connection
require_once '../auth/auth.php';  // Ensure this file is present for DB connection

// Handle incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['email']) || !isset($input['otp_code']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input. Email, OTP code, and new password are required.']);
        exit;
    }

    // Extract parameters
    $email = $input['email'];
    $otpCode = $input['otp_code'];
    $newPassword = $input['new_password'];

    // Sanitize input
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    // Search for the user based on email
    global $pdo;
    $query = "SELECT id, id_otp FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $userId = $user['id'];
    $storedOtpId = $user['id_otp'];

    // Verify OTP code
    $queryOtp = "SELECT otp_code FROM otp_code WHERE id = :otpId";
    $stmtOtp = $pdo->prepare($queryOtp);
    $stmtOtp->bindParam(':otpId', $storedOtpId);
    $stmtOtp->execute();
    $otpRecord = $stmtOtp->fetch(PDO::FETCH_ASSOC);

    if (!$otpRecord) {
        http_response_code(404);
        echo json_encode(['error' => 'OTP record not found']);
        exit;
    }

    $storedOtpCode = $otpRecord['otp_code'];

    // Check if the provided OTP matches the stored OTP
    if ($otpCode !== $storedOtpCode) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid OTP code']);
        exit;
    }

    // Proceed to reset the password
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the user's password
    $queryUpdatePassword = "UPDATE users SET password = :newPassword, updated_at = NOW() WHERE id = :userId";
    $stmtUpdatePassword = $pdo->prepare($queryUpdatePassword);
    $stmtUpdatePassword->bindParam(':newPassword', $hashedPassword);
    $stmtUpdatePassword->bindParam(':userId', $userId);

    if (!$stmtUpdatePassword->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
        exit;
    }

    // Successfully updated the password
    http_response_code(200);
    echo json_encode(['message' => 'Password reset successfully']);
    exit;
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}
?>
