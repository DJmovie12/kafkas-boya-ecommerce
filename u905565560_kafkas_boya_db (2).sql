-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 27 Kas 2025, 14:57:34
-- Sunucu sürümü: 11.8.3-MariaDB-log
-- PHP Sürümü: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `u905565560_kafkas_boya_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `logo_url`, `created_at`, `updated_at`) VALUES
(1, 'Polisan', 'Premium kaliteli boya ve vernik ürünleri', 'assets/img/polisan.webp', '2025-11-24 13:24:16', '2025-11-25 07:07:11'),
(2, 'Filli Boya', 'Geniş renk paletine sahip profesyonel boya', 'assets/img/filli-boya.webp', '2025-11-24 13:24:16', '2025-11-25 07:07:11'),
(3, 'Marshall', 'Dayanıklı ve uzun ömürlü boya çözümleri', 'assets/img/marshall.webp', '2025-11-24 13:24:16', '2025-11-25 07:07:11'),
(4, 'DYO', 'Ekonomik ve kaliteli boya seçenekleri', 'assets/img/dyo.webp', '2025-11-24 13:24:16', '2025-11-25 07:07:11'),
(5, 'Permolit', 'Profesyonel ve endüstriyel boya ürünleri', 'assets/img/permolit.webp', '2025-11-24 13:24:16', '2025-11-25 07:07:11');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(8, 1, 1, 8, '2025-11-25 10:13:51'),
(9, 1, 3, 8, '2025-11-25 10:14:50'),
(10, 1, 2, 1, '2025-11-25 10:29:29'),
(11, 1, 6, 2, '2025-11-25 10:30:13'),
(12, 1, 5, 3, '2025-11-25 10:30:15'),
(13, 1, 4, 1, '2025-11-25 10:30:16'),
(19, 5, 13, 2, '2025-11-26 11:07:24'),
(20, 5, 2, 1, '2025-11-26 11:07:24'),
(21, 5, 5, 1, '2025-11-26 11:07:24'),
(22, 5, 4, 1, '2025-11-26 11:07:24'),
(23, 5, 3, 2, '2025-11-26 11:07:24'),
(40, 8, 7, 9, '2025-11-27 13:07:11'),
(43, 10, 13, 1, '2025-11-27 13:42:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'İç Cephe', 'İç mekan boyama ürünleri', '2025-11-24 13:24:17', '2025-11-24 13:24:17'),
(2, 'Dış Cephe', 'Dış mekan boyama ürünleri', '2025-11-24 13:24:17', '2025-11-24 13:24:17'),
(3, 'Tavan', 'Tavan boyama ürünleri', '2025-11-24 13:24:17', '2025-11-24 13:24:17'),
(4, 'Ahşap & Metal', 'Ahşap ve metal yüzeyler için boya', '2025-11-24 13:24:17', '2025-11-24 13:24:17'),
(5, 'Astar', 'Astar ve hazırlık ürünleri', '2025-11-24 13:24:17', '2025-11-24 13:24:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contacts`
--

INSERT INTO `contacts` (`id`, `user_id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'burak yasar', 'admin@kafkasboya.com', '05537038203', 'deneme', 'deniyorum yorum test', 'read', '2025-11-27 07:26:12', '2025-11-27 08:08:09'),
(3, 1, 'burak yasar', 'burak.ysr16@gmail.com', '0934238443', 'deneme', 'dewfg3w4tg3ewrgew5r4hge5rh', 'replied', '2025-11-27 07:55:00', '2025-11-27 08:08:09'),
(4, 1, 'burak yasar', 'burak.ysr16@gmail.com', '0934238443', 'deneme', 'dewfg3w4tg3ewrgew5r4hge5rh', 'read', '2025-11-27 07:55:38', '2025-11-27 14:52:52'),
(5, 4, 'ustaniz_geldi', 'usta@gmail.cd', '28956982364', 'deneme', 'fwefgaresgershgred5hredhrth', 'replied', '2025-11-27 08:13:51', '2025-11-27 08:15:23'),
(9, 7, 'Türkçe', 'admin@kafkasboya.com', '05537038203', 'deneme', 'egwrefgbedsrfhbedrfhhthrhtgrhr', 'replied', '2025-11-27 12:58:11', '2025-11-27 13:03:21'),
(10, 11, 'Cemil', 'cemil@gmail.com', '05555555555555555555', 'BABAPRODAN BİR BİLDİRİ !', 'BU SİTE GÜZEL OLMUŞ', 'replied', '2025-11-27 14:02:30', '2025-11-27 14:03:15'),
(11, 11, 'Cemil', 'cemil@gmail.com', 'önemli deil', 'önemli deil', 'ööönemli deil', 'replied', '2025-11-27 14:03:41', '2025-11-27 14:05:04');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reply_message` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contact_replies`
--

INSERT INTO `contact_replies` (`id`, `contact_id`, `admin_id`, `reply_message`, `created_at`, `updated_at`) VALUES
(2, 3, 1, 'eryhrtjhtyjkytgukıyuhıkou', '2025-11-27 07:57:53', '2025-11-27 07:57:53'),
(3, 5, 1, 'lütfen düzgün mesaj yazalım pls', '2025-11-27 08:15:23', '2025-11-27 08:15:23'),
(4, 9, 1, 'test yorum deneme', '2025-11-27 13:03:21', '2025-11-27 13:03:21'),
(5, 10, 1, 'teşekkürler reis kralsın', '2025-11-27 14:03:15', '2025-11-27 14:03:15'),
(6, 10, 1, 'regıojnhseorıjhgnoırsth', '2025-11-27 14:03:26', '2025-11-27 14:03:26'),
(7, 10, 1, 'ıjedsroıgjhnorhgrth', '2025-11-27 14:04:25', '2025-11-27 14:04:25'),
(8, 11, 1, 'rica ederim dostum sitemizi kullandığın için tşk', '2025-11-27 14:05:04', '2025-11-27 14:05:04');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, 2761.20, 'pending', '2025-11-25 10:35:05', '2025-11-25 10:35:05'),
(4, 4, 177.00, 'delivered', '2025-11-25 10:39:05', '2025-11-25 11:32:41'),
(5, 4, 5841.00, 'pending', '2025-11-25 14:52:32', '2025-11-25 14:52:32'),
(6, 5, 2655.00, 'pending', '2025-11-26 09:44:06', '2025-11-26 09:44:06'),
(7, 6, 6549.00, 'delivered', '2025-11-26 11:36:25', '2025-11-26 11:46:59'),
(8, 6, 1392.40, 'pending', '2025-11-26 12:13:03', '2025-11-26 12:13:03'),
(9, 4, 3528.20, 'pending', '2025-11-27 06:46:44', '2025-11-27 06:46:44'),
(10, 7, 6903.00, 'pending', '2025-11-27 13:05:43', '2025-11-27 13:05:43'),
(11, 9, 3964.80, 'pending', '2025-11-27 13:27:10', '2025-11-27 13:27:10'),
(12, 11, 177.00, 'shipped', '2025-11-27 14:07:18', '2025-11-27 14:07:42'),
(13, 4, 11210.00, 'pending', '2025-11-27 14:54:49', '2025-11-27 14:54:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(3, 3, 10, 13, 180.00, '2025-11-25 10:35:05'),
(4, 4, 1, 1, 150.00, '2025-11-25 10:39:05'),
(5, 5, 1, 33, 150.00, '2025-11-25 14:52:32'),
(6, 6, 1, 15, 150.00, '2025-11-26 09:44:06'),
(7, 7, 4, 9, 120.00, '2025-11-26 11:36:25'),
(8, 7, 5, 4, 280.00, '2025-11-26 11:36:25'),
(9, 7, 6, 4, 750.00, '2025-11-26 11:36:25'),
(10, 7, 7, 2, 130.00, '2025-11-26 11:36:25'),
(11, 7, 8, 1, 90.00, '2025-11-26 11:36:25'),
(12, 8, 2, 2, 350.00, '2025-11-26 12:13:03'),
(13, 8, 4, 4, 120.00, '2025-11-26 12:13:03'),
(14, 9, 7, 23, 130.00, '2025-11-27 06:46:44'),
(15, 10, 13, 13, 450.00, '2025-11-27 13:05:43'),
(16, 11, 4, 7, 120.00, '2025-11-27 13:27:10'),
(17, 11, 5, 9, 280.00, '2025-11-27 13:27:10'),
(18, 12, 1, 1, 150.00, '2025-11-27 14:07:18'),
(19, 13, 2, 22, 350.00, '2025-11-27 14:54:49'),
(20, 13, 11, 9, 200.00, '2025-11-27 14:54:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `detailed_description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `usage_instructions` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `detailed_description`, `features`, `usage_instructions`, `specifications`, `price`, `stock`, `brand_id`, `category_id`, `image`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Polisan Premium İç Cephe Boyası 1L', 'Yüksek kaliteli, mat bitişli iç cephe boyası', 'Polisan Premium İç Cephe Boyası, su bazlı, mat bitişli, tüm iç mekan yüzeylerinde kullanıma uygun yüksek kaliteli bir boyadır. Özel formülü sayesinde kolay uygulanır, hızlı kurur ve uzun süre dayanıklılığını korur.', 'Su bazlı formül\\nKoku minimize edilmiş\\nHızlı kuruma\\nKolay uygulama\\nYıkanabilir yüzey\\nKüf ve mantara karşı koruma', '1. Yüzeyi temizleyin ve kuru olduğundan emin olun\\n2. Gerekirse astar uygulayın\\n3. Boyayı iyice karıştırın\\n4. Fırça, rulo veya püskürtme yöntemiyle uygulayın\\n5. 2-4 saat arası kurumaya bırakın\\n6. İkinci katı uygulayın', 'Renk: Beyaz\\nKuru Film Kalınlığı: 80-100 mikron\\nKuruma Süresi: 2-4 saat\\nUygulama Sıcaklığı: +5°C ile +35°C\\nKutu Başına Kaplama: 12-14 m²/L', 150.00, 1, 1, 1, 'assets/img/products/shop_01.webp', '2025-11-24 13:24:17', '2025-11-27 14:07:18', 1),
(2, 'Polisan Premium İç Cephe Boyası 2.5L', 'Yüksek kaliteli, mat bitişli iç cephe boyası', NULL, NULL, NULL, NULL, 350.00, 19, 1, 1, 'assets/img/products/shop_02.webp', '2025-11-24 13:24:17', '2025-11-27 14:54:49', 1),
(3, 'Polisan Premium Dış Cephe Boyası 5L', 'Dayanıklı dış cephe boyası, UV korumalı', NULL, NULL, NULL, NULL, 800.00, 30, 1, 2, 'assets/img/products/shop_03.webp', '2025-11-24 13:24:17', '2025-11-24 14:35:43', 1),
(4, 'Filli Boya İç Cephe 1L', 'Geniş renk seçeneği ile iç cephe boyası', NULL, NULL, NULL, NULL, 120.00, 40, 2, 1, 'assets/img/products/shop_04.webp', '2025-11-24 13:24:17', '2025-11-27 13:27:10', 1),
(5, 'Filli Boya İç Cephe 2.5L', 'Geniş renk seçeneği ile iç cephe boyası', NULL, NULL, NULL, NULL, 280.00, 37, 2, 1, 'assets/img/products/shop_05.webp', '2025-11-24 13:24:17', '2025-11-27 13:27:10', 1),
(6, 'Marshall Dış Cephe 5L', 'Uzun ömürlü dış cephe koruma boyası', NULL, NULL, NULL, NULL, 750.00, 21, 3, 2, 'assets/img/products/shop_06.webp', '2025-11-24 13:24:17', '2025-11-26 11:36:25', 1),
(7, 'Marshall Tavan Boyası 1L', 'Tavan boyama için özel formülasyon', NULL, NULL, NULL, NULL, 130.00, 20, 3, 3, 'assets/img/products/shop_07.webp', '2025-11-24 13:24:17', '2025-11-27 06:46:44', 1),
(8, 'DYO İç Cephe Ekonomik 1L', 'Ekonomik fiyatla kaliteli iç cephe boyası', NULL, NULL, NULL, NULL, 90.00, 79, 4, 1, 'assets/img/products/shop_08.webp', '2025-11-24 13:24:17', '2025-11-26 11:36:25', 1),
(9, 'DYO İç Cephe Ekonomik 2.5L', 'Ekonomik fiyatla kaliteli iç cephe boyası', NULL, NULL, NULL, NULL, 210.00, 70, 4, 1, 'assets/img/products/shop_09.webp', '2025-11-24 13:24:17', '2025-11-24 14:37:32', 1),
(10, 'Permolit Ahşap Boyası 1L', 'Ahşap yüzeyler için koruyucu boya', NULL, NULL, NULL, NULL, 180.00, 35, 5, 4, 'assets/img/products/shop_10.webp', '2025-11-24 13:24:17', '2025-11-24 14:37:32', 1),
(11, 'Permolit Metal Boyası 1L', 'Metal yüzeyler için paslanmaz boya', NULL, NULL, NULL, NULL, 200.00, 0, 5, 4, 'assets/img/products/shop_11.webp', '2025-11-24 13:24:17', '2025-11-27 14:55:12', 1),
(13, 'marshall ahsap için boya', 'deneme açıklama.comss', 'asdjksngbnrfdıujkgbnhfjuhgbnfnhgbjuıkfgbfkbjnfkgbn\r\nsoedgbıjnkhlfgjhıbnlkofjbnokljfmgıobjnmfpmnb', 'sağlam\r\nkokusuz\r\ngüzel görünüm\r\ndeneme\r\ntest', '100m2 5 lt\r\n200m2 10lt\r\nfsdpogjmpodjg\r\ndeneme\r\n', 'deneme, denemetest\r\ndenemeee\r\ndenemeee', 450.00, 10, 3, 4, 'assets/img/products/product_1764139572_6926a23493603.webp', '2025-11-26 06:46:12', '2025-11-27 14:47:57', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 5, 'Çok kaliteli bir boya, rengi çok canlı ve kolay uygulandı. Kesinlikle tavsiye ederim.', '2025-11-26 13:33:10', '2025-11-26 13:33:10'),
(2, 1, 4, 4, 'Fiyatına göre oldukça iyi, kokusu çok ağır değil. Bir dahaki sefere de alırım.', '2025-11-26 13:33:10', '2025-11-26 13:33:10'),
(3, 2, 5, 5, 'Mükemmel örtücülük, tek kat yeterli oldu. Evim çok güzel oldu.', '2025-11-26 13:33:10', '2025-11-26 13:33:10'),
(4, 3, 2, 3, 'Dış cephe için iyi ama biraz daha fazla koruma bekliyordum.', '2025-11-26 13:33:10', '2025-11-26 13:33:10'),
(5, 1, 6, 5, 'deneme şeklisi yorum', '2025-11-26 13:39:17', '2025-11-26 13:39:17'),
(6, 3, 6, 5, 'deneme yorum deneme123', '2025-11-26 13:40:26', '2025-11-26 13:40:26'),
(7, 2, 6, 5, 'test yorum', '2025-11-26 13:47:53', '2025-11-26 13:47:53'),
(8, 5, 6, 5, 'deniyoruz bakalım test', '2025-11-26 13:53:54', '2025-11-26 13:53:54'),
(9, 2, 4, 5, 'deneme test yorummmmm', '2025-11-27 11:18:22', '2025-11-27 11:18:22'),
(10, 13, 11, 5, 'Gerçekten ama gerçekten ama gerçekten harikulade bir ürün gerçekten ama gercekten çok iyi bir boya.', '2025-11-27 14:00:58', '2025-11-27 14:00:58'),
(12, 1, 11, 5, 'Hayırlı İşler !', '2025-11-27 14:41:18', '2025-11-27 14:41:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `address`, `PASSWORD`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@kafkasboya.com', 'rfwesgvfserhgbh', '$2a$12$dFr4/AEaazK/uJe4MpsbPefKP9.jClzB1VAtufrN7EsvOROs3oV8i', 'admin', '2025-11-24 13:24:17', '2025-11-26 07:13:16'),
(2, 'testuser', 'test@example.com', 'jntfjtghmgtymjkgyu', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'user', '2025-11-24 13:24:17', '2025-11-26 07:13:37'),
(4, 'ustaniz_geldi', 'usta@gmail.com', 'nrtgrfdcjhnytghjntfhntfy', '$2y$10$Y4QHN2H3zBtkKK1.3UvgB.GkBgl9VY7diqtDmXfEGtOfc0GD9lEGq', 'user', '2025-11-25 10:33:30', '2025-11-26 08:31:49'),
(5, 'denemeisim', 'deneme@gmail.com', 'deneme adres deneme sokak bilmem no142', '$2y$10$O9iceoHQy6HTp.RgOwL0ue0G7PKXWUpc9yR4lUQdJZuCzxLabCe5m', 'user', '2025-11-26 08:36:10', '2025-11-26 08:36:10'),
(6, 'seladeneme', 'denemee@gmail.com', 'denemeadressssss', '$2y$10$WFkNGyihIWOooBIVodkNN.pa7aMN6l2qB9sODEkY4WkcfqXYpWlfm', 'user', '2025-11-26 11:34:19', '2025-11-26 11:34:19'),
(7, 'deniyorum', 'deniyorum@gmail.com', 'denem adressssssss', '$2y$10$CmZ9XJslC/NXMJvfUGJiFO75h8unbFn6BI.2iTvy6eeTFZZRuK0CK', 'user', '2025-11-27 12:58:10', '2025-11-27 12:58:10'),
(8, 'asdasd', 'asdasd@gmail.com', 'asdasd adressssss', '$2y$10$YD6yVjimBs7fRKbEoN0uUOAkLAAvb6nLFO9oNu2B.KJw7zDXl7d6m', 'user', '2025-11-27 13:07:11', '2025-11-27 13:07:11'),
(9, 'deneme2', 'deneme2@gmail.com', 'denem2eadresssss', '$2y$10$KuNvcpWMR/plqPdFx5NP0uq0nmSJfvywabbW5DPjC9fG1Pxjdq9ci', 'user', '2025-11-27 13:23:15', '2025-11-27 13:23:15'),
(10, 'ustanizgeldicom', 'ustanizgeldi@gmail.com', 'bursa yıldırım', '$2y$10$AXvCUer5Z95ZLgVhkuywLO67TIJeXdCwiSYckGqCTvmeUoR9v5IOO', 'admin', '2025-11-27 13:42:35', '2025-11-27 13:46:32'),
(11, 'Cemil', 'cemil@gmail.com', 'Semti haciWat', '$2y$10$9IIq9QfumEO3838MjWJFieL/oYlDykcspAP9j45ojC3uSJ8s/LXLS', 'user', '2025-11-27 13:56:09', '2025-11-27 13:57:36');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `NAME` (`name`);

--
-- Tablo için indeksler `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `NAME` (`name`);

--
-- Tablo için indeksler `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_contacts_user_id` (`user_id`);

--
-- Tablo için indeksler `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_brand` (`brand_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contacts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD CONSTRAINT `contact_replies_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
