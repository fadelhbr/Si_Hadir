<?php
session_start();

require_once '../../../app/auth/auth.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    // Unset session variables and destroy session
    session_unset();
    session_destroy();
    
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    header('Location: ../../../login.php');
    exit;
}

// Get pegawai_id from database based on user_id
function getPegawaiId($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM pegawai WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

function getEmployeePermitData($pdo, $pegawaiId) {
    if (!$pegawaiId) return [];
    
    $sql = "SELECT i.*, u.nama_lengkap as nama_staff, i.status
            FROM izin i 
            INNER JOIN pegawai p ON i.pegawai_id = p.id 
            INNER JOIN users u ON p.user_id = u.id 
            WHERE i.pegawai_id = :pegawai_id 
            ORDER BY i.tanggal DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pegawai_id' => $pegawaiId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEmployeeLeaveData($pdo, $pegawaiId) {
    if (!$pegawaiId) return [];
    
    $sql = "SELECT c.*, u.nama_lengkap as nama_staff, c.status
            FROM cuti c 
            INNER JOIN pegawai p ON c.pegawai_id = p.id 
            INNER JOIN users u ON p.user_id = u.id 
            WHERE c.pegawai_id = :pegawai_id 
            ORDER BY c.tanggal_mulai DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pegawai_id' => $pegawaiId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get pegawai_id once and store it
$pegawaiId = null;
if (isset($_SESSION['user_id'])) {
    $pegawaiId = getPegawaiId($pdo, $_SESSION['user_id']);
    if (!$pegawaiId) {
        // If pegawai_id not found, redirect to error page or handle appropriately
        header('Location: ../../../login.php?error=no_employee_record');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Print the POST data
    error_log(print_r($_POST, true));
    
    try {
        // Get pegawai_id
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User ID not found in session');
        }
        
        $stmt = $pdo->prepare("SELECT id FROM pegawai WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $pegawaiId = $stmt->fetchColumn();
        
        if (!$pegawaiId) {
            throw new Exception('Pegawai ID not found');
        }
        
        // Process Permit Request
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'izin') {
            $stmt = $pdo->prepare("
                INSERT INTO izin (pegawai_id, tanggal, jenis_izin, keterangan, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            $result = $stmt->execute([
                $pegawaiId,
                $_POST['permitDate'],
                $_POST['permitType'],
                $_POST['permitDescription']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert permit request');
            }
        }
        
        // Process Leave Request
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'cuti') {
            // Calculate leave duration
            $date1 = new DateTime($_POST['leaveStartDate']);
            $date2 = new DateTime($_POST['leaveEndDate']);
            $interval = $date1->diff($date2);
            $durasi_cuti = $interval->days + 1;
            
            $stmt = $pdo->prepare("
                INSERT INTO cuti (pegawai_id, tanggal_mulai, tanggal_selesai, durasi_cuti, keterangan, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            $result = $stmt->execute([
                $pegawaiId,
                $_POST['leaveStartDate'],
                $_POST['leaveEndDate'],
                $durasi_cuti,
                $_POST['leaveDescription']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert leave request');
            }
        }
        
        // Redirect on success
        header('Location: permit.php?status=success');
        exit;
        
    } catch (Exception $e) {
        // Log the error
        error_log($e->getMessage());
        header('Location: permit.php?status=error&message=' . urlencode($e->getMessage()));
        exit;
    }
}

// Get updated data for tables
$dataIzin = getEmployeePermitData($pdo, $pegawaiId);
$dataCuti = getEmployeeLeaveData($pdo, $pegawaiId);
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
        <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="../../../assets/css/styles.css" rel="stylesheet" />
        <!-- Link Google Fonts untuk Poppins -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        
        
        <style>
            .alert {
    margin-bottom: 1rem;
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-dismissible .btn-close {
    padding: 0.75rem 1rem;
}

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

            .hidden {
                display: none;
            }
            </style>

<style>
    /* Style untuk switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
        margin: 0 10px; /* Menambahkan margin di sekitar switch */
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        border-radius: 50%;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .hidden {
        display: none;
    }
</style>

<style>
    .btn-izin {
        background-color: #007bff; /* Warna biru */
        color: white;
        transition: background-color 0.3s, color 0.3s;
    }
    
    .btn-cuti {
        background-color: #6c757d; /* Warna abu-abu */
        color: white;
        transition: background-color 0.3s, color 0.3s;
    }

    .btn-active {
        background-color: #0056b3 !important; /* Warna tombol aktif */
        color: white !important;
    }
</style>

    </head>
    <body>
    <?php if (isset($_GET['status'])): ?>
        <div class="alert <?php echo $_GET['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['status'] === 'success') {
                echo 'Pengajuan berhasil disubmit!';
            } else {
                echo 'Error: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            <div class="border-end-0 bg-white" id="sidebar-wrapper">
                <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="attendance.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                            <path d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z"/>
                        </svg>
                        Absen
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="attendanceHistory.php" style="display: flex; align-items: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#6c757d" style="margin-right: 8px;">
                            <path d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/>
                        </svg>
                        <span>Riwayat kehadiran</span>
                    </a>
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="permit.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                                <path d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z"/>
                            </svg>
                            Cuti & Perizinan
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
                        <div id="navbarSupportedContent">
                        </div>
                    </div>
                </nav>
                <!-- Page content-->
                <div class="container-fluid p-4">
                <div id="alertContainer">
                <?php if (isset($_GET['status'])): ?>
            <div class="alert <?php echo $_GET['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php 
                if ($_GET['status'] === 'success') {
                    echo 'Pengajuan berhasil disubmit!';
                } else {
                    echo 'Error: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
    <h1 class="text-3xl font-semibold mb-4">Cuti & Perizinan</h1>
    <div class="flex flex-col md:flex-row items-center justify-between mb-4 space-y-2 md:space-y-0 md:space-x-2">
        <div class="flex space-x-2">
            <!-- Changed to two separate buttons for leave and permit requests -->
            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" id="permitRequestBtn" data-bs-toggle="modal" data-bs-target="#permitRequestModal">Pengajuan Izin</button>
            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" id="leaveRequestBtn" data-bs-toggle="modal" data-bs-target="#leaveRequestModal">Pengajuan Cuti</button>
        </div>
        <input type="text" class="border border-gray-300 rounded px-2 py-1 w-full md:w-64" placeholder="Cari riwayat pengajuan...">
    </div>
</div>

<div class="bg-white shadow rounded-lg p-4 mb-4">
    <!-- Table switch for viewing history -->
    <div class="flex items-center mb-4">
        <label class="switch">
            <input type="checkbox" id="tableSwitch" onchange="toggleTableswitch()">
            <span class="slider"></span>
        </label>
        <span id="tableLabel" class="ml-2">Riwayat Izin</span>
    </div>

    <!-- Permit History Table -->
    <div id="izinTable" class="table-container">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Jenis Izin</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($dataIzin as $row): ?>
                <tr>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['jenis_izin']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="<?php echo $row['status'] == 'disetujui' ? 'bg-green-500' : ($row['status'] == 'pending' ? 'bg-yellow-500' : 'bg-red-500'); ?> text-white py-1 px-2 rounded">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Leave History Table -->
    <div id="cutiTable" class="table-container hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal Mulai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal Selesai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Durasi Cuti</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($dataCuti as $row): ?>
                <tr>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal_mulai']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal_selesai']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['durasi_cuti']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="<?php echo $row['status'] == 'disetujui' ? 'bg-green-500' : ($row['status'] == 'pending' ? 'bg-yellow-500' : 'bg-red-500'); ?> text-white py-1 px-2 rounded">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Permit Request Modal -->
<div class="modal fade" id="permitRequestModal" tabindex="-1" aria-labelledby="permitRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permitRequestModalLabel">Pengajuan Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
    <form id="permitRequestForm" method="POST" action="permit.php">
        <input type="hidden" name="form_type" value="izin">
        <div class="mb-3">
            <label for="permitDate" class="form-label">Tanggal</label>
            <input type="date" class="form-control" id="permitDate" name="permitDate" required>
        </div>
        <div class="mb-3">
            <label for="permitType" class="form-label">Jenis Izin</label>
            <select class="form-select" id="permitType" name="permitType" required>
                <option value="" class="placeholder" hidden>Pilih Jenis Izin</option>
                <option value="sakit">Sakit</option>
                <option value="keperluan_pribadi">Keperluan Pribadi</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="permitDescription" class="form-label">Keterangan</label>
            <textarea class="form-control" id="permitDescription" name="permitDescription" rows="3" required></textarea>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary">Submit Pengajuan</button>
        </div>
    </form>
</div>
        </div>
    </div>
</div>

<!-- Leave Request Modal -->
<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveRequestModalLabel">Pengajuan Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
    <form id="leaveRequestForm" method="POST" action="permit.php">
        <input type="hidden" name="form_type" value="cuti">
        <div class="mb-3">
            <label for="leaveStartDate" class="form-label">Tanggal Mulai</label>
            <input type="date" class="form-control" id="leaveStartDate" name="leaveStartDate" required>
        </div>
        <div class="mb-3">
            <label for="leaveEndDate" class="form-label">Tanggal Selesai</label>
            <input type="date" class="form-control" id="leaveEndDate" name="leaveEndDate" required>
        </div>
        <div class="mb-3">
            <label for="leaveDescription" class="form-label">Keterangan</label>
            <textarea class="form-control" id="leaveDescription" name="leaveDescription" rows="3" required></textarea>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary">Submit Pengajuan</button>
        </div>
    </form>
</div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/scripts.js"></script>
    
    <script>
function toggleTableswitch() {
    const isChecked = document.getElementById('tableSwitch').checked;
    const tableLabel = document.getElementById('tableLabel');
    
    if (isChecked) {
        document.getElementById('izinTable').classList.add('hidden');
        document.getElementById('cutiTable').classList.remove('hidden');
        tableLabel.textContent = "Riwayat Cuti";
    } else {
        document.getElementById('izinTable').classList.remove('hidden');
        document.getElementById('cutiTable').classList.add('hidden');
        tableLabel.textContent = "Riwayat Izin";
    }
}

        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');

        sidebarToggle.addEventListener('click', function () {
            sidebarWrapper.classList.toggle('collapsed');
        });

function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
}
    </script>
    <script>
document.getElementById('permitRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('permit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('permitRequestModal'));
        modal.hide();
        
        showAlert('Pengajuan izin berhasil disubmit!', 'success');
        
        // Refresh the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan saat mengirim pengajuan', 'danger');
    });
});

document.getElementById('leaveRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('permit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('leaveRequestModal'));
        modal.hide();
        
        showAlert('Pengajuan cuti berhasil disubmit!', 'success');
        
        // Refresh the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan saat mengirim pengajuan', 'danger');
    });
});

// Handle URL parameters for alerts on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');
    
    if (status === 'success') {
        showAlert('Pengajuan berhasil disubmit!', 'success');
    } else if (status === 'error') {
        showAlert(`Error: ${message || 'Terjadi kesalahan'}`, 'danger');
    }
});
</script>
</body>
</html>
