-- ========================================================
-- 7CellX Database Setup
-- Deskripsi: Schema lengkap untuk toko HP online
-- Author: Generated from PBW Project Development
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. SETUP DATABASE (Jalankan sekali saja)
CREATE DATABASE IF NOT EXISTS `db_7cellx` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_7cellx`;

-- ========================================================
-- 2. DROP EXISTING TABLES (UNCOMMENT JIKA INGIN RESET TOTAL)
-- ========================================================
-- DROP TABLE IF EXISTS `chats`;
-- DROP TABLE IF EXISTS `order_details`;
-- DROP TABLE IF EXISTS `orders`;
-- DROP TABLE IF EXISTS `products`;
-- DROP TABLE IF EXISTS `users`;

-- ========================================================
-- 3. CREATE TABLES
-- ========================================================

-- Tabel Users (Admin & Customer)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','customer') DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` VARCHAR(150) NOT NULL,
  `kategori` VARCHAR(50) NOT NULL,
  `harga_min` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `harga_max` DECIMAL(12,2) DEFAULT 0.00,
  `stok` INT(11) NOT NULL DEFAULT 0,
  `deskripsi` TEXT DEFAULT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `varian` TEXT DEFAULT NULL COMMENT 'JSON: [{"ram":"8","rom":"128","harga":7000000},...]',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `total_harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `alamat` TEXT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_detail` TEXT DEFAULT NULL COMMENT 'No Rek / No E-wallet / COD',
  `status` ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Order Details
CREATE TABLE IF NOT EXISTS `order_details` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `jumlah` INT(11) NOT NULL DEFAULT 1,
  `harga_satuan` DECIMAL(12,2) NOT NULL,
  `varian` VARCHAR(50) DEFAULT NULL COMMENT 'Contoh: 8/256 GB',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Chats (Real-time Support)
CREATE TABLE IF NOT EXISTS `chats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'Customer ID',
  `sender_role` ENUM('admin','customer') NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 4. INITIAL DATA (SAMPING DEVELOPMENT)
-- ========================================================

-- Admin Default (Password: 123 | Hash bcrypt standar PHP)
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@7cellx.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());
-- ⚠️ CATATAN: Ganti hash password di atas dengan hasil password_hash('123', PASSWORD_DEFAULT) dari PHP Anda jika perlu.

-- Sample Product
INSERT INTO `products` (`id`, `nama_barang`, `kategori`, `harga_min`, `harga_max`, `stok`, `deskripsi`, `gambar`, `varian`, `created_at`) VALUES
(1, 'Galaxy S26 Basic', 'Samsung', 12000000.00, 15000000.00, 15, 'Smartphone flagship terbaru dengan kamera 200MP dan chipset generasi terbaru.', NULL, '[{"ram":"8","rom":"256","harga":12000000},{"ram":"12","rom":"256","harga":13500000},{"ram":"12","rom":"512","harga":15000000}]', NOW());

COMMIT;