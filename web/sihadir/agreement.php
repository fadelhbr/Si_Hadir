<?php
session_start();

if (!isset($_SESSION['setup']) || $_SESSION['setup'] !== true) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Sistem Informasi Kehadiran</title>
    <link rel="icon" type="image/x-icon" href="assets/icon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #60a5fa;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --background: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.9);
            --hover-bg: rgba(255, 255, 255, 0.95);
            --accent: #f59e0b;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            animation: fadeIn 0.8s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
            transform: rotate(-10deg);
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: rotate(0deg);
        }

        .logo i {
            font-size: 2.5rem;
            color: white;
        }

        .title {
            font-size: 3.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .info-section {
            background: var(--background);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 2rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-title i {
            font-size: 1.25rem;
        }

        .info-list {
            list-style-type: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.5);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.8);
        }

        .info-item i {
            color: var(--primary);
            font-size: 1.25rem;
            padding-top: 0.25rem;
        }

        .info-item p {
            margin: 0;
            line-height: 1.6;
            color: var(--text-secondary);
        }

        .agreement-section {
            margin-top: 3rem;
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .checkbox-wrapper {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .checkbox-label:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        .checkbox-input {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .btn {
            padding: 1.25rem 3rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            min-width: 250px;
            justify-content: center;
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
            opacity: 0.5;
            pointer-events: none;
        }

        .btn-primary.active {
            opacity: 1;
            pointer-events: all;
        }

        .btn-primary.active:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.2) 50%,
                    transparent 100%);
            transition: left 0.5s ease;
        }

        .btn-primary.active:hover::before {
            left: 100%;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem;
            }

            .title {
                font-size: 2.5rem;
            }

            .info-section {
                padding: 1.5rem;
            }

            .info-list {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1 class="title">Si Hadir</h1>
            <p class="subtitle">Sistem Informasi Presensi Modern untuk Pengelolaan Karyawan yang Lebih Efektif</p>
        </header>

        <div class="info-section">
            <h2 class="info-title">
                <i class="fas fa-circle-info"></i>
                Informasi Penting Sistem
            </h2>

            <ul class="info-list">
                <li class="info-item">
                    <i class="fas fa-user-lock"></i>
                    <p>Perubahan data akun owner dapat dilakukan melalui proses recovery dengan verifikasi email.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-key"></i>
                    <p>Pemulihan akun dapat dilakukan melalui email yang terdaftar. Kode OTP akan dikirimkan ke email
                        Anda untuk verifikasi.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-clock"></i>
                    <p>Anda harus menambahkan jadwal shift karyawan terlebih dahulu sebelum dapat menambahkan karyawan.
                    </p>
                </li>
                <li class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <p>Anda dapat mengatur hari libur untuk masing-masing karyawan.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-desktop"></i>
                    <p>Pemantauan aktivitas presensi dapat dilakukan di menu monitor presensi.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-user-check"></i>
                    <p>Karyawan yang pulang lebih awal, tidak presensi pulang, atau terlambat tetap dihitung sebagai
                        hadir.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-plane-departure"></i>
                    <p>Karyawan dapat mengajukan request cuti dan menunggu persetujuan owner.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-database"></i>
                    <p>Data presensi karyawan akan dikosongkan setiap setahun sekali.</p>
                </li>
                <li class="info-item">
                    <i class="fas fa-file-alt"></i>
                    <p>Harap rutin membuat laporan di menu laporan.</p>
                </li>
            </ul>
        </div>

        <div class="agreement-section">
            <div class="checkbox-wrapper">
                <label class="checkbox-label">
                    <input type="checkbox" class="checkbox-input" id="agreement">
                    <span>Saya menyetujui dan telah membaca semua informasi di atas</span>
                </label>
            </div>
            <button class="btn btn-primary" id="continueBtn">
                <i class="fas fa-arrow-right"></i>
                Lanjutkan
            </button>
        </div>
    </div>

    <script>
        const checkbox = document.getElementById('agreement');
        const continueBtn = document.getElementById('continueBtn');

        checkbox.addEventListener('change', function () {
            if (this.checked) {
                continueBtn.classList.add('active');
            } else {
                continueBtn.classList.remove('active');
            }
        });

        continueBtn.addEventListener('click', function () {
            if (checkbox.checked) {
                window.location.href = 'register.php';
            }
        });
    </script>
</body>

</html>