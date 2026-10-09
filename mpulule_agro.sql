-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 07:58 PM
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
-- Database: `mpulule_agro`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(60) DEFAULT 'fa-leaf',
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `sort_order`) VALUES
(1, 'Crops & Seeds', 'crops', 'fa-seedling', 'Certified hybrid and open-pollinated seeds for all major crops', 1),
(2, 'Nitrogenous Fertilizer', 'nitrogen', 'fa-flask', 'Urea, CAN and other nitrogen-based fertilizers for healthy leaf growth', 2),
(3, 'Phosphorus Fertilizer', 'phosphorus', 'fa-atom', 'DAP, SSP and TSP for strong root development and early growth', 3),
(4, 'Potassium Fertilizer', 'potassium', 'fa-vial', 'MOP, SOP and other potassium fertilizers for fruit and grain quality', 4),
(5, 'Farm Equipment', 'equipment', 'fa-tractor', 'Tractors, pumps, sprayers and post-harvest machinery', 5);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_subjects`
--

CREATE TABLE `contact_subjects` (
  `id` int(11) NOT NULL,
  `label` varchar(200) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_subjects`
--

INSERT INTO `contact_subjects` (`id`, `label`, `sort_order`) VALUES
(1, 'Product inquiry', 1),
(2, 'Order support', 2),
(3, 'Loan application', 3),
(4, 'Delivery inquiry', 4),
(5, 'Partnership', 5),
(6, 'Other', 6);

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `loan_ref` varchar(24) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `applicant_name` varchar(180) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `farm_size_acres` decimal(8,2) NOT NULL,
  `primary_crop` varchar(100) NOT NULL,
  `annual_prod_kg` decimal(10,2) DEFAULT NULL,
  `loan_purpose` varchar(255) NOT NULL,
  `repay_months` int(11) NOT NULL DEFAULT 12,
  `requested_amount` decimal(14,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `monthly_payment` decimal(14,2) NOT NULL,
  `status` enum('pending','approved','rejected','disbursed') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_crops`
--

CREATE TABLE `loan_crops` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_crops`
--

INSERT INTO `loan_crops` (`id`, `name`, `sort_order`) VALUES
(1, 'Maize', 1),
(2, 'Rice', 2),
(3, 'Cassava', 3),
(4, 'Sunflower', 4),
(5, 'Tobacco', 5),
(6, 'Coffee', 6),
(7, 'Sugarcane', 7),
(8, 'Beans', 8),
(9, 'Soybeans', 9),
(10, 'Sweet Potato', 10),
(11, 'Vegetables', 11),
(12, 'Other', 12);

-- --------------------------------------------------------

--
-- Table structure for table `loan_purposes`
--

CREATE TABLE `loan_purposes` (
  `id` int(11) NOT NULL,
  `label` varchar(200) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_purposes`
--

INSERT INTO `loan_purposes` (`id`, `label`, `sort_order`) VALUES
(1, 'Buy seeds & inputs', 1),
(2, 'Purchase equipment', 2),
(3, 'Land preparation & ploughing', 3),
(4, 'Irrigation setup', 4),
(5, 'Post-harvest storage', 5),
(6, 'Agro-processing machinery', 6),
(7, 'Other', 7);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `type` enum('order','loan') NOT NULL,
  `ref_id` int(11) NOT NULL COMMENT 'order or loan application id',
  `ref_code` varchar(24) NOT NULL COMMENT 'order_ref or loan_ref',
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `channel` enum('email','sms','both') DEFAULT 'both',
  `sent_email` tinyint(1) DEFAULT 0,
  `sent_sms` tinyint(1) DEFAULT 0,
  `whatsapp_link` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `email`, `phone`, `type`, `ref_id`, `ref_code`, `old_status`, `new_status`, `message`, `subject`, `body`, `channel`, `sent_email`, `sent_sms`, `whatsapp_link`, `is_read`, `created_at`, `sent_at`) VALUES
(4, 7, 'angelina@gmail.com', '0746905955', 'order', 0, '', NULL, '', '', 'Order ORD-8B36F656 — Status: Processing', '\n        <p>Dear <strong>Angelina mpulule</strong>,</p>\n        <p>Your order <strong style=\'color:#c9a84c\'>ORD-8B36F656</strong> has been updated.</p>\n        <div style=\'text-align:center;margin:24px 0\'>\n            <span style=\'display:inline-block;padding:10px 28px;background:#eff6ff;color:#1e40af;border-radius:20px;font-size:18px;font-weight:800;border:1px solid #1e40af\'>PROCESSING</span>\n        </div>\n        <table style=\'width:100%;border-collapse:collapse;font-size:14px\'>\n            <tr><td style=\'padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0\'>Order ref</td><td style=\'padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0\'>ORD-8B36F656</td></tr>\n            <tr><td style=\'padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0\'>Total</td><td style=\'padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0\'>TSh 200,000</td></tr>\n            <tr><td style=\'padding:8px 0;color:#7a6840\'>Status</td><td style=\'padding:8px 0;font-weight:600;color:#1e40af\'>Processing</td></tr>\n        </table>\n        \n        <p style=\'margin-top:20px;font-size:14px;color:#7a6840\'>Questions? Reply to this email or WhatsApp: <strong>+255746905955</strong>.</p>\n        <p style=\'font-size:13px;color:#9ca3af\'>— The Mpulule AgroMarket team</p>\n    ', 'both', 0, 0, 'https://wa.me/0746905955?text=Mpulule+AgroMarket%3A+Order+ORD-8B36F656+%E2%80%94+Status%3A+Processing%0A%0ADear+Angelina+mpulule%2C+Your+order+ORD-8B36F656+has+been+updated.+PROCESSING+Order+refORD-8B36F656+TotalTSh+200%2C000+StatusProcessing+Questions%3F+Reply+to+this+email+or+WhatsApp%3A+%2B255746905955.+%E2%80%94+The+Mpulule+AgroMarket+team', 0, '2026-04-26 19:28:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `type` enum('order','loan') NOT NULL,
  `status` varchar(30) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sms_body` varchar(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `type`, `status`, `subject`, `body`, `sms_body`) VALUES
(1, 'order', 'approved', 'Your Order {ref} Has Been Approved ✓', 'Dear {name},\n\nGreat news! Your order {ref} has been APPROVED.\n\nOrder total: {amount}\nItems: {items}\n\nOur team will now process your order and contact you to arrange delivery to {region}.\n\nIf you have any questions, reply to this email or WhatsApp us at {whatsapp}.\n\nThank you for choosing {site_name}!\n\nBest regards,\n{site_name} Team\n{email}', 'Hi {name}, your order {ref} ({amount}) is APPROVED. We will contact you for delivery. - {site_name}'),
(2, 'order', 'rejected', 'Update on Your Order {ref}', 'Dear {name},\n\nWe regret to inform you that your order {ref} could not be processed at this time.\n\nReason: {admin_note}\n\nPlease contact us for more information:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nWe apologise for any inconvenience.\n\nBest regards,\n{site_name} Team', 'Hi {name}, your order {ref} was not approved. Reason: {admin_note}. Contact us: {whatsapp} - {site_name}'),
(3, 'order', 'processing', 'Your Order {ref} Is Being Processed', 'Dear {name},\n\nYour order {ref} is now being PROCESSED and prepared for dispatch.\n\nOrder total: {amount}\n\nYou will receive another notification once your order is shipped.\n\nThank you,\n{site_name} Team', 'Hi {name}, order {ref} is now PROCESSING and being prepared. Watch for shipping update. - {site_name}'),
(4, 'order', 'shipped', 'Your Order {ref} Has Been Shipped! 🚚', 'Dear {name},\n\nExcellent news! Your order {ref} has been SHIPPED and is on its way to {region}.\n\nOrder total: {amount}\n\nPlease ensure someone is available to receive the delivery. Contact us if needed:\nWhatsApp: {whatsapp}\n\nThank you,\n{site_name} Team', 'Hi {name}, order {ref} has been SHIPPED to {region}. Contact {whatsapp} for updates. - {site_name}'),
(5, 'order', 'delivered', 'Your Order {ref} Has Been Delivered ✓', 'Dear {name},\n\nYour order {ref} has been marked as DELIVERED.\n\nWe hope you are satisfied with your purchase. Please contact us if you have any concerns:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nThank you for your business!\n\n{site_name} Team', 'Hi {name}, order {ref} marked DELIVERED. Questions? Contact {whatsapp}. Thank you! - {site_name}'),
(6, 'loan', 'approved', 'Your Loan Application {ref} Has Been Approved! 🎉', 'Dear {name},\n\nCongratulations! Your agricultural loan application {ref} has been APPROVED.\n\nLoan details:\n- Amount approved: {amount}\n- Repayment period: {months} months\n- Monthly payment: {monthly}\n- Interest rate: {rate}% p.a.\n\nOur team will contact you at {phone} within 48 hours to complete the disbursement process.\n\nRequired at the office:\n- National ID or Passport\n- Farm ownership/lease documents\n\nContact us:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nCongratulations again!\n\n{site_name} Team', 'Congratulations {name}! Loan {ref} APPROVED: {amount} over {months} months. We will call {phone} within 48hrs. - {site_name}'),
(7, 'loan', 'rejected', 'Update on Your Loan Application {ref}', 'Dear {name},\n\nThank you for applying for an agricultural loan with {site_name}.\n\nUnfortunately, your loan application {ref} for {amount} was not approved at this time.\n\nReason: {admin_note}\n\nYou are welcome to apply again in the future or contact us to discuss alternative options:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nWe appreciate your interest in {site_name}.\n\nBest regards,\n{site_name} Team', 'Hi {name}, loan application {ref} was not approved. Reason: {admin_note}. Contact {whatsapp} for help. - {site_name}'),
(8, 'loan', 'disbursed', 'Your Loan {ref} Has Been Disbursed! 💰', 'Dear {name},\n\nYour loan {ref} of {amount} has been DISBURSED to your account.\n\nRepayment starts next month. Monthly installment: {monthly}\n\nPlease ensure timely repayment to maintain your credit record.\n\nContact us if you need assistance:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nThank you,\n{site_name} Team', 'Hi {name}, loan {ref} of {amount} has been DISBURSED. Monthly payment: {monthly}. - {site_name}');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_ref` varchar(24) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(180) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `region` varchar(100) NOT NULL,
  `delivery_notes` text DEFAULT NULL,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `status` enum('pending','approved','processing','shipped','delivered','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_ref`, `user_id`, `customer_name`, `email`, `phone`, `region`, `delivery_notes`, `total_amount`, `status`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 'ORD-1F167B64', 7, 'Angelina mpulule', 'angelina@gmail.com', '0623234543', 'irnga', '', 200000.00, 'delivered', '', '2026-04-26 18:15:45', '2026-04-26 18:37:22'),
(2, 'ORD-8B36F656', 7, 'Angelina mpulule', 'angelina@gmail.com', '0746905955', 'Dodoma', '', 200000.00, 'processing', '', '2026-04-26 19:27:04', '2026-04-26 19:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `unit_price`) VALUES
(1, 1, 23, 1, 200000.00),
(2, 2, 23, 1, 200000.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_statuses`
--

CREATE TABLE `order_statuses` (
  `slug` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `color` varchar(30) DEFAULT 'badge-gold'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_statuses`
--

INSERT INTO `order_statuses` (`slug`, `label`, `color`) VALUES
('approved', 'Approved', 'badge-success'),
('delivered', 'Delivered', 'badge-success'),
('pending', 'Pending', 'badge-warn'),
('processing', 'Processing', 'badge-info'),
('rejected', 'Rejected', 'badge-danger'),
('shipped', 'Shipped', 'badge-info');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `unit` varchar(60) NOT NULL DEFAULT 'kg',
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `unit`, `stock_qty`, `image`, `featured`, `active`, `created_at`) VALUES
(23, 3, 'mbolea saf sana', 'nlnklml;;l', 200000.00, 'kg', 32, 'prod_1777212988_601.jpg', 0, 1, '2026-04-26 17:16:28'),
(24, 3, 'mbolea saf sana', 'nlnklml;;l', 200000.00, 'kg', 34, 'prod_1777213110_851.jpg', 0, 0, '2026-04-26 17:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(120) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`) VALUES
('contact_address', 'Tanzania — Serving all regions nationwide'),
('contact_email', 'mpululefransisco@gmail.com'),
('currency', 'TSh'),
('hero_subtitle', 'Browse quality crops, fertilizers & equipment. Order from trusted suppliers. Apply for agricultural loans.'),
('hero_title', 'Tanzania\'s #1 Agricultural Marketplace'),
('loan_max_large', '10000000'),
('loan_max_medium', '2000000'),
('loan_max_small', '500000'),
('loan_rate_large', '8'),
('loan_rate_medium', '10'),
('loan_rate_small', '12'),
('loan_tier_large_label', 'Large farm (10+ acres)'),
('loan_tier_medium_label', 'Medium farm (2–10 acres)'),
('loan_tier_small_label', 'Small farm (0.5–2 acres)'),
('required_docs', 'National ID or passport|Farm ownership or lease proof|Previous season harvest records|2 guarantors (local leaders accepted)'),
('site_name', 'Mpulule AgroMarket'),
('site_tagline', 'Tanzania\'s premier agricultural marketplace connecting farmers and agribusinesses with quality crops, fertilizers, equipment, and financing.'),
('whatsapp_number', '+255746905955'),
('why_section_subtitle', 'Trusted by farmers across Tanzania'),
('why_section_title', 'Why choose Mpulule AgroMarket?'),
('why1_text', 'All products are certified and tested before listing on our platform.'),
('why1_title', 'Quality assured'),
('why2_text', 'We deliver to all regions across Tanzania, fast and reliably.'),
('why2_title', 'Nationwide delivery'),
('why3_text', 'Flexible agricultural loans tailored to your farm size and production.'),
('why3_title', 'Agri financing'),
('why4_text', 'Reach us via email or WhatsApp, six days a week, 7AM–6PM.'),
('why4_title', 'Expert support'),
('working_hours', 'Monday – Saturday, 7:00 AM – 6:00 PM EAT');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(80) NOT NULL,
  `lastname` varchar(80) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `phone`, `region`, `password`, `user_role`, `created_at`) VALUES
(5, 'fransisco', 'mpulule', 'mpululefransisco@gmail.com', '0746905955', 'iringa', '$2y$10$r48Vx0C4VWXK5SoZ9UlE/..37A6tI57W9RAF21HZUyqg7v3SgM3H.', 'admin', '2026-04-26 16:29:02'),
(7, 'Angelina', 'mpulule', 'angelina@gmail.com', '0623234543', 'irnga', '$2y$10$vlaVoudXM3kLF36gVvSZTeZ.A8a8N93O2jkenD9tQDG3KjjX6JUDC', 'user', '2026-04-26 18:15:17'),
(8, 'peter', 'kwangu', 'kwangupeter@gmail.com', '0768908789', 'bukoba', '$2y$10$z1nQJIUXh3SPW4RlyTYk/eX1yHG6A6ZvzKi4ElQLG9s1Vda2lD2Re', 'user', '2026-04-26 19:35:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_subjects`
--
ALTER TABLE `contact_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_ref` (`loan_ref`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `loan_crops`
--
ALTER TABLE `loan_crops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_purposes`
--
ALTER TABLE `loan_purposes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_type_status` (`type`,`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_ref` (`order_ref`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_statuses`
--
ALTER TABLE `order_statuses`
  ADD PRIMARY KEY (`slug`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_subjects`
--
ALTER TABLE `contact_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_crops`
--
ALTER TABLE `loan_crops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `loan_purposes`
--
ALTER TABLE `loan_purposes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
