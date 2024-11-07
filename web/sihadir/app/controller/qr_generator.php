<?php
include '../../app/auth/auth.php';

// Function to check if code exists in absensi table
function isCodeExistsInAbsensi($pdo, $code) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE kode_unik = :kode_unik");
    $stmt->execute(['kode_unik' => $code]);
    return $stmt->fetchColumn() > 0;
}

// Function to check if qr_code table is empty
function isQrCodeTableEmpty($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM qr_code");
    return $stmt->fetchColumn() == 0;
}

// Function to get the current code from qr_code table
function getCurrentCode($pdo) {
    $stmt = $pdo->query("SELECT kode_unik FROM qr_code ORDER BY id DESC LIMIT 1");
    return $stmt->fetchColumn();
}

// Function to generate unique code
function generateUniqueCode() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = 6;
    
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

// Function to update the code in qr_code table
function updateCode($pdo, $code) {
    $stmt = $pdo->prepare("UPDATE qr_code SET kode_unik = :kode_unik WHERE id = (SELECT id FROM qr_code ORDER BY id DESC LIMIT 1)");
    $stmt->execute(['kode_unik' => $code]);
}

// Function to insert new code in qr_code table
function insertCode($pdo, $code) {
    $stmt = $pdo->prepare("INSERT INTO qr_code (kode_unik) VALUES (:kode_unik)");
    $stmt->execute(['kode_unik' => $code]);
}

// Start session to store current code
session_start();

// Check if qr_code table is empty
if (isQrCodeTableEmpty($pdo)) {
    // Generate new code and insert it
    do {
        $newCode = generateUniqueCode();
    } while (isCodeExistsInAbsensi($pdo, $newCode));
    
    insertCode($pdo, $newCode);
    $_SESSION['current_code'] = $newCode;
    $generateNew = true;
} else {
    // Get the current code from database
    $currentCode = getCurrentCode($pdo);

    // Check if we need to generate a new code
    $generateNew = false;

    if (isCodeExistsInAbsensi($pdo, $currentCode)) {
        $generateNew = true;
        do {
            $newCode = generateUniqueCode();
        } while (isCodeExistsInAbsensi($pdo, $newCode));
        
        updateCode($pdo, $newCode);
        $_SESSION['current_code'] = $newCode;
    } else {
        $_SESSION['current_code'] = $currentCode;
    }
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