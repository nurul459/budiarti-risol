-- =====================================================================
-- Budiarti Risol — database dump (SANITIZED for public repo)
-- Source: phpMyAdmin export of `budiarti_risol` (localhost, 24 Nov 2026).
-- Structure: complete for all 16 tables (incl. foreign keys).
-- Data included: categories, products, settings (shop catalog + public
--   shop profile only).
-- Data REMOVED: users, orders, order_details, pembayaran, pengiriman,
--   pengembalian_penukaran, pengeluaran, preferensi_notifikasi, ulasan,
--   visitor_logs, wishlist, cart, crm_interaksi (personal / operational
--   data — names, emails, phone numbers, addresses, password hashes,
--   visitor IPs). Import this file, then create an admin account via
--   register.php or the admin panel; see README.md.
-- =====================================================================

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 12:14 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `budiarti_risol`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id_cart` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_category` int(11) NOT NULL,
  `nama_category` varchar(100) NOT NULL,
  `gambar_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_category`, `nama_category`, `gambar_category`) VALUES
(1, 'Risol Asin', 'kategori_722296a260372e77.jpg'),
(2, 'Risol Manis', 'kategori_ed603433a246e0b3.jpg'),
(3, 'Paket Hemat', 'kategori_9e764e0883c537a2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `crm_interaksi`
--

CREATE TABLE `crm_interaksi` (
  `id_interaksi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jenis_interaksi` varchar(50) NOT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal_interaksi` datetime NOT NULL DEFAULT current_timestamp(),
  `dibuat_oleh` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `provinsi` varchar(100) NOT NULL,
  `kota` varchar(100) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `kode_pos` varchar(10) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `ongkir` decimal(12,2) DEFAULT 0.00,
  `total_pembayaran` decimal(12,2) NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status` enum('Menunggu Pembayaran','Diproses','Dikirim','Selesai','Dibatalkan') DEFAULT 'Menunggu Pembayaran',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stok_dikurangi` tinyint(1) NOT NULL DEFAULT 0,
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id_detail` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `metode_pembayaran` varchar(30) NOT NULL,
  `jumlah` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_pembayaran` enum('Menunggu Verifikasi','Terverifikasi','Ditolak','Belum Dibayar','Lunas') NOT NULL DEFAULT 'Menunggu Verifikasi',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `tanggal_bayar` datetime DEFAULT NULL,
  `catatan` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `pengembalian_penukaran`
--

CREATE TABLE `pengembalian_penukaran` (
  `id_pengajuan` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jenis` enum('Pengembalian','Penukaran') NOT NULL,
  `alasan` varchar(100) NOT NULL,
  `detail_alasan` text DEFAULT NULL,
  `bukti_foto` varchar(255) DEFAULT NULL,
  `status` enum('Diajukan','Diproses','Disetujui','Ditolak','Selesai') NOT NULL DEFAULT 'Diajukan',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `pengiriman`
--

CREATE TABLE `pengiriman` (
  `id_pengiriman` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `kurir` varchar(100) NOT NULL DEFAULT '',
  `nomor_resi` varchar(100) NOT NULL DEFAULT '',
  `status_pengiriman` enum('Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai','Gagal') NOT NULL DEFAULT 'Belum Diproses',
  `tanggal_kirim` datetime DEFAULT NULL,
  `estimasi_sampai` date DEFAULT NULL,
  `catatan` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `preferensi_notifikasi`
--

CREATE TABLE `preferensi_notifikasi` (
  `id_user` int(11) NOT NULL,
  `notif_pesanan` tinyint(1) NOT NULL DEFAULT 1,
  `notif_promo` tinyint(1) NOT NULL DEFAULT 0,
  `notif_info` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id_product` int(11) NOT NULL,
  `id_category` int(11) NOT NULL,
  `nama_product` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `berat` varchar(100) DEFAULT '50 gram/pcs',
  `komposisi` text DEFAULT NULL,
  `cocok_untuk` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id_product`, `id_category`, `nama_product`, `deskripsi`, `harga`, `stok`, `gambar`, `created_at`, `berat`, `komposisi`, `cocok_untuk`) VALUES
(1, 1, 'Risol Mayo', 'Creamy, gurih, dan nikmat dalam satu gigitan.', 5000.00, 15, 'produk_20261114153619_19c075b9.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, mayones, telur rebus, smoked beef atau sosis, dan keju.', 'Sarapan praktis, bekal, atau camilan gurih saat istirahat.'),
(2, 1, 'Risol Ayam', 'Isian ayam berbumbu yang lezat dalam balutan risol keemasan.', 5000.00, 15, 'produk_20261114153602_b6317897.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, daging ayam suwir, wortel, bawang putih, dan bumbu.', 'Sarapan, bekal sekolah atau kuliah, dan camilan sore.'),
(3, 1, 'Risol Beef', 'Kulit renyah dengan isian daging sapi gurih yang kaya rasa.', 5000.00, 15, 'produk_20261114153549_142aad9a.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, daging sapi cincang, bawang bombai, wortel, dan bumbu gurih.', 'Sarapan, bekal, atau camilan yang lebih mengenyangkan di sela aktivitas.'),
(4, 1, 'Risol Sayur', 'Perpaduan sayuran pilihan dengan bumbu gurih yang menggugah selera.', 5000.00, 15, 'produk_20261114153535_f1a496c9.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, wortel, kol, kentang, daun bawang, dan bumbu.', 'Sarapan, camilan sore, atau sebagai pendamping teh hangat.'),
(5, 2, 'Risol Coklat', 'Isian cokelat manis yang siap memanjakan pencinta cokelat.', 5000.00, 14, 'produk_20261114153519_70ba8d27.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, cokelat batang atau meses cokelat, dan susu kental manis sesuai resep.', 'Waktu istirahat, camilan sore, atau saat ingin menikmati kudapan manis.'),
(6, 2, 'Risol Pisang Coklat', 'Manisnya pisang dan cokelat berpadu jadi camilan favorit.', 5000.00, 11, 'produk_20261114153508_9ddf2e80.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, pisang, cokelat, dan susu kental manis atau meses cokelat.', 'Camilan sore, teman minum susu, atau hidangan manis saat berkumpul.'),
(7, 2, 'Risol Keju', 'Gurihnya keju yang lumer di setiap gigitan.', 5000.00, 11, 'produk_20261114153449_555db012.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, keju cheddar atau mozzarella, susu, dan bumbu secukupnya.', 'Sarapan ringan, camilan sore, atau teman berkumpul bersama keluarga.'),
(8, 2, 'Risol Matcha', 'Sensasi manis matcha dengan cita rasa khas yang bikin penasaran.', 5000.00, 14, 'produk_20261114153432_8a2a43bb.jpg', '2026-09-26 08:00:39', '50 gram/pcs', 'Kulit risol, bubuk matcha, susu, gula, dan isian krim matcha.', 'Waktu santai sore hari, teman minum teh, atau camilan saat belajar.'),
(17, 3, 'Paket Ramai', 'Isi 8 pcs, semua varian rasa (masing-masing 1 pcs).', 35000.00, 15, 'produk_20261119091929_18796d1b.jpg', '2026-11-15 00:49:24', '50 gram/pcs', '8 pcs risol, terdiri dari 8 varian: coklat, matcha, pisang coklat, keju, ayam, sayur, beef, dan mayo.', 'Cocok untuk keluarga, acara kumpul, arisan, dan berbagi bersama teman.'),
(18, 3, 'Paket Hemat 1', 'Isi 5 pcs risol manis, terdiri dari 1 coklat, 1 matcha, 1 pisang coklat, dan 2 keju.', 23000.00, 15, 'produk_20261119091907_7cbc880a.jpg', '2026-11-15 00:53:19', '50 gram/pcs', '5 pcs risol manis, terdiri dari 1 coklat, 1 matcha, 1 pisang coklat, dan 2 keju.', 'Camilan santai, pecinta makanan manis, dan teman berkumpul.'),
(19, 3, 'Paket Hemat 2', 'Berisi 5 pcs risol asin, terdiri dari 1 ayam, 1 sayur, 1 beef, dan 2 mayo.', 23000.00, 13, 'produk_20261119091844_71181249.jpg', '2026-11-15 00:57:57', '50 gram/pcs', '5 pcs risol asin dengan kombinasi varian ayam, sayur, beef, dan mayo.', 'Camilan gurih, teman berkumpul, dan hidangan acara kecil.'),
(20, 3, 'Paket Hemat 3', 'Berisi 5 pcs risol campuran manis dan asin.', 23000.00, 0, 'produk_20261119091821_67c001b5.jpg', '2026-11-15 01:02:58', '50 gram/pcs', '1 Coklat, 1 Matcha, 1 Ayam, 1 Beef, 1 Mayo', 'Camilan pribadi, teman nongkrong, dan dinikmati bersama teman atau keluarga.');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id_setting` int(11) NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id_setting`, `nama_toko`, `slogan`, `no_hp`, `alamat`, `logo`, `favicon`, `updated_at`) VALUES
(1, 'Budiarti Risol', 'Risol Lezat, Buatan Rumah', '62 856-7806-654', 'Jl. Duku II, No. 76, RT 003/RW 007, Kelurahan Sukamaju Baru, Kecamatan Tapos, Kota Depok, Jawa Barat 16462.', NULL, NULL, '2026-11-14 13:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id_visitor` bigint(20) UNSIGNED NOT NULL,
  `visitor_key` char(64) NOT NULL,
  `halaman` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_kunjungan` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id_wishlist` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id_cart`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_product` (`id_product`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`);

--
-- Indexes for table `crm_interaksi`
--
ALTER TABLE `crm_interaksi`
  ADD PRIMARY KEY (`id_interaksi`),
  ADD KEY `idx_crm_user` (`id_user`),
  ADD KEY `idx_crm_date` (`tanggal_interaksi`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_order` (`id_order`),
  ADD KEY `id_product` (`id_product`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `uq_pembayaran_order` (`id_order`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id_pengeluaran`);

--
-- Indexes for table `pengembalian_penukaran`
--
ALTER TABLE `pengembalian_penukaran`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD UNIQUE KEY `uq_pengajuan_order` (`id_order`),
  ADD KEY `idx_pengajuan_user` (`id_user`),
  ADD KEY `idx_pengajuan_status` (`status`);

--
-- Indexes for table `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD PRIMARY KEY (`id_pengiriman`),
  ADD UNIQUE KEY `uq_pengiriman_order` (`id_order`),
  ADD KEY `idx_resi` (`nomor_resi`),
  ADD KEY `idx_status_pengiriman` (`status_pengiriman`);

--
-- Indexes for table `preferensi_notifikasi`
--
ALTER TABLE `preferensi_notifikasi`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `id_category` (`id_category`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id_setting`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id_ulasan`),
  ADD KEY `idx_ulasan_product` (`id_product`),
  ADD KEY `idx_ulasan_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id_visitor`),
  ADD KEY `idx_visitor_date` (`visitor_key`,`tanggal`),
  ADD KEY `idx_date` (`tanggal`),
  ADD KEY `idx_page` (`halaman`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id_wishlist`),
  ADD UNIQUE KEY `uq_wishlist_user_product` (`id_user`,`id_product`),
  ADD KEY `idx_wishlist_user` (`id_user`),
  ADD KEY `idx_wishlist_product` (`id_product`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id_cart` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `crm_interaksi`
--
ALTER TABLE `crm_interaksi`
  MODIFY `id_interaksi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengembalian_penukaran`
--
ALTER TABLE `pengembalian_penukaran`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengiriman`
--
ALTER TABLE `pengiriman`
  MODIFY `id_pengiriman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id_setting` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id_visitor` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=326;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id_wishlist` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id_category`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
