<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../auth/auth.php'; // Pastikan path ini benar

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    if (!$json) {
        throw new Exception('No input received');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Validate input
    if (!isset($data['username']) || !isset($data['password'])) {
        throw new Exception('Username and password are required');
    }

    // Get and sanitize inputs
    $username = trim($data['username']);
    $password = trim($data['password']);
    $deviceId = isset($data['device_id']) ? trim($data['device_id']) : 'unknown';

    // Simple device info
    $deviceInfo = [
        'hash' => hash('sha256', $deviceId . time()),
        'details' => json_encode([
            'device_id' => $deviceId,
            'timestamp' => time()
        ])
    ];

    // Check database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }

    // Prepare SQL statement
    $sql = "SELECT id, username, password, role FROM users WHERE username = :username AND role = 'karyawan'";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }

    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password, $user['password'])) {
            // Log access
            $randomId = random_int(100000, 999999);
            $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, device_hash, device_details, status) 
                       VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, :device_hash, :device_details, :status)";

            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([
                ':random_id' => $randomId,
                ':user_id' => $user['id'],
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':device_info' => 'Mobile App',
                ':device_hash' => $deviceInfo['hash'],
                ':device_details' => $deviceInfo['details'],
                ':status' => 'mobile_login'
            ]);

            // Create simple token
            $token = base64_encode(json_encode([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'exp' => time() + 3600
            ]));

            // Success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'debug' => $e->getMessage() // Hapus ini di production
    ]);
    error_log("Login API PDO Error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error',
        'debug' => $e->getMessage() // Hapus ini di production
    ]);
    error_log("Login API Error: " . $e->getMessage());
}

// Close the connection
unset($pdo);