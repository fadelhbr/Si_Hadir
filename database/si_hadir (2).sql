-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2024 at 03:04 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `si_hadir`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_in_with_code` (IN `kode_unik_param` CHAR(6), IN `check_time` DATETIME, IN `lokasi` VARCHAR(100))   BEGIN
    DECLARE karyawan_id_var INT;
    DECLARE jadwal_id_var INT;
    DECLARE shift_start TIME;
    DECLARE attendance_status VARCHAR(15);
    
    -- Dapatkan karyawan_id dari kode unik
    SELECT id INTO karyawan_id_var 
    FROM karyawan 
    WHERE kode_unik = kode_unik_param;
    
    -- Dapatkan jadwal shift aktif untuk hari ini
    SELECT js.id, s.jam_masuk 
    INTO jadwal_id_var, shift_start
    FROM jadwal_shift js
    JOIN shift s ON js.shift_id = s.id
    WHERE js.karyawan_id = karyawan_id_var 
    AND js.tanggal = DATE(check_time)
    AND js.status = 'aktif';
    
    -- Jika ditemukan jadwal yang sesuai
    IF jadwal_id_var IS NOT NULL THEN
        -- Tentukan status kehadiran
        SET attendance_status = get_attendance_status(check_time, shift_start);
        
        -- Update absensi
        UPDATE absensi 
        SET waktu_masuk = check_time,
            status_kehadiran = attendance_status,
            lokasi_masuk = lokasi
        WHERE karyawan_id = karyawan_id_var 
        AND jadwal_shift_id = jadwal_id_var
        AND DATE(created_at) = DATE(check_time);
        
        -- Jika belum ada record absensi, buat baru
        IF ROW_COUNT() = 0 THEN
            INSERT INTO absensi (
                karyawan_id,
                jadwal_shift_id,
                waktu_masuk,
                status_kehadiran,
                lokasi_masuk,
                created_at
            ) VALUES (
                karyawan_id_var,
                jadwal_id_var,
                check_time,
                attendance_status,
                lokasi,
                check_time
            );
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `check_in_with_qr` (IN `qr_code_param` VARCHAR(255), IN `check_time` DATETIME, IN `lokasi` VARCHAR(100))   BEGIN
    DECLARE karyawan_id_var INT;
    DECLARE jadwal_id_var INT;
    DECLARE shift_start TIME;
    DECLARE attendance_status VARCHAR(15);
    
    -- Dapatkan karyawan_id dari QR code
    SELECT id INTO karyawan_id_var 
    FROM karyawan 
    WHERE qr_code = qr_code_param;
    
    -- Dapatkan jadwal shift aktif untuk hari ini
    SELECT js.id, s.jam_masuk 
    INTO jadwal_id_var, shift_start
    FROM jadwal_shift js
    JOIN shift s ON js.shift_id = s.id
    WHERE js.karyawan_id = karyawan_id_var 
    AND js.tanggal = DATE(check_time)
    AND js.status = 'aktif';
    
    -- Jika ditemukan jadwal yang sesuai
    IF jadwal_id_var IS NOT NULL THEN
        -- Tentukan status kehadiran
        SET attendance_status = get_attendance_status(check_time, shift_start);
        
        -- Update absensi
        UPDATE absensi 
        SET waktu_masuk = check_time,
            status_kehadiran = attendance_status,
            lokasi_masuk = lokasi
        WHERE karyawan_id = karyawan_id_var 
        AND jadwal_shift_id = jadwal_id_var
        AND DATE(created_at) = DATE(check_time);
        
        -- Jika belum ada record absensi, buat baru
        IF ROW_COUNT() = 0 THEN
            INSERT INTO absensi (
                karyawan_id,
                jadwal_shift_id,
                waktu_masuk,
                status_kehadiran,
                lokasi_masuk,
                created_at
            ) VALUES (
                karyawan_id_var,
                jadwal_id_var,
                check_time,
                attendance_status,
                lokasi,
                check_time
            );
        END IF;
    END IF;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_attendance_status` (`check_time` DATETIME, `shift_start` TIME) RETURNS VARCHAR(15) CHARSET utf8mb4 COLLATE utf8mb4_bin DETERMINISTIC BEGIN
    DECLARE status_result VARCHAR(15);
    DECLARE time_diff INT;
    
    -- Hitung selisih menit
    SET time_diff = TIMESTAMPDIFF(MINUTE, 
        CONCAT(DATE(check_time), ' ', shift_start),
        check_time
    );
    
    -- Jika telat lebih dari 5 menit, status terlambat
    IF time_diff > 5 THEN
        RETURN 'terlambat';
    ELSE
        RETURN 'hadir';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `karyawan_id` int NOT NULL,
  `jadwal_shift_id` int NOT NULL,
  `waktu_masuk` datetime DEFAULT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `status_kehadiran` enum('hadir','terlambat','sakit','izin','alpha') COLLATE utf8mb4_bin NOT NULL DEFAULT 'alpha',
  `keterangan` text COLLATE utf8mb4_bin,
  `lokasi_masuk` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `lokasi_keluar` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `karyawan_id`, `jadwal_shift_id`, `waktu_masuk`, `waktu_keluar`, `status_kehadiran`, `keterangan`, `lokasi_masuk`, `lokasi_keluar`, `created_at`) VALUES
(1, 1, 1, '2024-01-22 07:55:00', '2024-01-22 16:05:00', 'hadir', 'Masuk tepat waktu', 'Kantor Pusat', 'Kantor Pusat', '2024-10-23 02:54:41'),
(2, 2, 2, '2024-01-22 13:10:00', '2024-01-22 21:00:00', 'terlambat', 'Terlambat karena macet', 'Kantor Pusat', 'Kantor Pusat', '2024-10-23 02:54:41'),
(3, 3, 3, '2024-01-22 21:00:00', '2024-01-23 05:00:00', 'hadir', 'Shift malam normal', 'Kantor Pusat', 'Kantor Pusat', '2024-10-23 02:54:41'),
(4, 1, 4, '2024-01-23 08:30:00', NULL, 'terlambat', 'Terlambat karena hujan', 'Kantor Pusat', NULL, '2024-10-23 02:54:41'),
(5, 1, 1, '2024-01-22 07:55:00', NULL, 'hadir', NULL, 'Kantor Pusat', NULL, '2024-01-22 00:55:00'),
(6, 1, 1, '2024-01-22 07:55:00', NULL, 'hadir', NULL, 'Kantor Pusat', NULL, '2024-01-22 00:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id` int NOT NULL,
  `nama_divisi` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `divisi`
--

INSERT INTO `divisi` (`id`, `nama_divisi`, `created_at`) VALUES
(1, 'IT', '2024-10-23 02:54:41'),
(2, 'HR', '2024-10-23 02:54:41'),
(3, 'Finance', '2024-10-23 02:54:41'),
(4, 'Marketing', '2024-10-23 02:54:41'),
(5, 'Operations', '2024-10-23 02:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_shift`
--

CREATE TABLE `jadwal_shift` (
  `id` int NOT NULL,
  `karyawan_id` int NOT NULL,
  `shift_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_bin DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `jadwal_shift`
--

INSERT INTO `jadwal_shift` (`id`, `karyawan_id`, `shift_id`, `tanggal`, `status`) VALUES
(1, 1, 1, '2024-01-22', 'aktif'),
(2, 2, 2, '2024-01-22', 'aktif'),
(3, 3, 3, '2024-01-22', 'aktif'),
(4, 1, 1, '2024-01-23', 'aktif'),
(5, 2, 2, '2024-01-23', 'aktif');

--
-- Triggers `jadwal_shift`
--
DELIMITER $$
CREATE TRIGGER `create_default_attendance` AFTER INSERT ON `jadwal_shift` FOR EACH ROW BEGIN
    INSERT INTO absensi (
        karyawan_id,
        jadwal_shift_id,
        status_kehadiran,
        created_at
    ) VALUES (
        NEW.karyawan_id,
        NEW.id,
        'alpha',  -- Default status
        CONCAT(NEW.tanggal, ' 00:00:00')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `divisi_id` int NOT NULL,
  `nip` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `kode_unik` char(6) COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `user_id`, `divisi_id`, `nip`, `qr_code`, `kode_unik`) VALUES
(1, 3, 1, '2024001', 'QR001', 'ABC123'),
(2, 4, 2, '2024002', 'QR002', 'DEF456'),
(3, 5, 3, '2024003', 'QR003', 'GHI789');

--
-- Triggers `karyawan`
--
DELIMITER $$
CREATE TRIGGER `before_insert_karyawan` BEFORE INSERT ON `karyawan` FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(10);
    SELECT role INTO user_role FROM users WHERE id = NEW.user_id;
    IF user_role != 'karyawan' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Hanya user dengan role karyawan yang bisa ditambahkan ke tabel karyawan';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `log_akses`
--

CREATE TABLE `log_akses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `waktu_login` datetime NOT NULL,
  `waktu_logout` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_bin NOT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('login','logout') COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `log_akses`
--

INSERT INTO `log_akses` (`id`, `user_id`, `waktu_login`, `waktu_logout`, `ip_address`, `device_info`, `status`) VALUES
(1, 1, '2024-01-22 08:00:00', '2024-01-22 17:00:00', '192.168.1.100', 'Chrome Windows 10', 'logout'),
(2, 2, '2024-01-22 09:00:00', NULL, '192.168.1.101', 'Firefox MacOS', 'login'),
(3, 3, '2024-01-22 07:45:00', '2024-01-22 16:15:00', '192.168.1.102', 'Safari iOS', 'logout'),
(4, 4, '2024-01-22 13:00:00', '2024-01-22 21:30:00', '192.168.1.103', 'Chrome Android', 'logout'),
(5, 5, '2024-01-22 20:45:00', '2024-01-23 05:15:00', '192.168.1.104', 'Chrome Windows 11', 'logout');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `isi` text COLLATE utf8mb4_bin NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_bin DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `admin_id`, `judul`, `isi`, `tanggal_mulai`, `tanggal_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Libur Nasional', 'Diberitahukan bahwa tanggal 25 Januari 2024 adalah libur nasional', '2024-01-22', '2024-01-25', 'aktif', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(2, 2, 'Maintenance Sistem', 'Akan dilakukan maintenance sistem pada tanggal 27 Januari 2024', '2024-01-23', '2024-01-27', 'aktif', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(3, 1, 'Rapat Bulanan', 'Rapat bulanan akan diadakan pada tanggal 30 Januari 2024', '2024-01-23', '2024-01-30', 'aktif', '2024-10-23 02:54:41', '2024-10-23 02:54:41');

--
-- Triggers `pengumuman`
--
DELIMITER $$
CREATE TRIGGER `before_insert_pengumuman` BEFORE INSERT ON `pengumuman` FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(10);
    SELECT role INTO user_role FROM users WHERE id = NEW.admin_id;
    IF user_role != 'admin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hanya admin yang bisa membuat pengumuman';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `shift`
--

CREATE TABLE `shift` (
  `id` int NOT NULL,
  `nama_shift` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `shift`
--

INSERT INTO `shift` (`id`, `nama_shift`, `jam_masuk`, `jam_keluar`) VALUES
(1, 'Pagi', '08:00:00', '16:00:00'),
(2, 'Siang', '13:00:00', '21:00:00'),
(3, 'Malam', '21:00:00', '05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `role` enum('admin','karyawan') COLLATE utf8mb4_bin NOT NULL,
  `no_telp` varchar(15) COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `no_telp`, `created_at`, `updated_at`) VALUES
(1, 'admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Satu', 'admin1@example.com', 'admin', '081234567890', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(2, 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Dua', 'admin2@example.com', 'admin', '081234567891', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(3, 'john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john@example.com', 'karyawan', '081234567892', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(4, 'jane_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Doe', 'jane@example.com', 'karyawan', '081234567893', '2024-10-23 02:54:41', '2024-10-23 02:54:41'),
(5, 'bob_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Smith', 'bob@example.com', 'karyawan', '081234567894', '2024-10-23 02:54:41', '2024-10-23 02:54:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`karyawan_id`),
  ADD KEY `jadwal_shift_id` (`jadwal_shift_id`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`karyawan_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD UNIQUE KEY `kode_unik` (`kode_unik`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `divisi_id` (`divisi_id`);

--
-- Indexes for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `shift`
--
ALTER TABLE `shift`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `log_akses`
--
ALTER TABLE `log_akses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shift`
--
ALTER TABLE `shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`),
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`jadwal_shift_id`) REFERENCES `jadwal_shift` (`id`);

--
-- Constraints for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  ADD CONSTRAINT `jadwal_shift_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`),
  ADD CONSTRAINT `jadwal_shift_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`);

--
-- Constraints for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD CONSTRAINT `karyawan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `karyawan_ibfk_2` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`);

--
-- Constraints for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD CONSTRAINT `log_akses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
