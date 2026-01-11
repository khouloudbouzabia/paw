-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3307
-- Généré le : dim. 11 jan. 2026 à 15:47
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `coffee_shop`
--

-- --------------------------------------------------------

--
-- Structure de la table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'المشروبات', 'beverages', 'جميع أنواع المشروبات', NULL, 1, 1, '2026-01-07 11:09:06'),
(2, 'القهوة', 'coffee', 'أنواع القهوة المختلفة', NULL, 2, 1, '2026-01-07 11:09:06'),
(3, 'الشاي', 'tea', 'أنواع الشاي المختلفة', NULL, 3, 1, '2026-01-07 11:09:06'),
(4, 'المعجنات', 'pastries', 'المعجنات الطازجة', NULL, 4, 1, '2026-01-07 11:09:06'),
(5, 'السندويشات', 'sandwiches', 'سندويشات متنوعة', NULL, 5, 1, '2026-01-07 11:09:06'),
(6, 'الحلويات', 'desserts', 'أشهى الحلويات', NULL, 6, 1, '2026-01-07 11:09:06');

-- --------------------------------------------------------

--
-- Structure de la table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'عبدالله سالم', 'abdullah@email.com', 'استفسار عن مواعيد العمل', 'هل تفتحون يوم الجمعة؟', 'replied', '2026-01-07 11:09:07'),
(2, 'ريم أحمد', 'reem@email.com', 'شكوى', 'الخدمة كانت بطيئة في آخر زيارة', 'read', '2026-01-07 11:09:07'),
(3, 'نواف محمد', 'nawaf@email.com', 'مقترح', 'أقترح إضافة قهوة مثلجة إلى القائمة', 'new', '2026-01-07 11:09:07'),
(4, 'nada bou', 'nada@gmail.com', 'تقييم', 'ممتاز جدا', 'new', '2026-01-07 12:00:40'),
(5, 'nada bou', 'nada@gmail.com', 'تقييم', 'ممتاز جدا', 'new', '2026-01-07 12:00:42'),
(6, 'nada bou', 'nada@gmail.com', 'dfghjqsdrftyuk', 'zertyuikjhgfds zegt', 'new', '2026-01-07 12:13:00'),
(7, 'nada bou', 'nada@gmail.com', 'dfghjqsdrftyuk', 'sdfghjkjhg', 'new', '2026-01-07 12:16:18'),
(8, 'nada bou', 'nada@gmail.com', 'dfghjqsdrftyuk', 'sdfghjkjhg', 'new', '2026-01-07 12:16:21'),
(9, 'nada bou', '2222@gmil.com', 'dfghjqsdrftyuk', 'fgyuiolkjhg', 'new', '2026-01-07 12:55:54'),
(10, 'hhhh', 'hhhh@gmail.com', 'تقييم', 'rtyuiop', 'new', '2026-01-07 13:11:26'),
(11, 'ppp', 'ppp@gmail.com', 'okg', 'yuiop^$^piu', 'new', '2026-01-07 13:16:12');

-- --------------------------------------------------------

--
-- Structure de la table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `total_visits` int(11) DEFAULT 0,
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `total_visits`, `loyalty_points`, `created_at`, `birth_date`, `address`, `notes`) VALUES
(1, 'أحمد محمد', 'ahmed@email.com', '0551234567', 5, 151, '2026-01-07 11:09:07', NULL, NULL, NULL),
(2, 'سارة عبدالله', 'sara@email.com', '0567890123', 3, 83, '2026-01-07 11:09:07', NULL, NULL, NULL),
(3, 'خالد سعيد', 'khaled@email.com', '0543210987', 8, 202, '2026-01-07 11:09:07', NULL, NULL, NULL),
(4, 'فاطمة علي', 'fatima@email.com', '0534567890', 2, 50, '2026-01-07 11:09:07', NULL, NULL, NULL),
(6, 'nada bou', '2222@gmil.com', '0551234566', 0, 60, '2026-01-09 22:26:24', '2004-07-01', 'بغعهخهعلب', ''),
(7, 'hhhh', 'SLLKDD@GMAIL.COM', '0578485622', 0, 0, '2026-01-09 22:31:37', '1969-04-02', 'نتالب', 'فغعم'),
(8, 'ppp', 'gfdertyuertyu@gmail.com', '0605885285', 0, 12, '2026-01-09 22:32:48', '2003-11-04', 'لفغعهخح', '');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('schedule','other') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','preparing','ready','served','completed','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `reservation_id`, `total_amount`, `order_status`, `payment_method`, `payment_status`, `created_at`, `staff_id`) VALUES
(1, 'ORD001', 1, 1, 45.00, 'completed', 'cash', 'paid', '2026-01-07 11:09:07', NULL),
(2, 'ORD002', 2, 2, 30.00, 'served', 'cash', 'paid', '2026-01-07 11:09:07', NULL),
(3, 'ORD003', 3, NULL, 68.00, 'preparing', 'cash', 'unpaid', '2026-01-07 11:09:07', NULL),
(4, 'ORD004', NULL, NULL, 24.00, 'completed', 'cash', 'paid', '2026-01-07 11:09:07', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 1, 'إسبريسو', 2, 12.00, 24.00, '2026-01-07 11:09:07'),
(2, 1, 5, 'ساندويش دجاج', 1, 22.00, 22.00, '2026-01-07 11:09:07'),
(3, 2, 2, 'كابتشينو', 2, 15.00, 30.00, '2026-01-07 11:09:07'),
(4, 3, 5, 'ساندويش دجاج', 2, 22.00, 44.00, '2026-01-07 11:09:07'),
(5, 3, 4, 'كرواسان', 3, 8.00, 24.00, '2026-01-07 11:09:07'),
(6, 4, 1, 'إسبريسو', 2, 12.00, 24.00, '2026-01-07 11:09:07');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `category`, `image`, `available`, `created_at`, `updated_at`) VALUES
(1, 2, 'إسبريسو', 'قهوة إيطالية مركزة COFFE', 12.00, 'coffee', NULL, 0, '2026-01-07 11:09:06', '2026-01-09 19:11:15'),
(2, 2, 'كابتشينو', 'مزيج من الإسبريسو والحليب', 15.00, 'coffee', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34'),
(3, 3, 'شاي أخضر', 'شاي أخضر طبيعي', 10.00, 'tea', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34'),
(4, 4, 'كرواسان', 'معجنات فرنسية هشة', 8.00, 'pastry', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34'),
(5, 5, 'ساندويش دجاج', 'دجاج مشوي مع خضار', 22.00, 'sandwich', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34'),
(6, 6, 'تشيز كيك', 'تشيز كيك مع التوت', 18.00, 'dessert', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34'),
(7, 2, 'قهوة تركية', 'قهوة تركية ', 10.00, 'coffee', NULL, 1, '2026-01-07 11:09:06', '2026-01-09 17:45:37'),
(8, 3, 'شاي بالنعناع', 'شاي بالنعناع الطازج', 9.00, 'tea', NULL, 1, '2026-01-07 11:09:06', '2026-01-07 21:29:34');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_people` int(11) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `customer_id`, `customer_name`, `email`, `phone`, `reservation_date`, `reservation_time`, `number_of_people`, `table_number`, `special_requests`, `status`, `created_by`, `confirmed_by`, `cancelled_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'أحمد محمد', 'ahmed@email.com', '0551234567', '2026-01-08', '08:00:00', 4, 'T03', 'OUI', 'confirmed', 1, NULL, NULL, '2026-01-07 11:09:07', NULL),
(2, 2, 'سارة عبدالله', 'sara@email.com', '0567890123', '2026-01-09', '19:00:00', 2, 'T01', NULL, 'confirmed', 1, NULL, NULL, '2026-01-07 11:09:07', '2026-01-09 10:57:46'),
(5, NULL, 'khouloud bouzabia', 'bouzabia@gmail.com', '0552139400', '2026-01-09', '13:00:00', 2, NULL, 'عيد ميلاد', 'confirmed', NULL, NULL, NULL, '2026-01-07 15:22:25', '2026-01-09 10:57:38');

-- --------------------------------------------------------

--
-- Structure de la table `staff_schedules`
--

CREATE TABLE `staff_schedules` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `shift_type` enum('morning','evening','night') NOT NULL,
  `shift_start` time NOT NULL,
  `shift_end` time NOT NULL,
  `week_start` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL,
  `table_type` enum('indoor','outdoor','vip','family') DEFAULT 'indoor',
  `location` varchar(100) DEFAULT NULL,
  `status` enum('available','occupied','reserved','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `capacity`, `table_type`, `location`, `status`) VALUES
(1, 'T01', 2, 'indoor', 'بالقرب من النافذة', 'available'),
(2, 'T02', 2, 'indoor', 'وسط الصالة', 'available'),
(3, 'T03', 4, 'indoor', 'ركن هادئ', 'available'),
(4, 'T04', 4, 'outdoor', 'الحديقة', 'available'),
(5, 'T05', 6, 'indoor', 'وسط الصالة', 'available'),
(6, 'T06', 8, 'vip', 'الطابق العلوي', 'available');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `created_at`) VALUES
(1, 'admin', '2222', 'المدير العام', 'admin@coffee-shop.com', '0501234567', 'admin', '2026-01-07 11:09:06'),
(2, 'manager', '1111', 'مدير المطعم', 'manager@coffee-shop.com', '0507654321', '', '2026-01-07 11:09:06'),
(3, 'waiter1', '3333', 'نادر الشريف', 'waiter1@coffee-shop.com', '050111223', 'staff', '2026-01-07 11:09:06');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_customers_email` (`email`),
  ADD KEY `idx_customers_phone` (`phone`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_available` (`available`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_reservations_date` (`reservation_date`),
  ADD KEY `idx_reservations_status` (`status`),
  ADD KEY `idx_reservations_customer` (`customer_id`);

--
-- Index pour la table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Index pour la table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Index pour la table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_4` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD CONSTRAINT `staff_schedules_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD CONSTRAINT `staff_shifts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
