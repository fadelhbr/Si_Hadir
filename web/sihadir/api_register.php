<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "abc54321", "si_hadir");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_POST['email'])) {
    echo json_encode(["status" => "error", "message" => "Email tidak dikirim"]);
    exit;
}


// Ambil data dari request (misalnya dari form registrasi)
$nama = $_POST['nama'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
$no_telepon = $_POST['no_telepon'];
$alamat = $_POST['alamat'];
$tanggal_masuk = $_POST['tanggal_masuk'];
$role = $_POST['role'];
$qr_code = $_POST['qr_code'];
$is_active = $_POST['is_active'];

// Query untuk menyimpan pengguna baru ke database
$sql = "INSERT INTO Karyawan (nama, username, email, password, no_telepon, alamat, tanggal_masuk, role, qr_code, is_active) VALUES ('$nama', '$username', '$email', '$password', '$no_telepon', '$alamat', '$tanggal_masuk', '$role', '$qr_code', '$is_active')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Registrasi berhasil"]);
} else {
    echo json_encode(["status" => "error", "message" => "Registrasi gagal: " . $conn->error]);
}

$conn->close();
?>
