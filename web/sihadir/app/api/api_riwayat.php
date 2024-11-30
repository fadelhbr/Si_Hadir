<?php 
header('Content-Type: application/json');
require_once '../auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

try {
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }

    // Mengambil data dari request body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        throw new Exception('Username dan password diperlukan');
    }

    $username = $data['username'];
    $password = $data['password'];

    // Validasi pengguna
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Ambil riwayat kehadiran pengguna
        $pegawaiId = $user['id'];

        // SQL untuk mengambil data absensi dan hari libur pegawai
        $sql = "SELECT 
                    DATE(a.tanggal) AS tanggal, -- Mengambil hanya tanggal
                    s.nama_shift AS jadwal_shift, 
                    a.waktu_masuk,
                    a.waktu_keluar,
                    a.status_kehadiran,
                    p.hari_libur -- Menambahkan kolom hari_libur dari tabel pegawai
                FROM absensi a
                JOIN pegawai p ON a.pegawai_id = p.id
                JOIN users u ON p.user_id = u.id
                LEFT JOIN jadwal_shift js ON a.jadwal_shift_id = js.id
                LEFT JOIN shift s ON js.shift_id = s.id
                WHERE u.username = :username
                    AND MONTH(a.tanggal) = MONTH(CURRENT_DATE())  -- Filter bulan ini
                    AND YEAR(a.tanggal) = YEAR(CURRENT_DATE())    -- Filter tahun ini
                ORDER BY a.tanggal DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mengembalikan data absensi dalam format JSON
        echo json_encode([
            'status' => 'success',
            'data' => $results
        ]);
    } else {
        http_response_code(401);
        throw new Exception('Username atau password salah');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

unset($pdo);