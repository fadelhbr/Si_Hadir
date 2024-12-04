<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
require_once "../auth/auth.php";

function respondWithError($message, $statusCode = 400)
{
    http_response_code($statusCode);
    echo json_encode(["error" => $message]);
    exit();
}

function formatTime($time)
{
    // Remove seconds from time
    return date("H:i", strtotime($time));
}

function getEmployeeSchedule($pdo, $userId)
{
    try {
        // Get employee data
        $stmt = $pdo->prepare(
            "SELECT id, hari_libur FROM pegawai WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$employee) {
            respondWithError("Employee not found", 404);
        }

        // Get shift data
        $stmt = $pdo->prepare("
            SELECT s.nama_shift, s.jam_masuk, s.jam_keluar
            FROM jadwal_shift js
            JOIN shift s ON js.shift_id = s.id
            WHERE js.pegawai_id = ? AND js.status = 'aktif'
            LIMIT 1
        ");
        $stmt->execute([$employee["id"]]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$shift) {
            respondWithError("No active shift found", 404);
        }

        // Create weekly schedule
        $weekDays = [
            "senin",
            "selasa",
            "rabu",
            "kamis",
            "jumat",
            "sabtu",
            "minggu",
        ];
        $schedule = [];

        foreach ($weekDays as $day) {
            if ($day === $employee["hari_libur"]) {
                $schedule[$day] = [
                    "status" => "Libur",
                    "shift_name" => "Libur",
                    "jam_masuk" => "-",
                    "jam_keluar" => "-",
                ];
            } else {
                $schedule[$day] = [
                    "status" => "Masuk",
                    "shift_name" => $shift["nama_shift"],
                    "jam_masuk" => formatTime($shift["jam_masuk"]),
                    "jam_keluar" => formatTime($shift["jam_keluar"]),
                ];
            }
        }

        return $schedule;
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

$userId = $_GET["user_id"];

try {
    // Get schedule using the provided user ID
    $schedule = getEmployeeSchedule($pdo, $userId);

    // Return schedule as JSON
    echo json_encode([
        "success" => true,
        "schedule" => $schedule,
    ]);
} catch (Exception $e) {
    respondWithError("Error retrieving schedule", 500);
}
