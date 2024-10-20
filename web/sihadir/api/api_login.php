<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "abc54321", "si_hadir");

// Cek koneksi database
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $conn->connect_error]));
}

// Ambil data dari request Android
$username = $_POST['username'];
$password = $_POST['password'];

// Log username dan password yang diterima
error_log("Username: $username");
error_log("Password: $password");

// Cek apakah username dan password sudah diterima
if (empty($username) || empty($password)) {
    die(json_encode(["status" => "error", "message" => "Username dan password tidak boleh kosong"]));
}

// Query untuk memeriksa login dengan username dan password
$sql = "SELECT * FROM Karyawan WHERE username='$username' AND password='$password'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Jika username dan password cocok
    echo json_encode(["status" => "success", "message" => "Login berhasil"]);
} else {
    // Jika username atau password salah
    echo json_encode(["status" => "error", "message" => "Username atau password salah"]);
}

// Tutup koneksi database
$conn->close();
?>
