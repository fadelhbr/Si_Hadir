<?php
// Proses logout jika konfirmasi diterima
if (isset($_POST['logout']) && $_POST['logout'] == 'yes') {
    session_start();

    // Menghapus semua session
    $_SESSION = array();

    // Menghancurkan session
    session_destroy();

    // Redirect ke halaman login
    header("Location: login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Logout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Istok+Web&display=swap" rel="stylesheet">
    <style>
        /* Add your CSS styling here */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Istok Web', sans-serif;
            font-size: 14px;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .logout-container {
            background-color: #ffffff;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
            text-align: center;
        }

        .logout-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 32px;
            color: rgba(0, 0, 0, 0.8);
            margin-bottom: 10px;
        }

        .logout-message {
            font-size: 16px;
            color: #555;
            margin-bottom: 35px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .logout-button, .cancel-button {
            width: 150px;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-button {
            background-color: #28a745;
            color: #fff;
        }

        .logout-button:hover {
            background-color: #218838;
        }

        .cancel-button {
            background-color: #dc3545;
            color: #fff;
        }

        .cancel-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

    <div class="logout-container">
        <h1 class="logout-title">Konfirmasi</h1>
        <p class="logout-message">Apakah Anda yakin ingin keluar?</p>
        <div class="button-group">
            <!-- Tombol logout -->
            <form action="" method="post">
                <input type="hidden" name="logout" value="yes">
                <button type="submit" class="logout-button">Ya, Logout</button>
            </form>
            <!-- Tombol batal kembali ke dashboard -->
            <a href="dashboardAdmin.php">
                <button class="cancel-button">Tidak, Kembali</button>
            </a>
        </div>
    </div>

</body>
</html>
