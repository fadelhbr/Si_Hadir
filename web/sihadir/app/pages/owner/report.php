<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Check if the user role is employee
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'owner') {
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

require '../../../vendor/autoload.php'; // Pastikan path ini sesuai
require_once '../../../app/auth/auth.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Function to get valid date range with actual attendance data
function getValidAttendanceDateRange($pdo)
{
    $query = $pdo->query("
        SELECT 
            MIN(tanggal) as earliest_date, 
            MAX(tanggal) as latest_date
        FROM absensi 
        WHERE 
            (status_kehadiran IS NOT NULL) AND 
            (status_kehadiran != '') AND
            (status_kehadiran IN ('hadir', 'alpha', 'sakit', 'cuti', 'izin', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang'))
    ");

    $result = $query->fetch(PDO::FETCH_ASSOC);

    return [
        'min_date' => $result['earliest_date'] ? date('Y-m-d', strtotime($result['earliest_date'])) : null,
        'max_date' => $result['latest_date'] ? date('Y-m-d', strtotime($result['latest_date'])) : null
    ];
}

$dateRange = getValidAttendanceDateRange($pdo);

// If no valid dates found, handle accordingly
if ($dateRange['min_date'] === null || $dateRange['max_date'] === null) {
    // No attendance data available
    $minDate = date('Y-m-d');
    $maxDate = date('Y-m-d');
} else {
    $minDate = $dateRange['min_date'];
    $maxDate = $dateRange['max_date'];
}

$earliest_date_query = $pdo->query("
    SELECT MIN(tanggal) as earliest_date 
    FROM absensi
");
$earliest_date_result = $earliest_date_query->fetch(PDO::FETCH_ASSOC);
$minDate = $earliest_date_result['earliest_date'] ? date('Y-m-d', strtotime($earliest_date_result['earliest_date'])) : date('Y-m-d');

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

if (!empty($start_date) && !empty($end_date)) {
    // Pengecekan format tanggal
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        die('Invalid date format. Please use YYYY-MM-DD.');
    }

    // Menambahkan waktu ke tanggal
    $start_date_with_time = $start_date . ' 00:00:00';
    $end_date_with_time = $end_date . ' 23:59:59';

    // Menyiapkan query dengan filter tanggal
    $stmt = $pdo->prepare("
    SELECT 
        u.nama_lengkap AS nama_staff,
        u.jenis_kelamin AS jenis_kelamin,
        SUM(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang') THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN a.status_kehadiran = 'alpha' THEN 1 ELSE 0 END) AS alpha,
        SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN a.status_kehadiran = 'cuti' THEN 1 ELSE 0 END) AS cuti,
        SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) AS izin
    FROM 
        absensi a
    JOIN 
        pegawai p ON a.pegawai_id = p.id
    JOIN 
        users u ON p.user_id = u.id
    WHERE 
        a.tanggal BETWEEN :start_date AND :end_date  -- Menggunakan kolom tanggal untuk filter
    GROUP BY 
        u.id  -- Mengelompokkan hanya berdasarkan id pegawai
    ORDER BY 
        u.nama_lengkap ASC;
");

    // Mengikat parameter tanggal ke query
    $stmt->bindParam(':start_date', $start_date_with_time);
    $stmt->bindParam(':end_date', $end_date_with_time);
} else {
    // Menyiapkan query tanpa filter tanggal
    $stmt = $pdo->prepare("
        SELECT 
            u.nama_lengkap AS nama_staff,
            u.jenis_kelamin AS jenis_kelamin,
            SUM(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang') THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status_kehadiran = 'alpha' THEN 1 ELSE 0 END) AS alpha,
            SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status_kehadiran = 'cuti' THEN 1 ELSE 0 END) AS cuti,
            SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) AS izin
        FROM 
            absensi a
        JOIN 
            pegawai p ON a.pegawai_id = p.id
        JOIN 
            users u ON p.user_id = u.id
        GROUP BY 
            u.id
        ORDER BY 
            u.nama_lengkap ASC;
    ");
}

// Eksekusi query setelah disiapkan
$stmt->execute();

// Mengambil hasil query
$attendanceDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);


//PDF
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Get date range from URL parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

    // Prepare the query based on date range
    if (!empty($start_date) && !empty($end_date)) {
        $start_date_with_time = $start_date . ' 00:00:00';
        $end_date_with_time = $end_date . ' 23:59:59';

        $stmt = $pdo->prepare("
            SELECT 
                u.nama_lengkap AS nama_staff,
                u.jenis_kelamin AS jenis_kelamin,
                SUM(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang') THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status_kehadiran = 'alpha' THEN 1 ELSE 0 END) AS alpha,
                SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status_kehadiran = 'cuti' THEN 1 ELSE 0 END) AS cuti,
                SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) AS izin
            FROM 
                absensi a
            JOIN 
                pegawai p ON a.pegawai_id = p.id
            JOIN 
                users u ON p.user_id = u.id
            WHERE 
                a.tanggal BETWEEN :start_date AND :end_date
            GROUP BY 
                u.id, u.nama_lengkap, u.jenis_kelamin
            ORDER BY 
                u.nama_lengkap ASC
        ");

        $stmt->bindParam(':start_date', $start_date_with_time);
        $stmt->bindParam(':end_date', $end_date_with_time);
    } else {
        // Query without date filter remains the same
        $stmt = $pdo->prepare("
            SELECT 
                u.nama_lengkap AS nama_staff,
                u.jenis_kelamin AS jenis_kelamin,
                SUM(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang') THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status_kehadiran = 'alpha' THEN 1 ELSE 0 END) AS alpha,
                SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status_kehadiran = 'cuti' THEN 1 ELSE 0 END) AS cuti,
                SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) AS izin
            FROM 
                absensi a
            JOIN 
                pegawai p ON a.pegawai_id = p.id
            JOIN 
                users u ON p.user_id = u.id
            GROUP BY 
                u.id, u.nama_lengkap, u.jenis_kelamin
            ORDER BY 
                u.nama_lengkap ASC
        ");
    }

    $stmt->execute();
    $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'print') {
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Courier');
        $dompdf = new Dompdf($options);

        // Add date range to the title if available
        $dateRangeTitle = '';
        if (!empty($start_date) && !empty($end_date)) {
            $dateRangeTitle = '<p style="text-align: left;">Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>';
        }

        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Laporan Absensi</title>
            <style>
                body { font-family: Arial, sans-serif; }
                h1 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h1>Laporan Absensi Karyawan</h1>
            ' . $dateRangeTitle . '
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Karyawan</th>
                        <th>Jenis Kelamin</th>
                        <th>Hadir</th>
                        <th>Alpha</th>
                        <th>Sakit</th>
                        <th>Cuti</th>
                        <th>Izin</th>
                    </tr>
                </thead>
                <tbody>';

        if (!empty($exportData)) {
            $no = 1;
            foreach ($exportData as $row) {
                $html .= '<tr>';
                $html .= '<td>' . $no++ . '</td>';
                $html .= '<td>' . htmlspecialchars($row['nama_staff']) . '</td>';
                $html .= '<td>' . htmlspecialchars(ucwords($row['jenis_kelamin'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['hadir']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['alpha']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['sakit']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['cuti']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['izin']) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align: center;">Tidak Ada Data Absensi Karyawan</td></tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('laporan_absensi.pdf', array('Attachment' => true));
        exit;

    } elseif ($action === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add title and date range if available
        $sheet->setCellValue('A1', 'LAPORAN ABSENSI KARYAWAN');
        $sheet->mergeCells('A1:H1');
        
        $currentRow = 2;
        if (!empty($start_date) && !empty($end_date)) {
            $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
            $sheet->mergeCells('A2:H2');
            $currentRow = 3;
        }

        // Add headers
        $currentRow++; // Move to next row for headers
        $headers = ['No.', 'Nama Karyawan', 'Jenis Kelamin', 'Hadir', 'Alpha', 'Sakit', 'Cuti', 'Izin'];
        $sheet->fromArray($headers, NULL, 'A' . $currentRow);

        // Add data
        if (!empty($exportData)) {
            $dataRow = $currentRow + 1;
            $no = 1;
            foreach ($exportData as $data) {
                $sheet->setCellValue('A' . $dataRow, $no++);
                $sheet->setCellValue('B' . $dataRow, $data['nama_staff']);
                $sheet->setCellValue('C' . $dataRow, ucwords($data['jenis_kelamin']));
                $sheet->setCellValue('D' . $dataRow, $data['hadir']);
                $sheet->setCellValue('E' . $dataRow, $data['alpha']);
                $sheet->setCellValue('F' . $dataRow, $data['sakit']);
                $sheet->setCellValue('G' . $dataRow, $data['cuti']);
                $sheet->setCellValue('H' . $dataRow, $data['izin']);
                $dataRow++;
            }
            $lastRow = $dataRow - 1;
        } else {
            $dataRow = $currentRow + 1;
            $sheet->setCellValue('A' . $dataRow, 'Tidak Ada Data Absensi Karyawan');
            $sheet->mergeCells('A' . $dataRow . ':H' . $dataRow);
            $lastRow = $dataRow;
        }

        // Style the Excel file
        // Header style
        $headerRange = 'A' . $currentRow . ':H' . $currentRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB(Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setRGB('4F81BD');

        // Title style
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if (!empty($start_date) && !empty($end_date)) {
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set alignment for all data cells
        $sheet->getStyle('A' . $currentRow . ':H' . $lastRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to all cells
        $sheet->getStyle('A' . $currentRow . ':H' . $lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="laporan_absensi.xlsx"');
        header('Cache-Control: max-age=0');

        // Save to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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
    <title>Si Hadir - Laporan</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../../assets/css/styles.css" rel="stylesheet" />
    <!-- Link Google Fonts untuk Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
</head>

<body class="bg-blue-50">

    <style>
        /* Mengatur font Poppins hanya untuk <strong> di dalam sidebar-heading */
        #sidebar-wrapper .sidebar-heading strong {
            font-family: 'Poppins', sans-serif;
            /* Menggunakan font Poppins hanya untuk Si Hadir */
            font-weight: 900;
            /* Menebalkan tulisan */
            font-size: 28px;
            /* Membesarkan ukuran font */
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

        body {
            background-color: #f3f4f6;
            padding: 0;
            margin: 0;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        .stats-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .chart-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
                        Monitor Absensi
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
                <!-- Page content -->
                <div class="flex-1 bg-blue-50 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-semibold">Rekap Absensi Karyawan</h1>
                        <!-- HTML -->
<div class="flex gap-4">
    <a id="pdfDownloadLink" href="#" class="pointer-events-none">
        <button id="pdfDownloadBtn" class="bg-red-500 text-white px-4 py-2 rounded-lg 
            hover:bg-red-600 
            disabled:opacity-50 disabled:cursor-not-allowed 
            disabled:hover:bg-red-500 
            transition-all duration-300">
            Download PDF
        </button>
    </a>
    <a id="excelDownloadLink" href="#" class="pointer-events-none">
        <button id="excelDownloadBtn" class="bg-green-500 text-white px-4 py-2 rounded-lg 
            hover:bg-green-600 
            disabled:opacity-50 disabled:cursor-not-allowed 
            disabled:hover:bg-green-500 
            transition-all duration-300">
            Download Excel
        </button>
    </a>
</div>
                    </div>
                    <!-- Filter Section -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal
                                        Mulai:</label>
                                    <input type="date" name="start_date" id="start_date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        min="<?php echo $minDate; ?>"
                                        max="<?php echo isset($end_date) ? $end_date : ''; ?>"
                                        value="<?php echo isset($start_date) ? $start_date : ''; ?>">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal
                                        Akhir:</label>
                                    <input type="date" name="end_date" id="end_date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        min="<?php echo isset($start_date) ? $start_date : $minDate; ?>"
                                        max="<?php echo date('Y-m-d'); ?>"
                                        value="<?php echo isset($end_date) ? $end_date : ''; ?>">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit"
                                        class="w-full bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                                        Filter Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Table Section -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Detail Absensi Karyawan</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="reportTable" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama Karyawan</th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Jenis Kelamin</th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Hadir</th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Alpha</th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sakit</th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cuti</th>

                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Izin</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody" class="bg-white divide-y divide-gray-200">
                                    <?php
                                    if (!empty($attendanceDetails)) {
                                        foreach ($attendanceDetails as $detail) {
                                            echo "<tr>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['nama_staff']) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars(ucwords($detail['jenis_kelamin'])) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['hadir']) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['alpha']) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['sakit']) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['cuti']) . "</td>";
                                            echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($detail['izin']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='px-6 py-4 text-center'>Tidak ada data absensi karyawan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Update batasan tanggal secara dinamis dengan JavaScript
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');

                // Saat tanggal akhir berubah, perbarui max dari tanggal mulai
                endDateInput.addEventListener('change', () => {
                    startDateInput.max = endDateInput.value;
                });

                // Saat tanggal mulai berubah, perbarui min dari tanggal akhir
                startDateInput.addEventListener('change', () => {
                    endDateInput.min = startDateInput.value || "<?php echo $minDate; ?>";
                });
            </script>

            <script>
                // Export functions
                function exportData(type) {
                    const timestamp = new Date().toISOString().split('T')[0];
                    const filename = rekap_absensi_${ timestamp }.${ type };

                    // Simulasi download
                    alert(Downloading ${ filename }...\nNote: This is just a UI demonstration.);
                }

                // Add table row hover effect
                document.querySelectorAll('tbody tr').forEach(row => {
                    row.addEventListener('mouseover', () => {
                        row.classList.add('bg-gray-50');
                    });
                    row.addEventListener('mouseout', () => {
                        row.classList.remove('bg-gray-50');
                    });
                });
            </script>

            <!-- Bootstrap core JS-->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
            <!-- Core theme JS-->
            <script src="../../../assets/js/scripts.js "></script>

            <!-- Custom JS to handle sidebar toggle -->
            <script>
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebarWrapper = document.getElementById('sidebar-wrapper');

                sidebarToggle.addEventListener('click', function () {
                    sidebarWrapper.classList.toggle('collapsed');
                });
            </script>

            <script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const filterForm = document.querySelector('form');
    const pdfDownloadLink = document.getElementById('pdfDownloadLink');
    const excelDownloadLink = document.getElementById('excelDownloadLink');
    const pdfDownloadBtn = document.getElementById('pdfDownloadBtn');
    const excelDownloadBtn = document.getElementById('excelDownloadBtn');

    // Utility function to disable download buttons
    function disableDownloadButtons() {
        pdfDownloadLink.href = '#';
        excelDownloadLink.href = '#';
        
        pdfDownloadLink.classList.add('pointer-events-none');
        excelDownloadLink.classList.add('pointer-events-none');
        
        pdfDownloadBtn.disabled = true;
        excelDownloadBtn.disabled = true;
    }

    // Utility function to enable download buttons
    function enableDownloadButtons(startDate, endDate) {
        pdfDownloadLink.href = `?action=print&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        excelDownloadLink.href = `?action=excel&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        
        pdfDownloadLink.classList.remove('pointer-events-none');
        excelDownloadLink.classList.remove('pointer-events-none');
        
        pdfDownloadBtn.disabled = false;
        excelDownloadBtn.disabled = false;
    }

    // Utility function to validate date inputs
    function validateDates() {
        // Check if both dates are filled
        if (!startDateInput.value || !endDateInput.value) {
            disableDownloadButtons();
            return false;
        }

        // Ensure start date is before or equal to end date
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (startDate > endDate) {
            alert('Start date must be before or equal to end date.');
            disableDownloadButtons();
            return false;
        }

        return true;
    }

    // Event listener for start date input
    startDateInput.addEventListener('change', function() {
        // Reset end date
        endDateInput.value = ''; 
        
        // Update min attribute of end date input
        endDateInput.min = this.value;
        
        // Disable download buttons
        disableDownloadButtons();
    });

    // Event listener for end date input
    endDateInput.addEventListener('change', function() {
        disableDownloadButtons();
        validateDates();
    });

    // Handle form submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent automatic form submission

        if (validateDates()) {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            // Enable download buttons with correct URLs
            enableDownloadButtons(startDate, endDate);

            // Submit the form to filter data
            this.submit();
        }
    });

    // After PHP form processing, check if data is filtered
    const isDataFiltered = <?php echo isset($start_date) && isset($end_date) ? 'true' : 'false'; ?>;
    
    if (isDataFiltered) {
        enableDownloadButtons('<?php echo isset($start_date) ? $start_date : ''; ?>', 
                               '<?php echo isset($end_date) ? $end_date : ''; ?>');
    } else {
        // Initial setup to disable download buttons
        disableDownloadButtons();
    }
});
            </script>

    </body>

</html>