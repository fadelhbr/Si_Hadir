-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 30, 2024 at 10:02 AM
-- Server version: 10.11.8-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

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
CREATE DEFINER=`root`@`localhost` PROCEDURE `auto_insert_absensi` ()   BEGIN
    -- Declare variables for user iteration
    DECLARE done INT DEFAULT FALSE;
    DECLARE curr_pegawai_id INT;
    DECLARE curr_shift_id INT;
    DECLARE curr_jadwal_id INT;
    DECLARE curr_max_late TIME;

    -- Cursor to get all active employees (karyawan)
    DECLARE cur_employees CURSOR FOR 
        SELECT p.id 
        FROM pegawai p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.role = 'karyawan' 
        AND p.status_aktif = 'aktif';

    -- Handler for when cursor is complete
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Open cursor
    OPEN cur_employees;

    -- Start loop for each employee
    read_loop: LOOP
        -- Fetch next employee
        FETCH cur_employees INTO curr_pegawai_id;

        -- Exit if no more employees
        IF done THEN 
            LEAVE read_loop;
        END IF;

        -- First, handle jadwal_shift insertion if it doesn't exist for today
        IF NOT EXISTS (
            SELECT 1 
            FROM jadwal_shift 
            WHERE pegawai_id = curr_pegawai_id 
            AND tanggal = CURDATE()
        ) THEN
            -- Get the last active shift_id and maksimal_keterlambatan for this employee
            SELECT shift_id, maksimal_keterlambatan 
            INTO curr_shift_id, curr_max_late
            FROM jadwal_shift 
            WHERE pegawai_id = curr_pegawai_id 
            AND status = 'aktif' 
            ORDER BY tanggal DESC 
            LIMIT 1;

            -- Insert new jadwal_shift record
            INSERT INTO jadwal_shift (
                pegawai_id,
                shift_id,
                tanggal,
                maksimal_keterlambatan,
                status
            ) VALUES (
                curr_pegawai_id,
                IFNULL(curr_shift_id, 1), -- Default to shift_id 1 if no previous shift
                CURDATE(),
                IFNULL(curr_max_late, '00:00:00'), -- Use previous value or '00:00:00' if none exists
                'aktif'
            );

            -- Get the newly inserted jadwal_shift id
            SET curr_jadwal_id = LAST_INSERT_ID();
        ELSE
            -- Get existing jadwal_shift id for today
            SELECT id, shift_id INTO curr_jadwal_id, curr_shift_id 
            FROM jadwal_shift 
            WHERE pegawai_id = curr_pegawai_id 
            AND tanggal = CURDATE() 
            AND status = 'aktif' 
            LIMIT 1;
        END IF;

        -- Then handle absensi insertion if it doesn't exist for today
        IF NOT EXISTS (
            SELECT 1 
            FROM absensi 
            WHERE pegawai_id = curr_pegawai_id 
            AND DATE(tanggal) = CURDATE()
        ) THEN
            -- Insert new attendance record
            INSERT INTO absensi (
                pegawai_id,
                jadwal_shift_id,
                waktu_masuk,
                waktu_keluar,
                kode_unik,
                status_kehadiran,
                tanggal
            ) VALUES (
                curr_pegawai_id,
                curr_jadwal_id,
                '00:00:00',
                '00:00:00',
                '000000',
                'alpha',
                CURDATE()
            );
        END IF;
    END LOOP;

    -- Close cursor
    CLOSE cur_employees;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `generate_random_code` () RETURNS CHAR(6) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE chars VARCHAR(62) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    DECLARE result CHAR(6) DEFAULT '';
    DECLARE i INT DEFAULT 0;
    
    WHILE i < 6 DO
        SET result = CONCAT(result, SUBSTRING(chars, FLOOR(1 + RAND() * 62), 1));
        SET i = i + 1;
    END WHILE;
    
    RETURN result;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jadwal_shift_id` int(11) NOT NULL,
  `waktu_masuk` time DEFAULT NULL,
  `waktu_keluar` time DEFAULT NULL,
  `kode_unik` char(6) NOT NULL,
  `status_kehadiran` enum('hadir','terlambat','sakit','izin','alpha','cuti','pulang_dahulu','dalam_shift') NOT NULL DEFAULT 'alpha',
  `keterangan` text DEFAULT NULL,
  `tanggal` timestamp NULL DEFAULT current_timestamp(),
  `cuti_id` int(11) DEFAULT NULL,
  `izin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `durasi_cuti` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Triggers `cuti`
--
DELIMITER $$
CREATE TRIGGER `before_cuti_insert` BEFORE INSERT ON `cuti` FOR EACH ROW BEGIN
    SET NEW.id = (
        SELECT COALESCE(MAX(id), 0) + 1
        FROM cuti
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `hitung_durasi_cuti` BEFORE INSERT ON `cuti` FOR EACH ROW BEGIN
    SET NEW.durasi_cuti = DATEDIFF(NEW.tanggal_selesai, NEW.tanggal_mulai);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `cuti_disetujui`
-- (See below for the actual view)
--
CREATE TABLE `cuti_disetujui` (
`nama_staff` varchar(100)
,`tanggal_mulai` date
,`tanggal_selesai` date
,`durasi_cuti` int(11)
,`keterangan` text
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `cuti_ditolak`
-- (See below for the actual view)
--
CREATE TABLE `cuti_ditolak` (
`nama_staff` varchar(100)
,`tanggal_mulai` date
,`tanggal_selesai` date
,`durasi_cuti` int(11)
,`keterangan` text
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `cuti_view`
-- (See below for the actual view)
--
CREATE TABLE `cuti_view` (
`nama_staff` varchar(100)
,`tanggal_mulai` date
,`tanggal_selesai` date
,`durasi_cuti` int(11)
,`keterangan` text
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id` int(11) NOT NULL,
  `nama_divisi` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `divisi`
--

INSERT INTO `divisi` (`id`, `nama_divisi`, `created_at`) VALUES
(1, 'IT', '2024-10-23 02:54:41'),
(2, 'HR', '2024-10-23 02:54:41'),
(3, 'Finance', '2024-10-23 02:54:41'),
(4, 'Marketing', '2024-10-23 02:54:41');

--
-- Triggers `divisi`
--
DELIMITER $$
CREATE TRIGGER `before_divisi_insert` BEFORE INSERT ON `divisi` FOR EACH ROW BEGIN
    SET NEW.id = (
        SELECT COALESCE(MAX(id), 0) + 1
        FROM divisi
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_izin` enum('keperluan_pribadi','dinas_luar') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Triggers `izin`
--
DELIMITER $$
CREATE TRIGGER `before_izin_insert` BEFORE INSERT ON `izin` FOR EACH ROW BEGIN
    SET NEW.id = (
        SELECT COALESCE(MAX(id), 0) + 1
        FROM izin
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `izin_disetujui`
-- (See below for the actual view)
--
CREATE TABLE `izin_disetujui` (
`nama_staff` varchar(100)
,`tanggal` date
,`jenis_izin` enum('keperluan_pribadi','dinas_luar')
,`keterangan` text
,`bukti_pendukung` varchar(255)
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `izin_ditolak`
-- (See below for the actual view)
--
CREATE TABLE `izin_ditolak` (
`nama_staff` varchar(100)
,`tanggal` date
,`jenis_izin` enum('keperluan_pribadi','dinas_luar')
,`keterangan` text
,`bukti_pendukung` varchar(255)
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_shift`
--

CREATE TABLE `jadwal_shift` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `maksimal_keterlambatan` time NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Triggers `jadwal_shift`
--
DELIMITER $$
CREATE TRIGGER `before_jadwal_shift_insert` BEFORE INSERT ON `jadwal_shift` FOR EACH ROW BEGIN
    SET NEW.id = (
        SELECT COALESCE(MAX(id), 0) + 1
        FROM jadwal_shift
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `log_akses`
--

CREATE TABLE `log_akses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waktu` datetime DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `status` enum('logout','login','first_registration') DEFAULT NULL,
  `device_hash` varchar(64) DEFAULT NULL,
  `device_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `divisi_id` int(11) NOT NULL,
  `status_aktif` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Triggers `pegawai`
--
DELIMITER $$
CREATE TRIGGER `before_pegawai_insert` BEFORE INSERT ON `pegawai` FOR EACH ROW BEGIN
    SET NEW.id = (
        SELECT COALESCE(MAX(id), 0) + 1
        FROM pegawai
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `perizinan_view`
-- (See below for the actual view)
--
CREATE TABLE `perizinan_view` (
`Nama_Staff` varchar(100)
,`tanggal` date
,`jenis_izin` enum('keperluan_pribadi','dinas_luar')
,`keterangan` text
,`bukti_pendukung` varchar(255)
,`status` enum('pending','disetujui','ditolak')
);

-- --------------------------------------------------------

--
-- Table structure for table `shift`
--

CREATE TABLE `shift` (
  `id` int(11) NOT NULL,
  `nama_shift` varchar(50) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `shift`
--

INSERT INTO `shift` (`id`, `nama_shift`, `jam_masuk`, `jam_keluar`) VALUES
(1, 'Pagi', '04:00:00', '07:00:00'),
(75576, 'Siang', '12:45:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('owner','karyawan') NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Structure for view `cuti_disetujui`
--
DROP TABLE IF EXISTS `cuti_disetujui`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_disetujui`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on(`cuti`.`pegawai_id` = `pegawai`.`id`)) join `users` on(`pegawai`.`user_id` = `users`.`id`)) WHERE `cuti`.`status` = 'disetujui' ;

-- --------------------------------------------------------

--
-- Structure for view `cuti_ditolak`
--
DROP TABLE IF EXISTS `cuti_ditolak`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_ditolak`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on(`cuti`.`pegawai_id` = `pegawai`.`id`)) join `users` on(`pegawai`.`user_id` = `users`.`id`)) WHERE `cuti`.`status` = 'ditolak' ;

-- --------------------------------------------------------

--
-- Structure for view `cuti_view`
--
DROP TABLE IF EXISTS `cuti_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_view`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on(`pegawai`.`id` = `cuti`.`pegawai_id`)) join `users` on(`users`.`id` = `pegawai`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `izin_disetujui`
--
DROP TABLE IF EXISTS `izin_disetujui`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `izin_disetujui`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`bukti_pendukung` AS `bukti_pendukung`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on(`izin`.`pegawai_id` = `pegawai`.`id`)) join `users` on(`pegawai`.`user_id` = `users`.`id`)) WHERE `izin`.`status` = 'disetujui' ;

-- --------------------------------------------------------

--
-- Structure for view `izin_ditolak`
--
DROP TABLE IF EXISTS `izin_ditolak`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `izin_ditolak`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`bukti_pendukung` AS `bukti_pendukung`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on(`izin`.`pegawai_id` = `pegawai`.`id`)) join `users` on(`pegawai`.`user_id` = `users`.`id`)) WHERE `izin`.`status` = 'ditolak' ;

-- --------------------------------------------------------

--
-- Structure for view `perizinan_view`
--
DROP TABLE IF EXISTS `perizinan_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `perizinan_view`  AS SELECT `users`.`nama_lengkap` AS `Nama_Staff`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`bukti_pendukung` AS `bukti_pendukung`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on(`izin`.`pegawai_id` = `pegawai`.`id`)) join `users` on(`pegawai`.`user_id` = `users`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`pegawai_id`),
  ADD KEY `jadwal_shift_id` (`jadwal_shift_id`),
  ADD KEY `absensi_cuti_fk` (`cuti_id`),
  ADD KEY `absensi_izin_fk` (`izin_id`);

--
-- Indexes for table `cuti`
--
ALTER TABLE `cuti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`pegawai_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `divisi_id` (`divisi_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=546765;

--
-- AUTO_INCREMENT for table `cuti`
--
ALTER TABLE `cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654325;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234237;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12314140;

--
-- AUTO_INCREMENT for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `shift`
--
ALTER TABLE `shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75577;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992292;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_cuti_fk` FOREIGN KEY (`cuti_id`) REFERENCES `cuti` (`id`),
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`),
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`jadwal_shift_id`) REFERENCES `jadwal_shift` (`id`),
  ADD CONSTRAINT `absensi_izin_fk` FOREIGN KEY (`izin_id`) REFERENCES `izin` (`id`);

--
-- Constraints for table `cuti`
--
ALTER TABLE `cuti`
  ADD CONSTRAINT `cuti_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`);

--
-- Constraints for table `izin`
--
ALTER TABLE `izin`
  ADD CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`);

--
-- Constraints for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  ADD CONSTRAINT `jadwal_shift_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`),
  ADD CONSTRAINT `jadwal_shift_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`);

--
-- Constraints for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD CONSTRAINT `log_akses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `pegawai_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_2` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `daily_absensi_event` ON SCHEDULE EVERY 1 DAY STARTS '2024-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL auto_insert_absensi()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
