<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if the user role is employee
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    // Unset session variables and destroy session
    session_unset();
    session_destroy();
    
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
            <meta name="description" content="" />
            <meta name="author" content="" />
            <title>Si Hadir - Dashboard</title>
            <!-- Favicon-->
            <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
            <!-- Core theme CSS (includes Bootstrap)-->
            <link href="../../../assets/css/styles.css" rel="stylesheet" />
            <!-- Link Google Fonts untuk Poppins -->
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
            <style>
                #sidebar-wrapper .sidebar-heading strong {
                    font-family: 'Poppins', sans-serif;
                    font-weight: 900;
                    font-size: 28px;
                }
                
                .navbar-toggler {
                    display: none;
                }
                
                #navbarSupportedContent {
                    display: flex !important;
                }
                
                .status-card {
                    transition: transform 0.3s, box-shadow 0.3s;
                }
                .status-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
                }

                .sidebar-icon {
                    width: 24px;
                    height: 24px;
                    margin-right: 10px;
                    vertical-align: middle;
                }

                .chart-container {
                    position: relative;
                    height: 300px;
                    width: 100%;
                    margin-bottom: 20px;
                }

                @media (max-width: 991.98px) {
                    .navbar-nav {
                        flex-direction: row;
                    }
                    
                    .navbar-nav .nav-item {
                        padding-right: 10px;
                    }
                    
                    .navbar-nav .dropdown-menu {
                        position: absolute;
                    }
                }
            </style>
        </head>
        <body class="bg-blue-50">
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            <div class="border-end-0 bg-white" id="sidebar-wrapper">
              <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="dashboard.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>
                        </svg>
                        Dashboard
                    </a>    
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="attendanceMonitor.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z"/>
                        </svg>
                        Monitor Absensi
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="schedule.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                        </svg>
                        Jadwal
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="manageMember.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d" width="24" height="24">
                            <path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 2.02 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        Manajemen Staff
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="permit.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z"/>
                        </svg>
                        Cuti & Perizinan
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="report.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" width="24" height="24" stroke="#6c757d" fill="none" stroke-width="2">
                            <path d="M6 2C5.44772 2 5 2.44772 5 3V21C5 21.5523 5.44772 22 6 22H18C18.5523 22 19 21.5523 19 21V7L14 2H6Z" />
                            <path d="M13 2V7H19" />
                        </svg>
                        Laporan
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="settings.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/>
                        </svg>
                        Setting
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/>
                        </svg>
                        Log out
                    </a>
                </div>
            </div>
            <!-- Page content wrapper-->
            <div id="page-content-wrapper">
                <!-- Top navigation-->
                <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
                    <div class="container-fluid">
                        <button class="btn btn-primary" id="sidebarToggle">☰</button>
                        <div id="navbarSupportedContent"></div>
                    </div>
                </nav>

                <!-- Page content-->
                <div class="container-fluid p-4">
                    <h1 class="text-3xl font-semibold mb-4">Dashboard</h1>

                    

                    <!-- Charts Section -->
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Monthly Attendance Trend -->
                        <div class="bg-white shadow rounded-lg p-4 mb-4">
                            <h2 class="text-lg font-semibold mb-4">Tren Kehadiran Bulanan</h2>
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>

                        <!-- Weekly Attendance -->
                        <div class="bg-white shadow rounded-lg p-4 mb-4">
                            <h2 class="text-lg font-semibold mb-4">Kehadiran Minggu Ini</h2>
                            <div class="chart-container">
                                <canvas id="weeklyChart"></canvas>
                            </div>
                        </div>
                    </div>

                        <!-- tabel kehadiran -->
                    <div class="bg-white shadow rounded-lg p-4 mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Staff</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Divisi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Dina Darius</td>
                                <td class="px-6 py-4 whitespace-nowrap">Belum Ditentukan</td>
                                <td class="px-6 py-4 whitespace-nowrap">Masih Berjalan</td>
                                <td class="px-6 py-4 whitespace-nowrap">Pagi</td>
                                <td class="px-6 py-4 whitespace-nowrap text-blue-500">08:00</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Alfina Amalia</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Pagi (08:00 - 12:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap">Selesai</td>
                                <td class="px-6 py-4 whitespace-nowrap">Pagi</td>
                                <td class="px-6 py-4 whitespace-nowrap text-blue-500">08:15</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Suhada Akbra</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Pagi (08:00 - 12:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap">Selesai</td>
                                <td class="px-6 py-4 whitespace-nowrap">Siang</td>
                                <td class="px-6 py-4 whitespace-nowrap text-blue-500">08:15</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Diah</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Siang (12:00 - 16:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap">Selesai</td>
                                <td class="px-6 py-4 whitespace-nowrap">Siang</td>
                                <td class="px-6 py-4 whitespace-nowrap text-blue-500">12:00</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Diah</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Siang (12:00 - 16:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-500">Tidak Absen Pulang</td>
                                <td class="px-6 py-4 whitespace-nowrap">Petang</td>
                                <td class="px-6 py-4 whitespace-nowrap text-blue-500">12:00</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Diah</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Malam (16:00 - 20:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-500">Tidak Masuk</td>
                                <td class="px-6 py-4 whitespace-nowrap">Petang</td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-500">-</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">Diah</td>
                                <td class="px-6 py-4 whitespace-nowrap">Jadwal Malam (16:00 - 20:00)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-orange-500">Cuti</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="../../../assets/js/scripts.js"></script>

        <!-- Custom JS to handle sidebar toggle -->
        <script>
            // Monthly Attendance Trend Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
                    datasets: [
                        {
                            label: 'Tepat Waktu',
                            data: [85, 82, 88, 85, 90, 87],
                            borderColor: 'rgb(59, 130, 246)',
                            tension: 0.1
                        },
                        {
                            label: 'Terlambat',
                            data: [10, 12, 8, 11, 7, 9],
                            borderColor: 'rgb(234, 179, 8)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Weekly Attendance Chart
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                    datasets: [
                        {
                            label: 'Hadir',
                            data: [45, 43, 44, 42, 41],
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        },
                        {
                            label: 'Lembur',
                            data: [5, 3, 4, 6, 2],
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');

            sidebarToggle.addEventListener('click', function() {
                sidebarWrapper.classList.toggle('collapsed');
            });
        </script>
    </body>
</html>