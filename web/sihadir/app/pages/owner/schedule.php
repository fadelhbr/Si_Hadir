<?php
session_start();
require_once '../../../app/auth/auth.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Check if the user role is employee
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'owner') {
    session_unset();
    session_destroy();

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    header('Location: ../../../login.php');
    exit;
}

function shiftNameExists($pdo, $nama_shift, $excludeId = null)
{
    $sql = "SELECT COUNT(*) FROM shift WHERE LOWER(nama_shift) = LOWER(?)";
    $params = [$nama_shift];

    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function shiftInUse($pdo, $shiftId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_shift WHERE shift_id = ?");
    $stmt->execute([$shiftId]);
    return $stmt->fetchColumn() > 0;
}

function validateShiftTimes($jam_masuk, $jam_keluar)
{
    $masuk = strtotime($jam_masuk);
    $keluar = strtotime($jam_keluar);

    if ($keluar <= $masuk) {
        $keluar_next_day = $keluar + (24 * 60 * 60);
        if ($keluar_next_day <= $masuk) {
            return ['valid' => false, 'message' => 'Periksa kembali jam masuk dan jam pulang anda, jam pulang tidak boleh kurang dari atau sama dengan jam masuk.'];
        }
    }

    return ['valid' => true];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    if (shiftNameExists($pdo, $_POST['nama_shift'])) {
                        $_SESSION['error'] = "Nama jadwal sudah ada (tidak memperhatikan huruf besar/kecil). Gunakan nama yang berbeda.";
                        break;
                    }

                    $timeValidation = validateShiftTimes($_POST['jam_masuk'], $_POST['jam_keluar']);
                    if (!$timeValidation['valid']) {
                        $_SESSION['error'] = $timeValidation['message'];
                        break;
                    }

                    $stmt = $pdo->prepare("INSERT INTO shift (nama_shift, jam_masuk, jam_keluar) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['nama_shift'], $_POST['jam_masuk'], $_POST['jam_keluar']]);
                    $_SESSION['success'] = "Jadwal berhasil ditambahkan.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error menambahkan jadwal: " . $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    if (shiftNameExists($pdo, $_POST['nama_shift'], $_POST['id'])) {
                        $_SESSION['error'] = "Nama jadwal sudah ada (tidak memperhatikan huruf besar/kecil). Gunakan nama yang berbeda.";
                        break;
                    }

                    $timeValidation = validateShiftTimes($_POST['jam_masuk'], $_POST['jam_keluar']);
                    if (!$timeValidation['valid']) {
                        $_SESSION['error'] = $timeValidation['message'];
                        break;
                    }

                    $stmt = $pdo->prepare("UPDATE shift SET nama_shift = ?, jam_masuk = ?, jam_keluar = ? WHERE id = ?");
                    $stmt->execute([$_POST['nama_shift'], $_POST['jam_masuk'], $_POST['jam_keluar'], $_POST['id']]);
                    $_SESSION['success'] = "Jadwal berhasil diperbarui.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error memperbarui jadwal: " . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    if (shiftInUse($pdo, $_POST['id'])) {
                        $_SESSION['error'] = "Jadwal tidak dapat dihapus karena sedang digunakan dalam penjadwalan staff.";
                        break;
                    }

                    $stmt = $pdo->prepare("DELETE FROM shift WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = "Jadwal berhasil dihapus.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error menghapus jadwal: " . $e->getMessage();
                }
                break;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM shift ORDER BY jam_masuk");
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error mengambil data jadwal: " . $e->getMessage();
    $shifts = [];
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Si Hadir - Jadwal Shift</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../../assets/css/styles.css" rel="stylesheet" />
    <!-- Link Google Fonts untuk Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }

        .alert-danger {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }

        .fade-out {
            opacity: 0;
            transition: opacity 2s;
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

        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body class="bg-blue-50">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar-->
        <div class="border-end-0 bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                    </svg>
                    Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="attendanceMonitor.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z" />
                    </svg>
                    Monitor Presensi
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="schedule.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d">
                        <path
                            d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z" />
                    </svg>
                    Jadwal Shift
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="manageMember.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d"
                        width="24" height="24">
                        <path
                            d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 2.02 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z" />
                    </svg>
                    Manajemen Staff
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="permit.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z" />
                    </svg>
                    Cuti & Perizinan
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="report.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" width="24"
                        height="24" stroke="#6c757d" fill="none" stroke-width="2">
                        <path
                            d="M6 2C5.44772 2 5 2.44772 5 3V21C5 21.5523 5.44772 22 6 22H18C18.5523 22 19 21.5523 19 21V7L14 2H6Z" />
                        <path d="M13 2V7H19" />
                    </svg>
                    Laporan
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="logout.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z" />
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
                <h1 class="text-3xl font-semibold mb-4">Jadwal Shift</h1>

                <!-- Add Shift Modal -->
                <div id="addShiftModal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Tambah Jadwal Baru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" id="addShiftForm" onsubmit="return validateShiftForm(this);">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Jadwal</label>
                                        <input type="text" class="form-control" name="nama_shift" required>
                                        <div class="invalid-feedback">
                                            Nama jadwal harus diisi dan unik
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jam Masuk</label>
                                        <input type="time" class="form-control" name="jam_masuk" required>
                                        <div class="invalid-feedback">

                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jam Pulang</label>
                                        <input type="time" class="form-control" name="jam_keluar" required>
                                        <div class="invalid-feedback">
                                            Periksa kembali jam masuk dan jam pulang anda, jam pulang tidak boleh kurang
                                            dari atau sama dengan jam masuk.
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">

                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Shift Modal -->
                <div id="editShiftModal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Jadwal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" id="editShiftForm" onsubmit="return validateShiftForm(this);">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" id="edit_id">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Jadwal</label>
                                        <input type="text" class="form-control" name="nama_shift" id="edit_nama_shift"
                                            required>
                                        <div class="invalid-feedback">
                                            Nama jadwal harus diisi dan unik
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jam Masuk</label>
                                        <input type="time" class="form-control" name="jam_masuk" id="edit_jam_masuk"
                                            required>
                                        <div class="invalid-feedback">

                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jam Pulang</label>
                                        <input type="time" class="form-control" name="jam_keluar" id="edit_jam_keluar"
                                            required>
                                        <div class="invalid-feedback">
                                            Periksa kembali jam masuk dan jam pulang anda, jam pulang tidak boleh kurang
                                            dari atau sama dengan jam masuk.
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <div class="flex space-x-2">
                        <button class="bg-blue-500 text-white px-4 py-2 rounded" data-bs-toggle="modal"
                            data-bs-target="#addShiftModal">
                            Tambah Jadwal
                        </button>
                    </div>
                    <input type="text" id="searchInput" class="border border-gray-300 rounded px-2 py-1"
                        placeholder="Cari Jadwal">
                </div>

                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="bg-white shadow rounded-lg p-4 mb-4">
                    <table class="min-w-full divide-y divide-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                                    Jadwal</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Jam
                                    Masuk</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Jam
                                    Pulang</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($shifts as $shift): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($shift['nama_shift']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('H:i', strtotime($shift['jam_masuk'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('H:i', strtotime($shift['jam_keluar'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editShiftModal"
                                            data-id="<?php echo $shift['id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($shift['nama_shift']); ?>"
                                            data-masuk="<?php echo $shift['jam_masuk']; ?>"
                                            data-keluar="<?php echo $shift['jam_keluar']; ?>">
                                            Edit
                                        </button>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $shift['id']; ?>">
                                            <button type="submit"
                                                class="px-4 py-2 bg-red-100 text-red-700 rounded-full text-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Core theme JS-->
    <script src="../../../assets/js/scripts.js "></script>

    <!-- Custom JS to handle sidebar toggle -->
    <script>
        function validateShiftForm(form) {
            const jamMasuk = form.querySelector('[name="jam_masuk"]');
            const jamKeluar = form.querySelector('[name="jam_keluar"]');

            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            let isValid = true;

            form.querySelectorAll('[required]').forEach(input => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });

            if (jamMasuk.value && jamKeluar.value) {
                const masuk = new Date(`2000-01-01T${jamMasuk.value}`);
                const keluar = new Date(`2000-01-01T${jamKeluar.value}`);

                if (masuk >= keluar) {
                    jamMasuk.classList.add('is-invalid');
                    isValid = false;
                }

                if (keluar <= masuk) {
                    jamKeluar.classList.add('is-invalid');
                    jamKeluar.nextElementSibling.textContent = 'Periksa kembali jam masuk dan jam pulang anda, jam pulang tidak boleh kurang dari atau sama dengan jam masuk.';
                    isValid = false;
                }
            }

            return isValid;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const addModal = document.getElementById('addShiftModal');
            addModal.addEventListener('hidden.bs.modal', function () {
                const form = this.querySelector('form');
                form.reset();
                form.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
            });

            const editModal = document.getElementById('editShiftModal');
            editModal.addEventListener('hidden.bs.modal', function () {
                const form = this.querySelector('form');
                form.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
            });

            setTimeout(function () {
                document.querySelectorAll('.alert').forEach(function (alert) {
                    alert.classList.add('fade-out');
                    setTimeout(() => alert.remove(), 3000);
                });
            }, 5000);
        });

        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_nama_shift').value = this.dataset.nama;
                document.getElementById('edit_jam_masuk').value = this.dataset.masuk;
                document.getElementById('edit_jam_keluar').value = this.dataset.keluar;
            });
        });

        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('[name="action"][value="delete"]')) {
                form.onsubmit = function (e) {
                    e.preventDefault();
                    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini? Pastikan tidak ada karyawan yang menggunakan jadwal ini.')) {
                        form.submit();
                    }
                };
            }
        });

        document.getElementById('searchInput').addEventListener('keyup', function () {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const nama = row.cells[0].textContent.toLowerCase();
                row.style.display = nama.includes(searchValue) ? '' : 'none';
            });
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');

        sidebarToggle.addEventListener('click', function () {
            sidebarWrapper.classList.toggle('collapsed');
        });
    </script>
</body>

</html>