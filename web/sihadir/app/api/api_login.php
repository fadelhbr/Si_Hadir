<?php
// koneksi ke database
$conn = new mysqli("localhost", "root", "", "hadir");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil data dari request Android
$username = $_POST['username'];
$password = $_POST['password'];

// Log username dan password yang diterima
error_log("Username: $username");
error_log("Password: $password");

// Query untuk memeriksa login
$sql = "SELECT * FROM karyawan WHERE username='$username'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($password === $row['password']) {
        echo json_encode([
            "status" => "success",
            "message" => "Login berhasil",
            "nama" => $row['nama'], // Nama
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
