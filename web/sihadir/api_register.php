<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "si_hadir");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil data dari request (misalnya dari form registrasi)
$username = $_POST['username'];
$password = $_POST['password'];

// Hash password menggunakan bcrypt
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Query untuk menyimpan pengguna baru ke database
$sql = "INSERT INTO akun (username, password) VALUES ('$username', '$hashedPassword')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Registrasi berhasil"]);
} else {
    echo json_encode(["status" => "error", "message" => "Registrasi gagal: " . $conn->error]);
}

$conn->close();
?>
