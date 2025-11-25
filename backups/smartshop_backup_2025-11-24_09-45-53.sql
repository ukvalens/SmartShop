-- SmartSHOP Database Backup
-- Created: 2025-11-24 09:45:53
-- Database: smartshop

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` VALUES ('1', 'Food & Beverages', 'Food items and drinks', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `categories` VALUES ('2', 'Personal Care', 'Soap, shampoo, toothpaste', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `categories` VALUES ('3', 'Household Items', 'Cleaning supplies, kitchen items', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `categories` VALUES ('4', 'Electronics', 'Phones, accessories, batteries', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `categories` VALUES ('5', 'Stationery', 'Pens, books, paper', '2025-11-13 16:09:29', '2025-11-13 16:09:29');

DROP TABLE IF EXISTS `customer_credits`;
CREATE TABLE `customer_credits` (
  `credit_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`credit_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_credits_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `loyalty_points`;
CREATE TABLE `loyalty_points` (
  `loyalty_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `points_redeemed` int(11) DEFAULT 0,
  `transaction_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`loyalty_id`),
  KEY `customer_id` (`customer_id`),
  KEY `transaction_id` (`transaction_id`),
  CONSTRAINT `loyalty_points_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_points_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `sales` (`sale_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Low Stock','Credit Reminder','Expiry Alert','Promotion') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('User','Customer') NOT NULL,
  `message_content` text NOT NULL,
  `status` enum('Sent','Pending','Failed') DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Mobile Money') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `sale_id` (`sale_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE SET NULL,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(150) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES ('1', 'Sugar 1kg', '1', '800.00', '1000.00', '0', '10', NULL, 'SG001', '2025-11-13 16:09:29', '2025-11-14 07:21:11');
INSERT INTO `products` VALUES ('2', 'Rice 1kg', '1', '1200.00', '1500.00', '0', '5', NULL, 'RC001', '2025-11-13 16:09:29', '2025-11-14 07:20:43');
INSERT INTO `products` VALUES ('3', 'Soap Bar', '2', '300.00', '400.00', '0', '20', NULL, 'SP001', '2025-11-13 16:09:29', '2025-11-14 07:20:20');
INSERT INTO `products` VALUES ('4', 'Cooking Oil 1L', '1', '2000.00', '2500.00', '0', '5', NULL, 'OL001', '2025-11-13 16:09:29', '2025-11-14 12:46:04');
INSERT INTO `products` VALUES ('5', 'Toothpaste', '2', '800.00', '1200.00', '0', '10', NULL, 'TP001', '2025-11-13 16:09:29', '2025-11-13 22:56:55');
INSERT INTO `products` VALUES ('6', 'Cooking Oil ', '1', '400.00', '800.00', '-6', '6', NULL, 'OL001', '2025-11-13 22:51:31', '2025-11-14 07:25:06');
INSERT INTO `products` VALUES ('7', 'Cooking Oil 1L', '1', '3000.00', '3500.00', '0', '1', NULL, '', '2025-11-14 13:06:14', '2025-11-15 13:51:20');
INSERT INTO `products` VALUES ('8', 'potatoes', '1', '500.00', '600.00', '3', '1', NULL, '', '2025-11-15 17:37:39', '2025-11-15 20:19:18');
INSERT INTO `products` VALUES ('9', 'potatoes', '1', '500.00', '600.00', '-38', '1', NULL, '', '2025-11-15 17:40:33', '2025-11-15 17:43:57');
INSERT INTO `products` VALUES ('10', 'potatoes', '1', '500.00', '600.00', '0', '10', NULL, '', '2025-11-15 17:41:24', '2025-11-15 17:42:23');
INSERT INTO `products` VALUES ('11', 'Cooking Oil 1L', '3', '3000.00', '3500.00', '0', '10', NULL, 'OL001', '2025-11-15 18:09:44', '2025-11-15 18:10:42');

DROP TABLE IF EXISTS `purchase_order_details`;
CREATE TABLE `purchase_order_details` (
  `purchase_order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`purchase_order_detail_id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_order_details_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_order_id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchase_order_details` VALUES ('1', '1', '5', '3', '0.37', '3', '2025-11-13 17:31:01', '2025-11-13 17:34:10');
INSERT INTO `purchase_order_details` VALUES ('2', '1', '4', '2', '0.14', '2', '2025-11-13 17:31:01', '2025-11-13 17:34:10');
INSERT INTO `purchase_order_details` VALUES ('3', '1', '4', '4', '333.00', '4', '2025-11-13 17:31:01', '2025-11-13 17:34:10');
INSERT INTO `purchase_order_details` VALUES ('4', '1', '3', '6', '0.06', '6', '2025-11-13 17:31:02', '2025-11-13 17:34:10');
INSERT INTO `purchase_order_details` VALUES ('5', '1', '4', '5', '0.07', '5', '2025-11-13 17:31:02', '2025-11-13 17:34:10');
INSERT INTO `purchase_order_details` VALUES ('6', '2', '1', '40', '500.00', '40', '2025-11-13 17:41:06', '2025-11-13 17:41:47');
INSERT INTO `purchase_order_details` VALUES ('7', '2', '4', '22', '500.00', '22', '2025-11-13 17:41:06', '2025-11-13 17:41:47');

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Delivered','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`purchase_order_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchase_orders` VALUES ('1', '3', '2025-11-13', '1334.10', 'Delivered', '2025-11-13 17:31:01', '2025-11-13 17:34:10');
INSERT INTO `purchase_orders` VALUES ('2', '3', '2025-11-13', '31000.00', 'Delivered', '2025-11-13 17:41:06', '2025-11-13 17:41:47');

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` enum('Daily','Weekly','Monthly') NOT NULL,
  `total_sales` decimal(12,2) NOT NULL,
  `total_profit` decimal(12,2) NOT NULL,
  `top_selling_product_id` int(11) DEFAULT NULL,
  `frequent_customer_id` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `top_selling_product_id` (`top_selling_product_id`),
  KEY `frequent_customer_id` (`frequent_customer_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`top_selling_product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`frequent_customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `returns`;
CREATE TABLE `returns` (
  `return_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `status` enum('processed','approved','rejected') DEFAULT 'processed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`return_id`),
  KEY `sale_id` (`sale_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sale_details`;
CREATE TABLE `sale_details` (
  `sale_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sale_detail_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_details_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `sale_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_item_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Mobile Money','Credit') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('completed','returned','cancelled') DEFAULT 'completed',
  PRIMARY KEY (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `staff_schedules`;
CREATE TABLE `staff_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `shift_type` enum('morning','afternoon','evening','full_day') DEFAULT 'morning',
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `adjustment_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `adjustment_type` enum('Damaged','Expired','Manual Correction') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`adjustment_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(150) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `suppliers` VALUES ('1', 'Inyama Ltd', 'Jean Baptiste', '+250788333333', 'jean@inyama.rw', 'Kigali, Rwanda', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `suppliers` VALUES ('2', 'Ubwiyunge Co', 'Marie Claire', '+250788444444', 'marie@ubwiyunge.rw', 'Musanze, Rwanda', '2025-11-13 16:09:29', '2025-11-13 16:09:29');
INSERT INTO `suppliers` VALUES ('3', 'Amazi Fresh', 'Patrick Nkusi', '+250788555555', 'patrick@amazi.rw', 'Huye, Rwanda', '2025-11-13 16:09:29', '2025-11-13 16:09:29');

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_settings` VALUES ('1', 'language', 'English', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('2', 'theme', 'Light', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('3', 'VAT_rate', '18', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('4', 'currency', 'RWF', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('5', 'low_stock_threshold', '10', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('6', 'sms_enabled', '1', '2025-11-13 16:09:29');
INSERT INTO `system_settings` VALUES ('7', 'backup_frequency', 'daily', '2025-11-13 16:09:29');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Owner','Cashier','Customer') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `language_preference` varchar(5) DEFAULT 'en',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('9', 'NIYIGABA', 'niyigabatheo10@gmail.com', '0780468216', '$2y$10$VbRCHJM7Z59eVHiKJxSFUOMQcy6UKCPfRZl27.tmOpO3Cmf9bokoq', 'Admin', 'active', 'en', NULL, NULL, '2025-11-15 16:56:01', '2025-11-15 16:56:01');

SET FOREIGN_KEY_CHECKS=1;
