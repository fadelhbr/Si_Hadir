<?php
// koneksi ke database
$conn = new mysqli("localhost", "root", "", "si_hadir");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil data dari request Android
$username = $_POST['username'];
$password = $_POST['password'];

// Log username dan password yang diterima
error_log("Username: $username");

$sql = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Verifikasi password menggunakan password_verify()
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Login berhasil",
            "nama_lengkap" => $row['nama_lengkap'], // Nama
            "role" => $row['role'] // Role 
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Username atau password salah"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Username atau password salah"]);
}

$conn->close();
?>