<?php
include '../../app/auth/auth.php';

// Function to check if code exists in database
function isCodeExists($pdo, $code) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE kode_unik = :kode_unik");
    $stmt->execute(['kode_unik' => $code]);
    return $stmt->fetchColumn() > 0;
}

// Function to get all existing codes from database
function getAllExistingCodes($pdo) {
    $stmt = $pdo->query("SELECT kode_unik FROM absensi");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Function to generate unique code
function generateUniqueCode($pdo) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = 6;
    
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
    } while (isCodeExists($pdo, $code));
    
    return $code;
}

// Start session to store current code
session_start();

// Check if we need to generate a new code
$generateNew = false;

if (!isset($_SESSION['current_code']) || !isset($_SESSION['code_timestamp'])) {
    $generateNew = true;
} else {
    // Check if current code exists in database
    if (isCodeExists($pdo, $_SESSION['current_code'])) {
        $generateNew = true;
    }
}

if ($generateNew) {
    $_SESSION['current_code'] = generateUniqueCode($pdo);
    $_SESSION['code_timestamp'] = time();
}

// Generate QR code URL
$size = '200x200';
$url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($_SESSION['current_code']);

// Return response
echo json_encode([
    'uniqueCode' => $_SESSION['current_code'],
    'qrUrl' => $url,
    'needsUpdate' => $generateNew
]);