<?php
require_once '../auth/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Fungsi untuk mendapatkan sidik jari perangkat
function getDeviceFingerprint()
{
    $fingerprint = [];

    // User Agent
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Platform
    if (preg_match('/\((.*?)\)/', $userAgent, $matches)) {
        $fingerprint['platform'] = $matches[1];
    }

    // Hash perangkat unik
    $deviceString = implode('|', array_filter($fingerprint));
    $deviceHash = hash('sha256', $deviceString);

    return $deviceHash;
}

// Fungsi untuk memeriksa apakah pengguna memiliki perangkat yang terdaftar
function hasRegisteredDevice($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) FROM log_akses WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// Fungsi untuk memeriksa kecocokan perangkat
function isMatchingDevice($pdo, $user_id, $device_hash)
{
    $sql = "SELECT device_hash FROM log_akses 
            WHERE user_id = :user_id 
            AND device_hash IS NOT NULL 
            ORDER BY waktu ASC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $registeredHash = $stmt->fetchColumn();
    return $registeredHash && $device_hash === $registeredHash;
}

// Handler untuk permintaan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['username']) && isset($data['password'])) {
        $username = $data['username'];
        $password = $data['password'];
        $device_hash = getDeviceFingerprint();

        try {
            // Validasi pengguna dengan role karyawan
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users 
                                   WHERE username = :username AND role = 'karyawan'");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Gunakan respons umum untuk semua kasus kegagalan
            $genericErrorMessage = 'Username atau password tidak ditemukan';

            if (!$user) {
                // Username tidak ditemukan
                echo json_encode([
                    'status' => 'error',
                    'message' => $genericErrorMessage
                ]);
                exit;
            }

            // Periksa password
            if (!password_verify($password, $user['password'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $genericErrorMessage
                ]);
                exit;
            }

            // Periksa apakah perangkat sudah pernah terdaftar untuk user ini
            $isFirstLogin = !hasRegisteredDevice($pdo, $user['id']);
            $deviceMatches = $isFirstLogin || isMatchingDevice($pdo, $user['id'], $device_hash);

            if (!$deviceMatches) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $genericErrorMessage
                ]);
                exit;
            }

            // Status log akses
            $logStatus = $isFirstLogin ? 'first_registration' : 'login';

            // Masukkan log ke database
            $log_stmt = $pdo->prepare("INSERT INTO log_akses 
                (user_id, waktu, ip_address, device_info, status, device_hash, device_details) 
                VALUES (:user_id, NOW(), :ip_address, :device_info, :status, :device_hash, :device_details)");
            $log_stmt->execute([
                'user_id' => $user['id'],
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'device_info' => 'android mobile app sihadir',
                'status' => $logStatus,
                'device_hash' => $device_hash,
                'device_details' => null
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);

        } catch (PDOException $e) {
            // Tangani error database dengan pesan generik
            echo json_encode([
                'status' => 'error',
                'message' => $genericErrorMessage
            ]);
        }
    } else {
        // Jika username atau password tidak diberikan
        echo json_encode([
            'status' => 'error',
            'message' => $genericErrorMessage
        ]);
    }
} else {
    // Metode request tidak diizinkan
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode tidak diizinkan'
    ]);
}
?>