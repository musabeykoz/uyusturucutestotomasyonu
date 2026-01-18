-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 14 Ara 2025, 13:37:14
-- Sunucu sürümü: 11.4.7-MariaDB
-- PHP Sürümü: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `cromtest_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `purchase_link` varchar(500) DEFAULT NULL COMMENT 'Satın alma linki',
  `click_count` int(11) DEFAULT 0 COMMENT 'Ürün detay sayfası tıklanma sayısı',
  `purchase_click_count` int(11) DEFAULT 0 COMMENT 'Satın alma linki tıklanma sayısı',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `products`
--

-- Buraya ürün verilerinizi ekleyebilirsiniz

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_order` int(11) DEFAULT 0 COMMENT 'Görsel sıralaması',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT 'Ana görsel mi?',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `product_images`
--

-- Buraya ürün görseli verilerinizi ekleyebilirsiniz

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `test_results`
--

CREATE TABLE `test_results` (
  `id` int(11) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `test_date` datetime NOT NULL,
  `control_result` tinyint(1) DEFAULT NULL COMMENT 'C çizgisi - Control',
  `test_result` tinyint(1) DEFAULT NULL COMMENT 'T çizgisi - Test',
  `is_filled` tinyint(1) DEFAULT 0 COMMENT 'Kullanıcı sonuçları doldurdu mu?',
  `filled_at` datetime DEFAULT NULL COMMENT 'Sonuçların girildiği tarih',
  `test_status` enum('Beklemede','Tamamlandı','Geçersiz') DEFAULT 'Beklemede',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `test_results`
--

-- Test sonuçları buraya eklenecek

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','operator') DEFAULT 'operator',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$12$Rqq9Y6xcmq4r6DSEtQYy/OJ8CGzOXHIJbkWUjvysMA5owdZxc1CHK', 'Sistem Yöneticisi', 'admin@cromtest.com', 'admin', 1, '2025-12-02 12:26:26', '2025-12-14 10:25:15'),
(2, 'operator', '$2y$12$FDUp/sI4adCsxDrRd2zkluGVrxAW/6khUPTLv1OmUQXAvEJL75GIK', 'Operatör', 'operator@cromtest.com', 'operator', 1, '2025-12-02 12:26:26', NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Tablo için indeksler `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Tablo için indeksler `test_results`
--
ALTER TABLE `test_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_is_filled` (`is_filled`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Tablo için AUTO_INCREMENT değeri `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Tablo için AUTO_INCREMENT değeri `test_results`
--
ALTER TABLE `test_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `test_results`
--
ALTER TABLE `test_results`
  ADD CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;



COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
