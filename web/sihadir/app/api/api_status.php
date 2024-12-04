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
        $today = date("Y-m-d"); // Ambil tanggal hari ini

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
            JOIN
                absensi a ON p.id = a.pegawai_id
            WHERE
                u.id = :user_id AND a.tanggal = :today
            ORDER BY
                a.tanggal DESC
            LIMIT 1
        ");

        // Bind parameter
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindParam(":today", $today, PDO::PARAM_STR);

        // Execute query
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            respondWithError("No attendance record found for today", 404);
        }

        // Return the relevant data
        return [
            "tanggal" => $result["tanggal"],
            "status_kehadiran" => $result["status_kehadiran"],
            "keterangan" => $result["keterangan"],
        ];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        respondWithError("Database error", 500);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        respondWithError("Internal server error", 500);
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
