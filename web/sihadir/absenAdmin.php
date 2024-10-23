<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
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
    
    header('Location: index.php');
    exit;
}
    // Database Connection
    $host = 'localhost';
    $dbname = 'si_hadir';
    $username = 'root';
    $password = '';
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->userId)) {
        $userId = $data->userId;
        $waktu = date('Y-m-d H:i:s');
        
        // Simpan ke database
        $stmt = $conn->prepare("INSERT INTO absensi (user_id, waktu_absen) VALUES (:user_id, :waktu)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':waktu', $waktu);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Absen berhasil direkap!"]);
        } else {
            echo json_encode(["message" => "Gagal menyimpan data."]);
        }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Si Hadir - Absen</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <!-- Link Google Fonts untuk Poppins -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        
        <style>
            /* Mengatur font Poppins hanya untuk <strong> di dalam sidebar-heading */
            #sidebar-wrapper .sidebar-heading strong {
                font-family: 'Poppins', sans-serif; /* Menggunakan font Poppins hanya untuk Si Hadir */
                font-weight: 900; /* Menebalkan tulisan */
                font-size: 28px;  /* Membesarkan ukuran font */
            }
            
            /* Menghilangkan tombol toggle navbar dan memastikan navbar selalu terlihat */
            .navbar-toggler {
                display: none;
            }
            
            #navbarSupportedContent {
                display: flex !important;
            }
            
            .sidebar-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            vertical-align: middle;
            }

            /* Menyesuaikan tampilan navbar untuk layar kecil */
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
                        .pesan-status {
                padding: 10px;
                border-radius: 5px;
                margin-top: 10px;
            }

            .pesan-status.sukses {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .pesan-status.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
        
    </head>
    <body>
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            <div class="border-end-0 bg-white" id="sidebar-wrapper">
                <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="dashboardAdmin.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>
                        </svg>
                        Dashboard
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="addMember.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M720-400v-120H600v-80h120v-120h80v120h120v80H800v120h-80Zm-360-80q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm80-80h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0-80Zm0 400Z"/>
                        </svg>
                        Add Member
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="riwayatKehadiranAdmin.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z"/>
                        </svg>
                        Riwayat kehadiran
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="absenAdmin.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z"/>
                        </svg>
                        Absen
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="pengumumanAdmin.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M720-440v-80h160v80H720Zm48 280-128-96 48-64 128 96-48 64Zm-80-480-48-64 128-96 48 64-128 96ZM200-200v-160h-40q-33 0-56.5-23.5T80-440v-80q0-33 23.5-56.5T160-600h160l200-120v480L320-360h-40v160h-80Zm240-182v-196l-98 58H160v80h182l98 58Zm120 36v-268q27 24 43.5 58.5T620-480q0 41-16.5 75.5T560-346ZM300-480Z"/>
                        </svg>
                        Pengumuman
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="settingsAdmin.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/>
                        </svg>
                        Setting
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="logoutAdmin.php">
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
                <body>
    <div class="container-fluid">
        <div class="attendance-container">
        <div id="manual-result" class="status-message mt-3"></div>
            <h2 class="mb-4">Absensi</h2>
            
            <div class="attendance-options">
                <button id="qr-option" class="btn btn-primary">input toket</button>
                <button id="manual-option" class="btn btn-secondary">Absen Manual</button>
            </div>

            <div id="qr-reader" style="display: none;">
                <div class="text-center mb-3">
                    <video id="qr-video" playsinline></video>
                    <canvas id="qr-canvas" style="display: none;"></canvas>
                </div>
                <div class="text-center">
                    <button id="start-scan" class="btn btn-primary">Mulai Scan</button>
                </div>
                <div id="qr-result" class="status-message mt-3"></div>
            </div>

            <div id="manual-form" class="manual-form">
    <form id="attendance-form" method="POST" enctype="multipart/form-data">
        <div class="attendance-type">
        <div id="manual-result" class="status-message mt-3"></div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="attendance_type" id="sakit" value="sakit">
                <label class="form-check-label" for="sakit">Sakit</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="attendance_type" id="ijin" value="ijin">
                <label class="form-check-label" for="ijin">Izin</label>
            </div>
        </div>
        
        <div id="description-area" class="description-area">
            <div class="form-group">
                <label for="description">Keterangan:</label>
                <div id="manual-result" class="status-message mt-3"></div>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Mengapa anda izin?"></textarea>
            </div>
        </div>
        
                <form id="absenForm">
                <div id="manual-result" class="status-message mt-3"></div>
        <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
</div>

        <p id="status"></p>

        </div>

        <script>
        document.getElementById('absenForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Mencegah reload halaman

    const userId = document.getElementById('userId').value;

    // Kirim data ke server melalui AJAX atau Fetch API
    fetch('process_absen.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: userId })
    })
    .then(response => response.json())
    .then(data => {
        // Tampilkan status absen
        document.getElementById('status').innerText = data.message;
    })
    .catch(error => console.error('Error:', error));
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qrOption = document.getElementById('qr-option');
            const manualOption = document.getElementById('manual-option');
            const qrReader = document.getElementById('qr-reader');
            const manualForm = document.getElementById('manual-form');
            const video = document.getElementById('qr-video');
            const startScanButton = document.getElementById('start-scan');
            const attendanceTypeInputs = document.querySelectorAll('input[name="attendance_type"]');
            const descriptionArea = document.getElementById('description-area');

            let scanner = null;

            // Toggle antara opsi QR dan manual
            qrOption.onclick = () => {
                qrReader.style.display = 'block';
                manualForm.style.display = 'none';
            };

            manualOption.onclick = () => {
                qrReader.style.display = 'none';
                manualForm.style.display = 'block';
            };

            // Tampilkan/sembunyikan area deskripsi berdasarkan jenis absensi
            attendanceTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    descriptionArea.style.display = 
                        (this.value === 'ijin') ? 'block' : 'none';
                });
            });

            // Set initial state of description area based on default selected radio
            descriptionArea.style.display = 
                document.querySelector('input[name="attendance_type"]:checked').value === 'ijin' 
                    ? 'block' 
                    : 'none';

            // Penanganan pemindaian QR
            startScanButton.onclick = () => {
                if (scanner) {
                    scanner.stop();
                    scanner = null;
                    startScanButton.textContent = 'Mulai Scan';
                    return;
                }

                scanner = new Instascan.Scanner({ video: video });
                scanner.addListener('scan', function(content) {
                    submitAttendance(content);
                });

                Instascan.Camera.getCameras()
                    .then(function(cameras) {
                        if (cameras.length > 0) {
                            // Coba gunakan kamera belakang terlebih dahulu
                            const backCamera = cameras.find(camera => camera.name.toLowerCase().includes('back'));
                            scanner.start(backCamera || cameras[0]);
                            startScanButton.textContent = 'Hentikan Scan';
                        } else {
                            console.error('Tidak ada kamera ditemukan.');
                            alert('Tidak ada kamera ditemukan.');
                        }
                    })
                    .catch(function(err) {
                        console.error(err);
                        alert('Error mengakses kamera: ' + err.message);
                    });
            };

            // Penanganan pengiriman form
            document.getElementById('attendance-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('manual-result');
                    resultDiv.textContent = data.message;
                    resultDiv.className = 'status-message mt-3 ' + (data.success ? 'success' : 'error');
                    
                    if (data.success) {
                        this.reset();
                        descriptionArea.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const resultDiv = document.getElementById('manual-result');
                    resultDiv.textContent = 'Terjadi kesalahan pada sistem';
                    resultDiv.className = 'status-message mt-3 error';
                });
            });

            function submitAttendance(qrData) {
                const formData = new FormData();
                formData.append('qr_data', qrData);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('qr-result');
                    resultDiv.textContent = data.message;
                    resultDiv.className = 'status-message mt-3 ' + (data.success ? 'success' : 'error');
                    
                    if (data.success && scanner) {
                        scanner.stop();
                        scanner = null;
                        startScanButton.textContent = 'Mulai Scan';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const resultDiv = document.getElementById('qr-result');
                    resultDiv.textContent = 'Terjadi kesalahan pada sistem';
                    resultDiv.className = 'status-message mt-3 error';
                });
            }
        });
            </script>
            <script src="js/scripts.js"></script>
                <!-- Custom JS to handle sidebar toggle -->
                <script>
                    const sidebarToggle = document.getElementById('sidebarToggle');
                    const sidebarWrapper = document.getElementById('sidebar-wrapper');
            
                    sidebarToggle.addEventListener('click', function () {
                        sidebarWrapper.classList.toggle('collapsed');
                    });
            </script>
        </body>
    </html>