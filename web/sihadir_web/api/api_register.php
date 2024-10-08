<?php

header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'si_hadir');

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'register') {
        // Mengambil data dari POST
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $no_telpon = $_POST['no_telpon'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password

        // Query untuk registrasi
        $sql = "INSERT INTO akun (nama_lengkap, email, tanggal_lahir, no_telpon, username, password) VALUES ('$nama_lengkap', '$email', '$tanggal_lahir', '$no_telpon', '$username', '$password')";

        if ($conn->query($sql) === TRUE) {
            $response = array('status' => 'success', 'message' => 'Registrasi Berhasil');
        } else {
            $response = array('status' => 'error', 'message' => 'Registrasi Gagal: ' . $conn->error);
        }
    } elseif ($action === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Query untuk login
        $sql = "SELECT * FROM akun WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Verifikasi password
            if (password_verify($password, $row['password'])) {
                $response = array('status' => 'success', 'message' => 'Login Berhasil');
            } else {
                $response = array('status' => 'error', 'message' => 'Password salah');
            }
        } else {
            $response = array('status' => 'error', 'message' => 'Username tidak ditemukan');
        }
    }
    echo json_encode($response);
}
?>
