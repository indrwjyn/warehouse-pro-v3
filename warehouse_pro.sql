-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2026 at 01:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warehouse_pro`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_category` int(11) NOT NULL,
  `name_category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`id_category`, `name_category`) VALUES
(1, 'Elektronik'),
(2, 'Packing'),
(3, 'Safety'),
(4, 'Cold Chain');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'pcs',
  `stock_min` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `storage_location_id` int(11) NOT NULL,
  `image_seed` varchar(120) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `unit`, `stock_min`, `current_stock`, `storage_location_id`, `image_seed`, `image_path`, `created_at`) VALUES
(1, 'LWT-EL-1001', 'Barcode Scanner Zebra DS2208', 'Scanner barcode untuk proses inbound dan outbound gudang.', 'unit', 8, 36, 4, 'barcode-scanner', NULL, '2026-06-15 11:25:54'),
(2, 'LWT-PK-2104', 'Thermal Label 100x150', 'Label pengiriman untuk print resi dan kode rak.', 'roll', 20, 140, 3, 'thermal-label', NULL, '2026-06-15 11:25:54'),
(3, 'LWT-SF-3320', 'Safety Helmet Orange', 'Helm keselamatan staff lapangan gudang.', 'pcs', 15, 74, 4, 'safety-helmet', NULL, '2026-06-15 11:25:54'),
(4, 'LWT-CL-4402', 'Insulated Food Box', 'Box makanan tahan suhu untuk penyimpanan sementara.', 'box', 10, 28, 2, 'insulated-food-box', NULL, '2026-06-15 11:25:54'),
(5, 'LWT-PK-2105', 'Kardus Packing Medium', 'Kardus ukuran 30x30x30 cm standar pengiriman.', 'pcs', 50, 200, 1, 'kardus-medium', NULL, '2026-06-16 09:00:00'),
(6, 'LWT-SF-3321', 'Safety Shoes 42', 'Sepatu safety ukuran 42 untuk area basah.', 'pasang', 5, 20, 4, 'safety-shoes', NULL, '2026-06-16 09:00:00'),
(7, 'LWT-EL-1002', 'Printer Thermal Zebra', 'Printer cetak resi gudang cabang.', 'unit', 2, 5, 2, 'printer-thermal', NULL, '2026-06-16 09:00:00'),
(8, 'LWT-CL-4403', 'Ice Gel Pack 500g', 'Pendingin portable untuk food box.', 'pcs', 100, 500, 2, 'ice-gel', NULL, '2026-06-16 09:00:00'),
(9, 'LWT-PK-2106', 'Lakban Bening 2 inch', 'Lakban standar packing barang.', 'roll', 30, 150, 3, 'lakban', NULL, '2026-06-16 09:00:00'),
(10, 'LWT-SF-3322', 'Rompi Safety Hijau', 'Rompi lapangan dengan reflektor cahaya.', 'pcs', 20, 60, 4, 'rompi', NULL, '2026-06-16 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `stock_entries`
--

CREATE TABLE `stock_entries` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `storage_location_id` int(11) NOT NULL,
  `vendor` varchar(150) NOT NULL,
  `sender_name` varchar(120) NOT NULL,
  `staff_in_charge_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `entry_date` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_entries`
--

INSERT INTO `stock_entries` (`id`, `product_id`, `storage_location_id`, `vendor`, `sender_name`, `staff_in_charge_id`, `quantity`, `entry_date`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'PT Sistem Logistik Nusantara', 'Andri Wijaya', 5, 20, '2026-06-01 09:20:00', 'Barang diterima lengkap dan sudah ditempel label rak.', 4, '2026-06-15 11:25:54'),
(2, 2, 3, 'CV Label Cepat', 'Sinta Permata', 5, 80, '2026-06-02 13:10:00', 'Masuk untuk kebutuhan packing mingguan.', 4, '2026-06-15 11:25:54'),
(3, 4, 2, 'PT Fresh Container', 'Dewi Laras', 5, 14, '2026-06-04 08:35:00', 'Dicek suhu ruangan sebelum disimpan.', 4, '2026-06-15 11:25:54'),
(6, 5, 1, 'PT Kardus Jaya', 'Budi Santoso', 5, 200, '2026-06-16 08:00:00', 'Restock material kardus.', 4, '2026-06-16 08:05:00'),
(7, 6, 4, 'CV Safety First', 'Rini Melati', 5, 20, '2026-06-16 09:00:00', 'Sepatu safety baru untuk batch karyawan baru.', 4, '2026-06-16 09:05:00'),
(8, 7, 2, 'Zebra Tech ID', 'Agus Salim', 5, 5, '2026-06-16 10:00:00', 'Unit printer cadangan.', 4, '2026-06-16 10:05:00'),
(9, 8, 2, 'PT Dingin Sejuk', 'Maya Sari', 5, 500, '2026-06-16 11:00:00', 'Ice gel persediaan akhir bulan.', 4, '2026-06-16 11:05:00'),
(10, 9, 3, 'Toko Alat Tulis', 'Joko Anwar', 5, 150, '2026-06-16 12:00:00', 'Lakban restock mingguan.', 4, '2026-06-16 12:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `stock_exits`
--

CREATE TABLE `stock_exits` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `storage_location_id` int(11) NOT NULL,
  `courier` varchar(100) NOT NULL,
  `staff_in_charge_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `exit_date` datetime NOT NULL,
  `awb_number` varchar(80) DEFAULT NULL,
  `destination` varchar(180) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_exits`
--

INSERT INTO `stock_exits` (`id`, `product_id`, `storage_location_id`, `courier`, `staff_in_charge_id`, `quantity`, `exit_date`, `awb_number`, `destination`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'jnt', 5, 4, '2026-06-03 15:40:00', 'JNT123456789', 'Cabang Semarang', 'Dikirim untuk penggantian perangkat kasir.', 4, '2026-06-15 11:25:54'),
(2, 2, 3, 'jnt', 5, 30, '2026-06-05 10:15:00', 'JNT987654321', 'Gudang Mikro Bekasi', 'Outbound label untuk batch pengiriman sore.', 4, '2026-06-15 11:25:54'),
(4, 5, 1, 'SiCepat', 5, 50, '2026-06-17 08:00:00', 'SCP123001', 'Bandung', 'Kirim stok kardus ke gudang cabang.', 4, '2026-06-17 08:05:00'),
(5, 6, 4, 'JNE', 5, 2, '2026-06-17 09:00:00', 'JNE123002', 'Surabaya', 'Retur sepatu cacat.', 4, '2026-06-17 09:05:00'),
(6, 8, 2, 'GoSend', 5, 100, '2026-06-17 10:00:00', 'GOS123003', 'Jakarta Selatan', 'Kirim ice gel untuk pengiriman kurir lokal.', 4, '2026-06-17 10:05:00'),
(7, 9, 3, 'JNT', 5, 20, '2026-06-17 11:00:00', 'JNT555004', 'Bekasi', 'Supplies packing harian.', 4, '2026-06-17 11:05:00'),
(8, 10, 4, 'Wahana', 5, 5, '2026-06-17 12:00:00', 'WHN123005', 'Bogor', 'Pengadaan rompi staf cabang.', 4, '2026-06-17 12:05:00'),
(9, 2, 3, 'SiCepat', 5, 10, '2026-06-17 13:00:00', 'SCP444006', 'Tangerang', 'Refill label thermal.', 4, '2026-06-17 13:05:00'),
(10, 4, 2, 'JNE', 5, 5, '2026-06-17 14:00:00', 'JNE999007', 'Depok', 'Distribusi food box tambahan.', 4, '2026-06-17 14:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `storage_locations`
--

CREATE TABLE `storage_locations` (
  `id` int(11) NOT NULL,
  `warehouse_name` varchar(120) NOT NULL,
  `room_name` varchar(80) NOT NULL,
  `rack_number` varchar(40) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage_locations`
--

INSERT INTO `storage_locations` (`id`, `warehouse_name`, `room_name`, `rack_number`, `capacity`, `notes`, `created_at`) VALUES
(1, 'Gudang Utama Jakarta', 'Receiving Room', 'RAK-A01', 800, 'Area barang cepat masuk', '2026-06-15 11:25:54'),
(2, 'Gudang Utama Jakarta', 'Cold Safe Room', 'RAK-C03', 300, 'Rak produk sensitif suhu', '2026-06-15 11:25:54'),
(3, 'Gudang Bandung', 'Dispatch Room', 'RAK-D07', 600, 'Area barang siap kirim', '2026-06-15 11:25:54'),
(4, 'Gudang Surabaya', 'Bulk Room', 'RAK-B12', 1200, 'Rak stok volume besar', '2026-06-15 11:25:54'),
(5, 'Gudang Utama Jakarta', 'Hazardous Room', 'RAK-H01', 200, 'Area produk kimia/berbahaya', '2026-06-16 08:30:00'),
(6, 'Gudang Bandung', 'Receiving Room', 'RAK-A02', 500, 'Area penerimaan barang', '2026-06-16 08:30:00'),
(7, 'Gudang Bandung', 'Cold Safe Room', 'RAK-C01', 300, 'Penyimpanan suhu terkontrol', '2026-06-16 08:30:00'),
(8, 'Gudang Surabaya', 'Dispatch Room', 'RAK-D01', 600, 'Barang antrian pengiriman', '2026-06-16 08:30:00'),
(9, 'Gudang Makassar', 'Main Room', 'RAK-M01', 1000, 'Gudang operasional baru', '2026-06-16 08:30:00'),
(10, 'Gudang Medan', 'Receiving Room', 'RAK-MD01', 800, 'Area transit masuk', '2026-06-16 08:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('direktur','manager','kepala_gudang','admin_gudang','staff_gudang','hrd') NOT NULL DEFAULT 'staff_gudang',
  `phone` varchar(30) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `phone`, `profile_photo`, `created_by`, `last_login`, `created_at`) VALUES
(1, 'Darmawan Santoso', 'direktur', '$2y$10$cS01QxqAprzqL6MDaOk7bOfkF6z4TprW2.j43uCM4zJBJdYR3uxry', 'direktur', '0811-0001-0001', NULL, NULL, '2026-06-15 20:42:58', '2026-06-15 11:25:54'),
(2, 'Raka Pradipta', 'manager', '$2y$10$.uvcuZqJjCItjhWIYmAfS.l6tKnlf5GwKAZiOSi7rsyAvqUyzhTe6', 'manager', '0812-1100-2026', NULL, 1, '2026-10-30 18:39:44', '2026-06-15 11:25:54'),
(3, 'Dimas Prasetyo', 'kepalagudang', '$2y$10$yqbCjexrmbG3LyLkgpKS.uAdX5BNoWHZlPhwVXYdvDSGcyrSwEFum', 'kepala_gudang', '0815-7766-9900', NULL, 2, '2026-06-15 20:46:19', '2026-06-15 11:25:54'),
(4, 'Hendra Kusuma', 'admingudang', '$2y$10$5c8.NXQjAl1Pw0VmqN/5IugbtbflGZNMTZcuKFCnmD.bb/6wb5odu', 'admin_gudang', '0813-2200-3030', NULL, 3, '2026-06-15 21:43:58', '2026-06-15 11:25:54'),
(5, 'Maya Lestari', 'staff', '$2y$10$gP0pGAxyzTRZHmmz9wfH0OEHK03goCCJAqT1f5hszFv6mDsO.lAX6', 'staff_gudang', '0813-4455-6677', NULL, 4, '2026-06-15 21:42:37', '2026-06-15 11:25:54'),
(6, 'Sari Indah', 'hrd', '$2y$10$zcmiqsqDG0agXFmvxLFixeJl3gIgF3.PqPP9XCcUsvZL8IHIoIAoS', 'hrd', '0812-9988-7766', NULL, 2, '2026-06-15 21:44:17', '2026-06-15 11:25:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_logistics_report`
-- (See below for the actual view)
--
CREATE TABLE `vw_logistics_report` (
`log_code` varchar(15)
,`movement_type` varchar(3)
,`reference_id` int(11)
,`sku` varchar(60)
,`product_name` varchar(150)
,`unit` varchar(30)
,`quantity` int(11)
,`warehouse_name` varchar(120)
,`room_name` varchar(80)
,`rack_number` varchar(40)
,`partner_name` varchar(180)
,`sender_name` varchar(120)
,`courier` varchar(100)
,`awb_number` varchar(80)
,`staff_in_charge` varchar(120)
,`recorded_by` varchar(120)
,`movement_date` datetime
,`notes` varchar(255)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_logistics_report`
--
DROP TABLE IF EXISTS `vw_logistics_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_logistics_report`  AS SELECT concat('IN-',`se`.`id`) AS `log_code`, 'IN' AS `movement_type`, `se`.`id` AS `reference_id`, `p`.`sku` AS `sku`, `p`.`name` AS `product_name`, `p`.`unit` AS `unit`, `se`.`quantity` AS `quantity`, `sl`.`warehouse_name` AS `warehouse_name`, `sl`.`room_name` AS `room_name`, `sl`.`rack_number` AS `rack_number`, `se`.`vendor` AS `partner_name`, `se`.`sender_name` AS `sender_name`, NULL AS `courier`, NULL AS `awb_number`, `staff`.`name` AS `staff_in_charge`, `maker`.`name` AS `recorded_by`, `se`.`entry_date` AS `movement_date`, `se`.`notes` AS `notes` FROM ((((`stock_entries` `se` join `products` `p` on(`se`.`product_id` = `p`.`id`)) join `storage_locations` `sl` on(`se`.`storage_location_id` = `sl`.`id`)) join `users` `staff` on(`se`.`staff_in_charge_id` = `staff`.`id`)) join `users` `maker` on(`se`.`created_by` = `maker`.`id`))union all select concat('OUT-',`sx`.`id`) AS `log_code`,'OUT' AS `movement_type`,`sx`.`id` AS `reference_id`,`p`.`sku` AS `sku`,`p`.`name` AS `product_name`,`p`.`unit` AS `unit`,`sx`.`quantity` AS `quantity`,`sl`.`warehouse_name` AS `warehouse_name`,`sl`.`room_name` AS `room_name`,`sl`.`rack_number` AS `rack_number`,`sx`.`destination` AS `partner_name`,NULL AS `sender_name`,`sx`.`courier` AS `courier`,`sx`.`awb_number` AS `awb_number`,`staff`.`name` AS `staff_in_charge`,`maker`.`name` AS `recorded_by`,`sx`.`exit_date` AS `movement_date`,`sx`.`notes` AS `notes` from ((((`stock_exits` `sx` join `products` `p` on(`sx`.`product_id` = `p`.`id`)) join `storage_locations` `sl` on(`sx`.`storage_location_id` = `sl`.`id`)) join `users` `staff` on(`sx`.`staff_in_charge_id` = `staff`.`id`)) join `users` `maker` on(`sx`.`created_by` = `maker`.`id`))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `storage_location_id` (`storage_location_id`);

--
-- Indexes for table `stock_entries`
--
ALTER TABLE `stock_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `storage_location_id` (`storage_location_id`),
  ADD KEY `staff_in_charge_id` (`staff_in_charge_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_exits`
--
ALTER TABLE `stock_exits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `storage_location_id` (`storage_location_id`),
  ADD KEY `staff_in_charge_id` (`staff_in_charge_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_location` (`warehouse_name`,`room_name`,`rack_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_entries`
--
ALTER TABLE `stock_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_exits`
--
ALTER TABLE `stock_exits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `storage_locations`
--
ALTER TABLE `storage_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_locations` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_entries`
--
ALTER TABLE `stock_entries`
  ADD CONSTRAINT `stock_entries_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_entries_ibfk_2` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_locations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_entries_ibfk_3` FOREIGN KEY (`staff_in_charge_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_entries_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_exits`
--
ALTER TABLE `stock_exits`
  ADD CONSTRAINT `stock_exits_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_exits_ibfk_2` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_locations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_exits_ibfk_3` FOREIGN KEY (`staff_in_charge_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_exits_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
