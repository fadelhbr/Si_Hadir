<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../auth/auth.php"; // Autentikasi

function respondWithError($message, $statusCode = 400)
{
    http_response_code($statusCode);
    echo json_encode(["error" => $message]);
    exit();
}

function getAttendanceStatus($pdo, $userId)
{
    try {
        // Set timezone to GMT+7
        date_default_timezone_set("Asia/Jakarta");
        $today = date("Y-m-d");

        $stmt = $pdo->prepare("
            SELECT
                u.id AS user_id,
                u.username,
                u.nama_lengkap,
                a.tanggal,
                a.status_kehadiran,
                a.keterangan
            FROM
                users u
            JOIN
                pegawai p ON u.id = p.user_id
            LEFT JOIN
                absensi a ON p.id = a.pegawai_id AND a.tanggal = :today
            WHERE
                u.id = :user_id
            ORDER BY
                a.tanggal DESC
            LIMIT 1
        ");

        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindParam(":today", $today, PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $userStmt = $pdo->prepare(
                "SELECT * FROM users WHERE id = :user_id"
            );
            $userStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
            $userStmt->execute();
            $userExists = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$userExists) {
                respondWithError("User not found", 404);
            }

            return [
                "tanggal" => $today,
                "status_kehadiran" => "Belum Absen",
                "keterangan" => "Tidak ada catatan absensi hari ini",
            ];
        }

        return [
            "tanggal" => $result["tanggal"] ?? $today,
            "status_kehadiran" => $result["status_kehadiran"] ?? "Belum Absen",
            "keterangan" => $result["keterangan"] ?? "Tidak ada keterangan",
        ];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        respondWithError("Database error: " . $e->getMessage(), 500);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        respondWithError("Internal server error: " . $e->getMessage(), 500);
    }
}

// Check if user_id is provided via GET
if (!isset($_GET["user_id"]) || empty($_GET["user_id"])) {
    respondWithError("User ID is required", 400);
}

$userId = (int) $_GET["user_id"]; // Cast user_id to integer for security

try {
    // Get attendance status using the provided user ID
    $attendanceStatus = getAttendanceStatus($pdo, $userId);

    // Return the status as JSON
    echo json_encode([
        "success" => true,
        "attendance" => $attendanceStatus,
    ]);
} catch (Exception $e) {
    respondWithError("Error retrieving attendance status", 500);
}
