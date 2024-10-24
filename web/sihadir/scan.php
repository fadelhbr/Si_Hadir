<?php
// Sertakan file auth.php untuk koneksi database
include 'auth/auth.php';

// Fungsi untuk menghasilkan kode unik 6 karakter (angka, huruf kecil, huruf besar)
function generateUniqueCode($pdo) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = 6; // Panjang kode unik
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE kode_unik = :kode_unik");
        $stmt->execute(['kode_unik' => $code]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    
    return $code;
}

// Proses untuk menghasilkan QR code
$uniqueCode = '';
$url = '';

// Cek apakah sudah ada kode unik di tabel absensi
$stmt = $pdo->prepare("SELECT kode_unik FROM absensi LIMIT 1");
$stmt->execute();
$existingCode = $stmt->fetchColumn();

if ($existingCode) {
    // Jika ada kode unik yang sudah ada
    $uniqueCode = $existingCode;
} else {
    // Jika tidak ada, generate kode unik baru
    $uniqueCode = generateUniqueCode($pdo);
}

// Buat URL untuk QR code
$size = '200x200'; // Ukuran QR code
$url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($uniqueCode);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Istok+Web&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
    font-family: 'Istok Web', sans-serif;
    font-size: 14px;
    height: 100vh;
    overflow: hidden; /* Prevent scrolling */
    position: relative; /* For positioning snowflakes */
    background: linear-gradient(45deg, #ff7e5f, #feb47b, #ff6a88, #ffb199, #ff9a9e, #00c6ff, #0072ff, #6a82fb, #d3cce3, #f6d6e1); /* Smooth gradient with more colors */
    background-size: 400% 400%;
    animation: gradient 15s ease infinite; /* Animated gradient background */
}
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Snow effect */
        .snow {
            position: absolute;
            top: -10%;
            left: 50%;
            width: 10px;
            height: 10px;
            background: white;
            opacity: 0.8;
            border-radius: 50%;
            animation: fall linear infinite;
            will-change: transform;
        }

        @keyframes fall {
            0% {
                transform: translateX(-50%) translateY(0);
                opacity: 0.8;
            }
            100% {
                transform: translateX(-50%) translateY(100vh);
                opacity: 0;
            }
        }

        .container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Center the container */
            background-color: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 90%; /* Responsive width */
            max-width: 400px; /* Maximum width */
            text-align: center; /* Center content horizontally */
            z-index: 1; /* On top of the background */
        }

        .login-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 36px;
            color: #333;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8); /* Text shadow for depth */
        }

        .unique-code {
            font-weight: bold; /* Make unique code bold */
            font-size: 35px; /* Adjust font size if needed */
            margin-top: 15px; /* Add margin for spacing */
            color: #007bff; /* Use a blue color for unique code */
            transition: color 0.3s; /* Animation for color change */
        }

        .unique-code:hover {
            color: #0056b3; /* Darker blue on hover */
        }

        img {
            margin: 20px 0; /* Add margin around the QR code */
            border: 5px solid #007bff; /* Add a border around the QR code */
            border-radius: 10px; /* Slightly rounded corners for the border */
            padding: 10px; /* Padding inside the border */
            background-color: #f9f9f9; /* Light background behind the QR code */
            transition: box-shadow 0.5s; /* Animation for shadow */
        }

        img:hover {
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.5); /* Shadow effect on hover */
        }
    </style>
    
</head>
<body>

    <div class="container">
        <h1 class="login-title">Scan Absensi</h1>

        <?php if ($url): ?>
            <img src="<?php echo $url; ?>" alt="QR Code">
            <p class="unique-code"><?php echo htmlspecialchars($uniqueCode); ?></p>
        <?php else: ?>
            <p>No unique code found.</p>
        <?php endif; ?>
    </div>

    <!-- Generate falling snowflakes -->
    <script>
    function createSnowflakes() {
        for (let i = 0; i < 100; i++) {
            let snowflake = document.createElement('div');
            snowflake.className = 'snow';
            snowflake.style.left = Math.random() * 100 + 'vw'; // Random horizontal position
            snowflake.style.top = Math.random() * -100 + 'vh'; // Random starting position above the viewport
            snowflake.style.animationDuration = Math.random() * 3 + 2 + 's'; // Random falling speed
            snowflake.style.opacity = Math.random(); // Random opacity
            document.body.appendChild(snowflake);
        }
    }
    createSnowflakes();
</script>

</body>
</html>
