-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 06, 2024 at 06:50 AM
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `reset_database_tables` ()   BEGIN
    -- Nonaktifkan pemeriksaan foreign key terlebih dahulu
    SET FOREIGN_KEY_CHECKS = 0;

    -- Kosongkan tabel absensi
    TRUNCATE TABLE absensi;

    -- Kosongkan tabel log_akses
    TRUNCATE TABLE log_akses;

    -- Aktifkan kembali pemeriksaan foreign key
    SET FOREIGN_KEY_CHECKS = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_attendance` ()   BEGIN
    -- Deklarasi variabel untuk iterasi dan kontrol
    DECLARE done INT DEFAULT FALSE;
    DECLARE curr_tanggal DATE;
    
    -- Cursor untuk mengambil semua tanggal sebelum hari ini dengan absensi tidak lengkap
    DECLARE cur_incomplete_attendance CURSOR FOR 
        SELECT DISTINCT DATE(tanggal) AS tanggal
        FROM absensi 
        WHERE DATE(tanggal) < CURRENT_DATE()
        AND waktu_masuk != '00:00:00' 
        AND waktu_keluar = '00:00:00';
    
    -- Handler untuk mengatur status selesai ketika cursor habis
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Set timezone ke Asia/Jakarta (GMT+7)
    SET time_zone = '+07:00';

    -- Membuka cursor
    OPEN cur_incomplete_attendance;

    -- Memulai loop untuk setiap tanggal dengan absensi tidak lengkap
    read_loop: LOOP
        -- Mengambil tanggal berikutnya
        FETCH cur_incomplete_attendance INTO curr_tanggal;

        -- Keluar jika tidak ada tanggal lagi
        IF done THEN 
            LEAVE read_loop; 
        END IF;

        -- Update status menjadi tidak_absen_pulang untuk tanggal yang dipilih
        UPDATE absensi 
        SET status_kehadiran = 'tidak_absen_pulang'
        WHERE DATE(tanggal) = curr_tanggal
        AND waktu_masuk != '00:00:00'
        AND waktu_keluar = '00:00:00';

    END LOOP;

    -- Menutup cursor
    CLOSE cur_incomplete_attendance;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jadwal_shift_id` int NOT NULL,
  `waktu_masuk` time DEFAULT NULL,
  `waktu_keluar` time DEFAULT NULL,
  `kode_unik` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `status_kehadiran` enum('hadir','terlambat','izin','alpha','cuti','pulang_dahulu','tidak_absen_pulang','libur') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'alpha',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `durasi_cuti` int DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('pending','disetujui','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
`durasi_cuti` int
,`keterangan` text
,`nama_staff` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal_mulai` date
,`tanggal_selesai` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `cuti_ditolak`
-- (See below for the actual view)
--
CREATE TABLE `cuti_ditolak` (
`durasi_cuti` int
,`keterangan` text
,`nama_staff` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal_mulai` date
,`tanggal_selesai` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `cuti_view`
-- (See below for the actual view)
--
CREATE TABLE `cuti_view` (
`durasi_cuti` int
,`keterangan` text
,`nama_staff` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal_mulai` date
,`tanggal_selesai` date
);

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id` int NOT NULL,
  `nama_divisi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_izin` enum('keperluan_pribadi','dinas_luar','sakit') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('pending','disetujui','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
`jenis_izin` enum('keperluan_pribadi','dinas_luar','sakit')
,`keterangan` text
,`nama_lengkap` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `izin_ditolak`
-- (See below for the actual view)
--
CREATE TABLE `izin_ditolak` (
`jenis_izin` enum('keperluan_pribadi','dinas_luar','sakit')
,`keterangan` text
,`nama_lengkap` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal` date
);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_shift`
--

CREATE TABLE `jadwal_shift` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `shift_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'aktif'
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
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `waktu` datetime DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `device_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('logout','login','first_registration') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `device_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `device_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `otp_code`
--

CREATE TABLE `otp_code` (
  `id` int NOT NULL,
  `otp_code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `divisi_id` int NOT NULL,
  `status_aktif` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'aktif',
  `hari_libur` enum('senin','selasa','rabu','kamis','jumat','sabtu','minggu') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
-- Stand-in structure for view `perizinan_setuju_tolak`
-- (See below for the actual view)
--
CREATE TABLE `perizinan_setuju_tolak` (
`jenis_izin` enum('keperluan_pribadi','dinas_luar','sakit')
,`keterangan` text
,`nama_lengkap` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `perizinan_view`
-- (See below for the actual view)
--
CREATE TABLE `perizinan_view` (
`jenis_izin` enum('keperluan_pribadi','dinas_luar','sakit')
,`keterangan` text
,`nama_lengkap` varchar(100)
,`status` enum('pending','disetujui','ditolak')
,`tanggal` date
);

-- --------------------------------------------------------

--
-- Table structure for table `qr_code`
--

CREATE TABLE `qr_code` (
  `id` int NOT NULL,
  `kode_unik` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `shift`
--

CREATE TABLE `shift` (
  `id` int NOT NULL,
  `nama_shift` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `jenis_kelamin` enum('laki','perempuan') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `role` enum('owner','karyawan') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `no_telp` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `id_otp` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Structure for view `cuti_disetujui`
--
DROP TABLE IF EXISTS `cuti_disetujui`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_disetujui`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on((`cuti`.`pegawai_id` = `pegawai`.`id`))) join `users` on((`pegawai`.`user_id` = `users`.`id`))) WHERE (`cuti`.`status` = 'disetujui')  ;

-- --------------------------------------------------------

--
-- Structure for view `cuti_ditolak`
--
DROP TABLE IF EXISTS `cuti_ditolak`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_ditolak`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on((`cuti`.`pegawai_id` = `pegawai`.`id`))) join `users` on((`pegawai`.`user_id` = `users`.`id`))) WHERE (`cuti`.`status` = 'ditolak')  ;

-- --------------------------------------------------------

--
-- Structure for view `cuti_view`
--
DROP TABLE IF EXISTS `cuti_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cuti_view`  AS SELECT `users`.`nama_lengkap` AS `nama_staff`, `cuti`.`tanggal_mulai` AS `tanggal_mulai`, `cuti`.`tanggal_selesai` AS `tanggal_selesai`, `cuti`.`durasi_cuti` AS `durasi_cuti`, `cuti`.`keterangan` AS `keterangan`, `cuti`.`status` AS `status` FROM ((`cuti` join `pegawai` on((`pegawai`.`id` = `cuti`.`pegawai_id`))) join `users` on((`users`.`id` = `pegawai`.`user_id`)))  ;

-- --------------------------------------------------------

--
-- Structure for view `izin_disetujui`
--
DROP TABLE IF EXISTS `izin_disetujui`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `izin_disetujui`  AS SELECT `users`.`nama_lengkap` AS `nama_lengkap`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on((`pegawai`.`id` = `izin`.`pegawai_id`))) join `users` on((`users`.`id` = `pegawai`.`user_id`))) WHERE (`izin`.`status` = 'disetujui')  ;

-- --------------------------------------------------------

--
-- Structure for view `izin_ditolak`
--
DROP TABLE IF EXISTS `izin_ditolak`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `izin_ditolak`  AS SELECT `users`.`nama_lengkap` AS `nama_lengkap`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on((`pegawai`.`id` = `izin`.`pegawai_id`))) join `users` on((`users`.`id` = `pegawai`.`user_id`))) WHERE (`izin`.`status` = 'ditolak')  ;

-- --------------------------------------------------------

--
-- Structure for view `perizinan_setuju_tolak`
--
DROP TABLE IF EXISTS `perizinan_setuju_tolak`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `perizinan_setuju_tolak`  AS SELECT `users`.`nama_lengkap` AS `nama_lengkap`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on((`pegawai`.`id` = `izin`.`pegawai_id`))) join `users` on((`users`.`id` = `pegawai`.`user_id`))) WHERE (`izin`.`status` in ('disetujui','ditolak'))  ;

-- --------------------------------------------------------

--
-- Structure for view `perizinan_view`
--
DROP TABLE IF EXISTS `perizinan_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `perizinan_view`  AS SELECT `users`.`nama_lengkap` AS `nama_lengkap`, `izin`.`tanggal` AS `tanggal`, `izin`.`jenis_izin` AS `jenis_izin`, `izin`.`keterangan` AS `keterangan`, `izin`.`status` AS `status` FROM ((`izin` join `pegawai` on((`pegawai`.`id` = `izin`.`pegawai_id`))) join `users` on((`users`.`id` = `pegawai`.`user_id`)))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `karyawan_id` (`pegawai_id`),
  ADD KEY `jadwal_shift_id` (`jadwal_shift_id`);

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
-- Indexes for table `otp_code`
--
ALTER TABLE `otp_code`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `divisi_id` (`divisi_id`);

--
-- Indexes for table `qr_code`
--
ALTER TABLE `qr_code`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_otp` (`id_otp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cuti`
--
ALTER TABLE `cuti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654325;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234237;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12314140;

--
-- AUTO_INCREMENT for table `jadwal_shift`
--
ALTER TABLE `jadwal_shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `log_akses`
--
ALTER TABLE `log_akses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=938431;

--
-- AUTO_INCREMENT for table `otp_code`
--
ALTER TABLE `otp_code`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992334;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `shift`
--
ALTER TABLE `shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75588;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992328;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`),
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`jadwal_shift_id`) REFERENCES `jadwal_shift` (`id`);

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

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_otp`) REFERENCES `otp_code` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `auto_clear_database` ON SCHEDULE EVERY 1 YEAR STARTS '2024-11-07 16:54:05' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    CALL reset_database_table();
END$$

CREATE DEFINER=`root`@`localhost` EVENT `auto_insert_absensi_event` ON SCHEDULE EVERY 10 SECOND STARTS '2024-11-07 16:54:05' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    CALL update_attendance();
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
