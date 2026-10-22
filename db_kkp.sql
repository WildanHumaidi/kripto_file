-- Schema Database untuk Aplikasi Kriptografi File AES (db_kkp)
-- Rekonstruksi kanonis berdasarkan analisis query kode sumber

CREATE DATABASE IF NOT EXISTS `db_kkp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_kkp`;

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `job_title` VARCHAR(50) NOT NULL,
  `last_activity` DATETIME DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Data Awal Pengguna Default (Password MD5: '1' -> c4ca4238a0b923820dcc509a6f75849b)
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password`, `fullname`, `job_title`, `last_activity`)
VALUES ('admin', 'c4ca4238a0b923820dcc509a6f75849b', 'Administrator', 'Security Officer', NOW())
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

-- --------------------------------------------------------
-- Struktur Tabel `file`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `file` (
  `id_file` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `file_name_source` VARCHAR(255) NOT NULL,
  `file_name_finish` VARCHAR(255) NOT NULL,
  `file_url` VARCHAR(255) NOT NULL,
  `file_size` VARCHAR(50) NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `tgl_upload` DATETIME NOT NULL,
  `status` ENUM('1', '2') NOT NULL DEFAULT '1' COMMENT '1=Terenkripsi, 2=Didekripsi',
  `keterangan` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_file`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
