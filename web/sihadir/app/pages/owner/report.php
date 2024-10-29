<?php

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


$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

if (!empty($start_date) && !empty($end_date)) {
        $stmt = $pdo->prepare("
        SELECT 
            u.nama_lengkap AS nama_staff,
            SUM(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status_kehadiran = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) AS izin
        FROM 
            absensi a
        JOIN 
            pegawai p ON a.pegawai_id = p.id
        JOIN 
            users u ON p.user_id = u.id
        WHERE 
            a.waktu_masuk BETWEEN :start_date AND :end_date
        GROUP BY 
            u.id
        ORDER BY 
            u.nama_lengkap ASC;
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $attendanceDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Query default jika belum ada filter
    $stmt = $pdo->prepare("
        SELECT 
            u.nama_lengkap AS nama_staff,
            SUM(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status_kehadiran = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) AS sakit,
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
    $stmt->execute();
    $attendanceDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//PDF
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'print') {
        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Courier');
        $dompdf = new Dompdf($options);

        $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : 'Tanggal Mulai';
        $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : 'Tanggal Akhir';

        // Konten HTML yang ingin Anda masukkan ke dalam PDF
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Laporan</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                }
                h1 {
                    text-align: center;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
            </style>
        </head>
        <body>
            <h1>Laporan Bulanan</h1>
            <table>
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Hadir</th>
                        <th>Terlambat</th>
                        <th>Sakit</th>
                        <th>Izin</th>
                    </tr>
                </thead>
                <tbody>';

        // Assuming $attendanceDetails is an array containing your data
        if (!empty($attendanceDetails)) {
            foreach ($attendanceDetails as $detail) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($detail['nama_staff']) . '</td>';
                $html .= '<td>' . htmlspecialchars($detail['hadir']) . '</td>';
                $html .= '<td>' . htmlspecialchars($detail['terlambat']) . '</td>';
                $html .= '<td>' . htmlspecialchars($detail['sakit']) . '</td>';
                $html .= '<td>' . htmlspecialchars($detail['izin']) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align: center;">Tidak ada data absensi karyawan</td></tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        // Memuat HTML ke Dompdf
        $dompdf->loadHtml($html);

        // (opsional) Atur ukuran kertas dan orientasi
        $dompdf->setPaper('A4', 'portrait');

        // Render PDF
        $dompdf->render();

        // Output PDF ke browser
        $dompdf->stream('laporan.pdf', array('Attachment' => true));
        exit; // Hentikan eksekusi script setelah mengunduh PDF
        //EXCEL
    } elseif ($action === 'excel') {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Menambahkan header
        $headers = ['Karyawan', 'Hadir', 'Terlambat', 'Sakit', 'Izin'];
        $sheet->fromArray($headers, NULL, 'A1');

        // Menambahkan data
        if (!empty($attendanceDetails)) {
            $row = 2; // Mulai dari baris kedua
            foreach ($attendanceDetails as $detail) {
                $sheet->fromArray([
                    htmlspecialchars($detail['nama_staff']),
                    htmlspecialchars($detail['hadir']),
                    htmlspecialchars($detail['terlambat']),
                    htmlspecialchars($detail['sakit']),
                    htmlspecialchars($detail['izin']),
                ], NULL, 'A' . $row++);
            }
        } else {
            // Jika tidak ada data, masukkan pesan ke dalam file
            $sheet->setCellValue('A2', 'Tidak ada data absensi karyawan');
        }

        // Mengatur format header
        $headerRange = 'A1:E1'; // Ubah ke E1 untuk lima kolom
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB(Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setRGB('4F81BD');

        // Mengatur lebar kolom
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Mengatur perataan teks
        $sheet->getStyle('A1:E' . ($row - 1))
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Menambahkan border
        $sheet->getStyle('A1:E' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(Color::COLOR_BLACK);

        // Mengatur header untuk unduhan Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="laporan_bulanan.xlsx"');
        header('Cache-Control: max-age=0');

        // Tulis file ke output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit; // Hentikan eksekusi script setelah mengunduh Excel
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
        <title>Si Hadir - Dashboard</title>
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
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
        </head>
        <body class="bg-blue-50">
        
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .chart-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
                            Jadwal Shift
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
                <div class="">
        <div class="page-container">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-3xl font-semibold mb-4">Rekap Absensi Karyawan</h1>
                <div class="flex gap-4">
                <a href="?action=print"><button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Download PDF</button>
                    </a>
                <a href="?action=excel"><button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                        Download Excel
                    </button></a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-container">
        <!-- Filter Section -->
        <div class="filter-section">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai:</label>
                        <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo isset($start_date) ? $start_date : ''; ?>">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir:</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo isset($end_date) ? $end_date : ''; ?>">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Karyawan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hadir</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terlambat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sakit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Izin</th>
                </tr>
            </thead>
            <tbody id="reportTableBody" class="bg-white divide-y divide-gray-200">
                <?php
                if (!empty($attendanceDetails)) {
                    foreach ($attendanceDetails as $detail) {
                        echo "<tr>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . htmlspecialchars($detail['nama_staff']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . htmlspecialchars($detail['hadir']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . htmlspecialchars($detail['terlambat']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . htmlspecialchars($detail['sakit']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . htmlspecialchars($detail['izin']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='px-6 py-4 text-center'>Tidak ada data absensi karyawan</td></tr>";
                }
                    ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Showing 1 to 3 of 50 entries
            </div>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border rounded-md hover:bg-gray-50">Previous</button>
                <button class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600">1</button>
                <button class="px-3 py-1 border rounded-md hover:bg-gray-50">2</button>
                <button class="px-3 py-1 border rounded-md hover:bg-gray-50">3</button>
                <button class="px-3 py-1 border rounded-md hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Export functions
    function exportData(type) {
        const timestamp = new Date().toISOString().split('T')[0];
        const filename = rekap_absensi_${timestamp}.${type};
        
        // Simulasi download
        alert(Downloading ${filename}...\nNote: This is just a UI demonstration.);
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
    </body>
</html>