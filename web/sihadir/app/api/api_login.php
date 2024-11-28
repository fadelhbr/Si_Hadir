<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection and authentication
include '../auth/auth.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Function to get device fingerprint
function getDeviceFingerprint()
{
    $fingerprint = [];

    // Get user agent components
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // CPU Architecture and platform
    if (preg_match('/\((.*?)\)/', $userAgent, $matches)) {
        $fingerprint['platform'] = $matches[1];
    }

    // Timezone
    $fingerprint['timezone'] = date_default_timezone_get();

    // Available languages
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $fingerprint['languages'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

    // Headers that might help identify the device
    $headers = [
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_ACCEPT_CHARSET'
    ];

    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            $fingerprint[$header] = $_SERVER[$header];
        }
    }

    // Check for mobile device indicators
    $fingerprint['is_mobile'] = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) ? 'true' : 'false';

    // Generate unique device hash
    $deviceString = implode('|', array_filter($fingerprint));
    $deviceHash = hash('sha256', $deviceString);

    return [
        'hash' => $deviceHash,
        'details' => json_encode($fingerprint)
    ];
}

// Function to check if user has any registered devices
function hasRegisteredDevice($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) FROM log_akses WHERE user_id = :user_id AND device_hash IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// Function to verify if device matches registered device
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
    return $device_hash === $registeredHash;
}

// Function to log access
function logAccess($pdo, $user, $device_info, $status)
{
    $random_id = random_int(100000, 999999);

    $sql_log = "INSERT INTO log_akses (id, user_id, waktu, ip_address, device_info, device_hash, device_details, status) 
                VALUES (:random_id, :user_id, NOW(), :ip_address, :device_info, :device_hash, :device_details, :status)";

    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->execute([
        ':random_id' => $random_id,
        ':user_id' => $user['id'],
        ':ip_address' => $_SERVER['REMOTE_ADDR'],
        ':device_info' => 'mobile app sihadir',
        ':device_hash' => $device_info['hash'],
        ':device_details' => $device_info['details'],
        ':status' => $status
    ]);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data
    $inputData = json_decode(file_get_contents('php://input'), true);

    $username = trim($inputData['username'] ?? '');
    $password = trim($inputData['password'] ?? '');

    $response = [
        'success' => false,
        'message' => ''
    ];

    // Validate input
    if (empty($username)) {
        $response['message'] = "Mohon masukkan username.";
        echo json_encode($response);
        exit;
    }

    if (empty($password)) {
        $response['message'] = "Mohon masukkan password.";
        echo json_encode($response);
        exit;
    }

    // Get device fingerprint
    $device_info = getDeviceFingerprint();

    try {
        // Prepare SQL to prevent SQL injection
        $sql = "SELECT u.id, u.username, u.password, u.role, u.nama_lengkap, p.id AS pegawai_id 
                FROM users u
                LEFT JOIN pegawai p ON u.id = p.user_id
                WHERE u.username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user is employee
            if ($row['role'] !== 'karyawan') {
                $response['message'] = "Hanya karyawan yang diizinkan login melalui mobile.";
                echo json_encode($response);
                exit;
            }

            // Verify password
            if (password_verify($password, $row['password'])) {
                // Check device registration
                if (hasRegisteredDevice($pdo, $row['id'])) {
                    if (!isMatchingDevice($pdo, $row['id'], $device_info['hash'])) {
                        $response['message'] = "Perangkat tidak dikenal. Silahkan hubungi owner";
                        echo json_encode($response);
                        exit;
                    }
                    
                    // Login with existing device
                    logAccess($pdo, $row, $device_info, 'login');
                } else {
                    // First time login (device registration)
                    logAccess($pdo, $row, $device_info, 'first_registration');
                }

                $response = [
                    'success' => true,
                    'message' => 'Login berhasil',
                    'user' => [
                        'id' => $row['id'],
                        'nama_lengkap' => $row['nama_lengkap'],
                        'username' => $row['username'],
                        'role' => $row['role'],
                        'pegawai_id' => $row['pegawai_id']
                    ]
                ];
            } else {
                $response['message'] = "Username atau password salah.";
            }
        } else {
            $response['message'] = "Username atau password salah.";
        }
    } catch (PDOException $e) {
        $response['message'] = "Terjadi kesalahan sistem. Silakan coba lagi.";
    }

    // Send JSON response
    echo json_encode($response);
    exit;
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak diizinkan'
    ]);
    exit;
}

// Close the connection
unset($pdo);
?>