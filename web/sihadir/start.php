<?php
session_start(); // Mulai session

// Cek apakah session 'setup' telah diset, dan jika tidak, redirect ke halaman login atau dashboard
if (!isset($_SESSION['setup']) || $_SESSION['setup'] !== true) {
    header('Location: index.php'); // Atau redirect ke halaman lain, misalnya dashboard jika login berhasil
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Si Hadir - Sistem Informasi Kehadiran</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #60a5fa;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --background: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.7);
            --hover-bg: rgba(255, 255, 255, 0.9);
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
        }

        .container {
            width: 100%;
            max-width: 1200px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 3rem;
            animation: fadeIn 0.8s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .title {
            font-size: 3rem;
            font-weight: 700;
            color: #1e293b; /* Ganti dengan hitam */
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .feature-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.7);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-card:hover {
            background: var(--hover-bg);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 0.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .cta-section {
            text-align: center;
            margin-top: 3rem;
            padding-top: 3rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .cta-text {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            min-width: 200px;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        .note {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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

            .features {
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
            <p class="subtitle">
                Sistem absensi modern yang memudahkan pengelolaan kehadiran karyawan dengan teknologi terkini
            </p>
        </header>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="feature-title">Absensi Mobile</h3>
                <p class="feature-description">
                    Lakukan absensi dengan mudah melalui aplikasi Android yang user-friendly dan efisien
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="feature-title">Real-time Tracking</h3>
                <p class="feature-description">
                    Pantau kehadiran karyawan secara real-time dengan dashboard yang informatif
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Laporan Lengkap</h3>
                <p class="feature-description">
                    Akses laporan detail dan analisis kehadiran karyawan dengan mudah
                </p>
            </div>
        </div>

        <div class="cta-section">
            
            <div class="buttons">
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Daftar Sebagai Admin
                </a>
                <a href="#" class="btn btn-secondary">
                    <i class="fas fa-download"></i>
                    Download Aplikasi
                </a>
            </div>
            <div class="note">
                <i class="fas fa-info-circle"></i>
                Daftar sebagai admin terlebih dahulu untuk dapat mendaftarkan karyawan Anda ke dalam sistem
            </div>
        </div>
    </div>
</body>
</html>