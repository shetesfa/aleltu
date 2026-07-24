-- Aleltu POS Backup
-- Generated: 2026-01-23 02:43:04 (Ethiopian Time)
-- Type: Auto Backup (Every 2 hours)
-- Database: sql200.infinityfree.com via TCP/IP

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `addstock`;
CREATE TABLE `addstock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'admin',
  `sent_by` varchar(100) DEFAULT NULL,
  `date_sent` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `addstock` VALUES('1','1','Seller John','Apples','50.00','kg','admin','Admin1','2026-01-15 10:23:57','approved','Fresh from farm','2026-01-15 10:47:04');
INSERT INTO `addstock` VALUES('2','1','Seller John','Carrots','30.00','kg','admin','Admin2','2026-01-15 10:23:57','approved','Organic carrots','2026-01-15 10:47:26');
INSERT INTO `addstock` VALUES('3','1','Seller John','Potatoes','100.00','kg','admin','Admin1','2026-01-15 10:23:57','approved','New harvest','2026-01-15 10:47:28');
INSERT INTO `addstock` VALUES('4','1','Seller John','Tomatoes','40.00','kg','admin','Admin3','2026-01-15 10:23:57','approved','Already approved','2026-01-13 10:23:57');
INSERT INTO `addstock` VALUES('5','1','Seller John','Onions','60.00','kg','admin','Admin1','2026-01-15 10:23:57','approved','Approved yesterday','2026-01-14 10:23:57');

DROP TABLE IF EXISTS `admin_inventory`;
CREATE TABLE `admin_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin_item` (`admin_id`,`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_stock_logs`;
CREATE TABLE `admin_stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `source` varchar(100) NOT NULL,
  `added_by` varchar(255) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `seller_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_date` (`admin_id`,`date_added`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_stock_receiving`;
CREATE TABLE `admin_stock_receiving` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(100) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `source` varchar(100) NOT NULL,
  `added_by` varchar(100) NOT NULL,
  `date_added` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date_added`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_stock_receiving` VALUES('1','4','aleltu','Coca Cola','1000.00','bundle','transfer','belete (Admin)','2026-01-15 09:02:47','');
INSERT INTO `admin_stock_receiving` VALUES('2','4','aleltu','Juice','10000.00','l','admin','belete (Admin)','2026-01-15 09:50:35','');
INSERT INTO `admin_stock_receiving` VALUES('3','4','aleltu','Tea','99999999.99','carton','supplier','belete (Admin)','2026-01-15 09:51:51','');
INSERT INTO `admin_stock_receiving` VALUES('4','4','aleltu','Water','99999999.99','sack','supplier','belete (Admin)','2026-01-15 09:51:51','');
INSERT INTO `admin_stock_receiving` VALUES('5','4','aleltu','Juice','10000.00','bundle','supplier','belete (Admin)','2026-01-15 09:51:51','');
INSERT INTO `admin_stock_receiving` VALUES('6','4','aleltu','አይብ','450.00','kg','admin','belete (Admin)','2026-01-17 13:12:19','');
INSERT INTO `admin_stock_receiving` VALUES('7','4','aleltu','ሌግ','4542.00','kg','farmer','belete (Admin)','2026-01-17 13:12:51','');
INSERT INTO `admin_stock_receiving` VALUES('8','4','aleltu','ሞዘሬላ','100.00','kg','admin','belete (Admin)','2026-01-17 13:25:09','');
INSERT INTO `admin_stock_receiving` VALUES('9','4','aleltu','ናይልቸርች','100.00','kg','supplier','belete (Admin)','2026-01-17 13:25:22','');
INSERT INTO `admin_stock_receiving` VALUES('10','4','aleltu','TESFA','100.00','kg','supplier','belete (Admin)','2026-01-21 15:53:23','');

DROP TABLE IF EXISTS `admin_transactions`;
CREATE TABLE `admin_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `daily_summaries`;
CREATE TABLE `daily_summaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_expenses` decimal(10,2) DEFAULT 0.00,
  `total_withdrawals` decimal(10,2) DEFAULT 0.00,
  `closing_balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`),
  KEY `idx_daily_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `daily_transactions`;
CREATE TABLE `daily_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `daily_id` int(11) DEFAULT NULL,
  `transaction_type` enum('withdrawal','expense') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `seller_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transactions_daily` (`daily_id`),
  KEY `idx_transactions_type` (`transaction_type`),
  CONSTRAINT `daily_transactions_ibfk_1` FOREIGN KEY (`daily_id`) REFERENCES `daily_summaries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `daily_withdrawals`;
CREATE TABLE `daily_withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `gregorian_date` date DEFAULT NULL,
  `ethiopian_date` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `ethiopian_time` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `daily_withdrawals` VALUES('1','4','Roza','5000.00','lekmkfdkvf',NULL,'2017/05(ጥር)/09','2026-01-17 15:52:31',NULL);
INSERT INTO `daily_withdrawals` VALUES('2','1','belete','500.00','lenedag',NULL,'2018/04(ታህሳስ)/21','2026-01-21 08:35:34','06:35:34 AM');
INSERT INTO `daily_withdrawals` VALUES('3','4','Roza','100.00','fuel',NULL,'2018/04(ታህሳስ)/21','2026-01-21 08:38:07','06:38:07 AM');
INSERT INTO `daily_withdrawals` VALUES('4','4','Roza','1000.00','fuel',NULL,'2018/04(ታህሳስ)/21','2026-01-21 12:01:29','10:01:29 AM');
INSERT INTO `daily_withdrawals` VALUES('5','3','tesfa','1000.00','nothing',NULL,'2018/04(ታህሳስ)/21','2026-01-21 12:53:04','10:53:04 AM');
INSERT INTO `daily_withdrawals` VALUES('6','1','belete','100.00','for sercive',NULL,'2018/04(ታህሳስ)/21','2026-01-21 12:53:23','10:53:23 AM');
INSERT INTO `daily_withdrawals` VALUES('7','1','belete','1000.00','bet rent',NULL,'2018/04(ታህሳስ)/21','2026-01-21 15:57:20','01:57:20 PM');

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 10,
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory_items` VALUES('1','Coca Cola 500ml','CC-500','Beverages','100','20','30.00','Main Warehouse','2026-01-09 22:20:19');
INSERT INTO `inventory_items` VALUES('2','Coffee Cup','COFF-001','Hot Drinks','50','10','25.00','Main Warehouse','2026-01-09 22:20:19');
INSERT INTO `inventory_items` VALUES('3','Bread','BRD-001','Food','30','5','10.00','Main Warehouse','2026-01-09 22:20:19');
INSERT INTO `inventory_items` VALUES('4','Juice 250ml','JCE-250','Beverages','80','15','35.00','Main Warehouse','2026-01-09 22:20:19');

DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` enum('send','receive','adjust','transfer') NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `from_location` varchar(100) DEFAULT NULL,
  `to_location` varchar(100) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','sent','received','cancelled') DEFAULT 'pending',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `item_edit_history`;
CREATE TABLE `item_edit_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `old_name` varchar(100) DEFAULT NULL,
  `new_name` varchar(100) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `new_price` decimal(10,2) DEFAULT NULL,
  `edited_by` varchar(100) DEFAULT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `item_edit_history` VALUES('13','42','30000','እርጎ','100.00','65.00','belete','2026-01-17 02:07:08');
INSERT INTO `item_edit_history` VALUES('14','38','444445','አንባዛ','2223.00','450.00','belete','2026-01-17 02:07:32');
INSERT INTO `item_edit_history` VALUES('15','37','bread 122223','ፍሌቶ','122.00','490.00','belete','2026-01-17 02:07:50');
INSERT INTO `item_edit_history` VALUES('16','19','Bread12','ናይልቸርች','100.00','1400.00','belete','2026-01-17 02:08:12');
INSERT INTO `item_edit_history` VALUES('17','22','Coca Cola','የሀበሻ ዶሮ','30.00','650.00','belete','2026-01-17 02:08:30');
INSERT INTO `item_edit_history` VALUES('18','30','Coca Cola','የፈረንጅ ዶሮ','30.00','1000.00','belete','2026-01-17 02:08:51');
INSERT INTO `item_edit_history` VALUES('20','1','Coffee','የምግብ ቅቤ','1000.00','930.00','belete','2026-01-17 02:12:12');
INSERT INTO `item_edit_history` VALUES('21','20','Egg','Egg','5.00','10.00','belete','2026-01-17 02:12:40');
INSERT INTO `item_edit_history` VALUES('22','15','Juice','Juice','35.00','40.00','belete','2026-01-17 02:12:53');
INSERT INTO `item_edit_history` VALUES('23','23','Juice','Juice','35.00','100.00','belete','2026-01-17 02:13:16');
INSERT INTO `item_edit_history` VALUES('24','16','Cookie','ስላይስ','8.00','1200.00','belete','2026-01-17 02:13:47');
INSERT INTO `item_edit_history` VALUES('25','14','coca colaaaaaa','ሞዘሬላ','100.00','900.00','belete','2026-01-17 02:14:13');
INSERT INTO `item_edit_history` VALUES('26','6','cola','የጸጉር ቅቤ','50.00','1200.00','belete','2026-01-17 02:15:49');
INSERT INTO `item_edit_history` VALUES('27','24','Cookie','አይብ','8.00','500.00','belete','2026-01-17 02:16:20');
INSERT INTO `item_edit_history` VALUES('28','32','Cookie','ሌግ','8.00','720.00','belete','2026-01-17 02:17:19');
INSERT INTO `item_edit_history` VALUES('29','20','Egg','ብረስት','10.00','1500.00','belete','2026-01-17 02:17:52');
INSERT INTO `item_edit_history` VALUES('30','4','Egg','ዊንግ','5.00','700.00','belete','2026-01-17 02:18:10');
INSERT INTO `item_edit_history` VALUES('31','14','ሞዘሬላ','ሞዘሬላ','900.00','1000.00','belete','2026-01-21 01:49:49');

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `locations` VALUES('1','Main Location','Address 1');
INSERT INTO `locations` VALUES('2','Branch Location','Address 2');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('stock_transfer','system','alert','info') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` VALUES('1','4','✅ Stock Received','You received 100.00 pcs of Egg from admin','info','1','1','2026-01-11 01:14:11');
INSERT INTO `notifications` VALUES('2','4','New Stock Received','You received 1000 pcs of Coffee','info','1',NULL,'2026-01-11 04:35:45');
INSERT INTO `notifications` VALUES('3','4','New Stock Received','You received 50 l of Juice','info','1',NULL,'2026-01-11 04:36:21');
INSERT INTO `notifications` VALUES('4','4','✅ Stock Received','You received 1000.00 pcs of Coffee from admin','info','1','2','2026-01-11 04:45:25');
INSERT INTO `notifications` VALUES('5','4','✅ Stock Received','You received 50.00 l of Juice from admin','info','1','3','2026-01-11 04:45:25');
INSERT INTO `notifications` VALUES('6','4','New Stock Received','You received 100 pcs of Coffee','info','1',NULL,'2026-01-11 04:59:26');
INSERT INTO `notifications` VALUES('7','4','New Stock Received','You received 100 can of Egg','info','1',NULL,'2026-01-11 05:04:06');
INSERT INTO `notifications` VALUES('8','4','New Stock Received','You received 400 ml of Coffee','info','1',NULL,'2026-01-11 05:29:02');
INSERT INTO `notifications` VALUES('9','4','✅ Stock Received','You received 100.00 pcs of Coffee from admin','info','1','4','2026-01-11 05:29:18');
INSERT INTO `notifications` VALUES('10','4','✅ Stock Received','You received 100.00 can of Egg from admin','info','1','5','2026-01-11 05:29:18');
INSERT INTO `notifications` VALUES('11','4','✅ Stock Received','You received 400.00 ml of Coffee from admin','info','1','6','2026-01-11 05:29:19');
INSERT INTO `notifications` VALUES('12','4','New Stock Received','You received 100 pcs of Egg','info','1',NULL,'2026-01-11 05:59:09');
INSERT INTO `notifications` VALUES('13','4','New Stock Received','You received 10000 m of Egg','info','1',NULL,'2026-01-11 06:28:16');
INSERT INTO `notifications` VALUES('14','4','New Stock Received','You received 1000 kg of Cookie','info','1',NULL,'2026-01-11 21:02:48');
INSERT INTO `notifications` VALUES('15','4','New Stock Received','You received 100 l of Juice','info','1',NULL,'2026-01-11 21:39:16');
INSERT INTO `notifications` VALUES('16','4','New Stock Received','You received 200 pcs of ዳቦ','info','1',NULL,'2026-01-11 21:59:47');
INSERT INTO `notifications` VALUES('17','4','📦 New Stock Transfer Available','Admin sent you 100.00 pcs of Egg. Please approve or reject.','stock_transfer','1','7','2026-01-11 22:51:09');
INSERT INTO `notifications` VALUES('18','4','📦 New Stock Transfer Available','Admin sent you 10000.00 m of Egg. Please approve or reject.','stock_transfer','1','8','2026-01-11 22:51:09');
INSERT INTO `notifications` VALUES('19','4','📦 New Stock Transfer Available','Admin sent you 1000.00 kg of Cookie. Please approve or reject.','stock_transfer','1','9','2026-01-11 22:51:09');
INSERT INTO `notifications` VALUES('20','4','📦 New Stock Transfer Available','Admin sent you 100.00 l of Juice. Please approve or reject.','stock_transfer','1','10','2026-01-11 22:51:09');
INSERT INTO `notifications` VALUES('21','4','📦 New Stock Transfer Available','Admin sent you 200.00 pcs of ዳቦ. Please approve or reject.','stock_transfer','1','11','2026-01-11 22:51:09');
INSERT INTO `notifications` VALUES('22','4','New Stock Received','You received 1000 kg of ብረስት','','0',NULL,'2026-01-21 01:04:17');

DROP TABLE IF EXISTS `places`;
CREATE TABLE `places` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `place_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `places` VALUES('1','hayet','','active','1','2026-01-11 00:38:38','2026-01-11 00:38:38');
INSERT INTO `places` VALUES('2','goro','','active','1','2026-01-11 00:40:11','2026-01-11 00:40:11');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `last_edited_by` varchar(100) DEFAULT NULL,
  `last_edited_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` VALUES('1','የምግብ ቅቤ','930.00','1','belete','2026-01-17 02:12:12','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('4','ዊንግ','700.00','1','belete','2026-01-17 02:18:10','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('6','የጸጉር ቅቤ','1200.00','1','belete','2026-01-17 02:15:49','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('14','ሞዘሬላ','1000.00','2','belete','2026-01-21 01:49:49','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('16','ስላይስ','1200.00','2','belete','2026-01-17 02:13:47','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('19','ናይልቸርች','1400.00','3','belete','2026-01-17 02:08:12','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('20','ብረስት','1500.00','3','belete','2026-01-17 02:17:52','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('22','የሀበሻ ዶሮ','650.00','3','belete','2026-01-17 02:08:30','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('24','አይብ','500.00','3','belete','2026-01-17 02:16:20','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('30','የፈረንጅ ዶሮ','1000.00','4','belete','2026-01-17 02:08:51','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('32','ሌግ','800.00','4','Roza','2026-01-21 01:02:34','1','2026-01-09 03:13:43');
INSERT INTO `products` VALUES('37','ፍሌቶ','490.00',NULL,'belete','2026-01-17 02:07:50','1','2026-01-16 02:23:32');
INSERT INTO `products` VALUES('38','አንባዛ','450.00',NULL,'belete','2026-01-17 02:07:32','1','2026-01-16 03:35:20');
INSERT INTO `products` VALUES('40','ወተት','100.00',NULL,'belete','2026-01-17 02:02:55','1','2026-01-16 03:54:45');
INSERT INTO `products` VALUES('42','እርጎ','65.00',NULL,'belete','2026-01-17 02:07:08','1','2026-01-16 04:27:29');

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sale_items` VALUES('1','1','Juice','999.00','35.00','34965.00');

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales` VALUES('1','1','belete','34965.00','34965.00','0.00','cash','2018-01-14','2026-01-14 13:27:31');
INSERT INTO `sales` VALUES('3','1',NULL,'34965.00','0.00','0.00',NULL,NULL,'2026-01-14 13:54:50');
INSERT INTO `sales` VALUES('4','1',NULL,'34965.00','0.00','0.00',NULL,NULL,'2026-01-14 13:55:33');
INSERT INTO `sales` VALUES('5','1',NULL,'34930.00','0.00','0.00',NULL,NULL,'2026-01-14 13:58:01');

DROP TABLE IF EXISTS `seller_inventory`;
CREATE TABLE `seller_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `seller_inventory` VALUES('1','4','Juice','100.00','80.00','l','2026-01-17 13:13:16');
INSERT INTO `seller_inventory` VALUES('2','4','Tea','0.00','100.00','pcs','2026-01-15 10:31:07');
INSERT INTO `seller_inventory` VALUES('3','1','Juice','100.00','4.00','l','2026-01-17 13:13:16');
INSERT INTO `seller_inventory` VALUES('4','1','ዳቦ','0.00','77999999.99','kg','2026-01-16 15:53:32');
INSERT INTO `seller_inventory` VALUES('5','4','Water','0.00','21004.00','l','2026-01-17 10:49:54');
INSERT INTO `seller_inventory` VALUES('6','4','Coffee','1000.00','8998.00','pcs','2026-01-17 13:11:45');
INSERT INTO `seller_inventory` VALUES('7','4','Coca Cola','0.00','93.00','pcs','2026-01-17 09:08:08');
INSERT INTO `seller_inventory` VALUES('8','4','Egg','10.00','997.00','pcs','2026-01-17 13:12:40');
INSERT INTO `seller_inventory` VALUES('9','4','Cookie','0.00','99997.00','kg','2026-01-16 13:58:27');
INSERT INTO `seller_inventory` VALUES('11','1','Apples','0.00','50.00','kg','2026-01-15 10:47:04');
INSERT INTO `seller_inventory` VALUES('12','1','Carrots','0.00','30.00','kg','2026-01-15 10:47:26');
INSERT INTO `seller_inventory` VALUES('13','1','Potatoes','0.00','100.00','kg','2026-01-15 10:47:28');
INSERT INTO `seller_inventory` VALUES('15','1','Coffee','1000.00','989.00','pcs','2026-01-17 13:11:45');
INSERT INTO `seller_inventory` VALUES('16','1','444445','0.00','9999.00','kg','2026-01-16 15:53:46');
INSERT INTO `seller_inventory` VALUES('17','1','2222222222222','200.00','0.00','pcs','2026-01-16 14:54:45');
INSERT INTO `seller_inventory` VALUES('18','1','milk12333333','10000.00','0.00','pcs','2026-01-16 15:06:07');
INSERT INTO `seller_inventory` VALUES('19','1','30000','100.00','0.00','pcs','2026-01-16 15:27:29');
INSERT INTO `seller_inventory` VALUES('20','1','12121212','0.00','9000.00','pcs','2026-01-16 22:23:31');
INSERT INTO `seller_inventory` VALUES('21','4','2222222222222','0.00','10000.00','kg','2026-01-17 09:57:03');
INSERT INTO `seller_inventory` VALUES('22','1','ወተት','0.00','438.00','l','2026-01-21 09:20:22');
INSERT INTO `seller_inventory` VALUES('23','4','ሌግ','0.00','90.46','kg','2026-01-21 13:11:17');
INSERT INTO `seller_inventory` VALUES('24','1','ሌግ','0.00','90.00','kg','2026-01-21 12:53:55');
INSERT INTO `seller_inventory` VALUES('25','1','ሞዘሬላ','1000.00','100.00','kg','2026-01-21 12:49:49');
INSERT INTO `seller_inventory` VALUES('26','4','ስላይስ','0.00','1000.00','kg','2026-01-21 12:01:57');
INSERT INTO `seller_inventory` VALUES('27','4','ብረስት','1500.00','900.00','kg','2026-01-21 14:38:44');
INSERT INTO `seller_inventory` VALUES('28','1','','0.00','0.00','pcs','2026-01-21 12:31:12');
INSERT INTO `seller_inventory` VALUES('29','1','ቡና','200.00','0.00','pcs','2026-01-21 12:49:29');
INSERT INTO `seller_inventory` VALUES('30','4','TESFA','150.00','0.00','pcs','2026-01-21 14:39:17');

DROP TABLE IF EXISTS `sellers`;
CREATE TABLE `sellers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `daily_limit` decimal(10,2) DEFAULT 5000.00,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sellers` VALUES('1','Seller_1','5000.00','1');

DROP TABLE IF EXISTS `stock_logs`;
CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'pcs',
  `source` varchar(100) DEFAULT 'seller',
  `added_by` varchar(255) NOT NULL,
  `date_added` datetime DEFAULT current_timestamp(),
  `ethiopian_date` date DEFAULT NULL,
  `ethiopian_time` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `delivery_method` enum('direct','admin_sent') DEFAULT 'direct',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_logs` VALUES('1','4','Roza','Juice','1000.00','l','admin','Roza','2026-01-14 08:26:25',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('2','4','Roza','Tea','10000.00','pcs','supplier','Roza','2026-01-14 08:46:29',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('3','1','belete','Juice','10000.00','l','admin','belete','2026-01-14 10:05:00',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('4','1','belete','ዳቦ','100000.00','pcs','admin','belete','2026-01-14 12:41:40',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('5','1','','Juice','-500.00','pcs','sale','','2026-01-14 00:00:00',NULL,NULL,'Sale transaction','direct');
INSERT INTO `stock_logs` VALUES('6','1','','Juice','-100.00','pcs','sale','','2026-01-14 00:00:00',NULL,NULL,'Sale transaction','direct');
INSERT INTO `stock_logs` VALUES('7','1','','Juice','-50.00','pcs','sale','','2026-01-14 00:00:00',NULL,NULL,'Sale transaction','direct');
INSERT INTO `stock_logs` VALUES('8','1','POS System','ዳቦ','-20000.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('9','1','POS System','ዳቦ','-20000.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('10','1','POS System','Juice','-8.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('11','1','POS System','Juice','-6.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('12','1','POS System','Juice','-10.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('13','1','POS System','Juice','-1.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('14','1','POS System','Juice','-1.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('15','1','POS System','Juice','-1.00','pcs','sale_deduction','POS System','2026-01-14 00:00:00',NULL,NULL,'Stock deducted from sale - Batch update','direct');
INSERT INTO `stock_logs` VALUES('16','4','Roza','Water','10000.00','pcs','admin','Roza','2026-01-15 07:09:19',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('17','4','Roza','Coffee','10000.00','pcs','admin','Roza','2026-01-15 07:26:17',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('18','4','Roza','Coca Cola','100.00','pcs','transfer','Roza (Admin)','2026-01-15 07:28:58',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('19','4','Roza','Egg','1000.00','pcs','admin','Roza (Admin)','2026-01-15 07:54:43',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('20','4','Roza','Cookie','100000.00','kg','farmer','Roza','2026-01-15 07:58:00',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('21','4','Roza','Tea','100.00','pcs','admin','Roza (Admin)','2026-01-15 08:31:07',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('22','1','belete','Apples','50.00','kg','Admin1','belete','2026-01-15 08:47:04',NULL,NULL,'Fresh from farm','admin_sent');
INSERT INTO `stock_logs` VALUES('23','1','belete','Carrots','30.00','kg','Admin2','belete','2026-01-15 08:47:26',NULL,NULL,'Organic carrots','admin_sent');
INSERT INTO `stock_logs` VALUES('24','1','belete','Potatoes','100.00','kg','Admin1','belete','2026-01-15 08:47:28',NULL,NULL,'New harvest','admin_sent');
INSERT INTO `stock_logs` VALUES('25','1','belete','ዳቦ','99999999.99','kg','admin','belete','2026-01-15 09:49:38',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('26','1','belete','Coffee','1000.00','pcs','admin','belete','2026-01-16 06:29:22',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('27','1','belete','444445','10000.00','kg','admin','belete','2026-01-16 12:53:03',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('28','1','belete','12121212','10000.00','pcs','admin','belete','2026-01-16 20:23:14',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('29','4','Roza','2222222222222','10000.00','kg','mine','Roza','2026-01-17 07:57:03',NULL,NULL,'','direct');
INSERT INTO `stock_logs` VALUES('30','4','Roza','Water','1000.00','l','admin','Roza','2026-01-17 10:49:54','2018-05-09','4:49:54 ጥዋት','','direct');
INSERT INTO `stock_logs` VALUES('31','1','belete','ወተት','450.00','l','admin','belete','2026-01-17 14:01:29','2018-05-09','8:01:29 ከሰዓት','','direct');
INSERT INTO `stock_logs` VALUES('32','4','Roza','ሌግ','100.00','kg','admin','Roza','2026-01-21 08:17:47','2018-05-13','2:17:47 ጥዋት','','direct');
INSERT INTO `stock_logs` VALUES('33','1','belete','ሌግ','100.00','kg','admin','belete','2026-01-21 09:51:07','2018-05-13','3:51:07 ጥዋት','','direct');
INSERT INTO `stock_logs` VALUES('34','1','belete','ሞዘሬላ','100.00','kg','kesileshi','belete','2026-01-21 11:37:27','2018-05-13','5:37:27 ጥዋት','','direct');
INSERT INTO `stock_logs` VALUES('35','4','Roza','ስላይስ','1000.00','kg','purchase','Roza','2026-01-21 12:01:57','2018-05-13','6:01:57 ከሰዓት','','direct');

DROP TABLE IF EXISTS `stock_transfer_items`;
CREATE TABLE `stock_transfer_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `transferred_by` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_transfers` VALUES('1','4','Egg','100.00','pcs','Admin','approved',NULL,'','2026-01-10 01:23:10');
INSERT INTO `stock_transfers` VALUES('2','4','Coffee','1000.00','pcs','belete','approved',NULL,'','2026-01-11 04:35:45');
INSERT INTO `stock_transfers` VALUES('3','4','Juice','50.00','l','belete','approved',NULL,'','2026-01-11 04:36:21');
INSERT INTO `stock_transfers` VALUES('4','4','Coffee','100.00','pcs','belete','approved',NULL,'','2026-01-11 04:59:26');
INSERT INTO `stock_transfers` VALUES('5','4','Egg','100.00','can','belete','approved',NULL,'','2026-01-11 05:04:06');
INSERT INTO `stock_transfers` VALUES('6','4','Coffee','400.00','ml','belete','approved',NULL,'','2026-01-11 05:29:02');
INSERT INTO `stock_transfers` VALUES('7','4','Egg','100.00','pcs','admin','pending',NULL,'','2026-01-11 05:59:09');
INSERT INTO `stock_transfers` VALUES('8','4','Egg','10000.00','m','admin','pending',NULL,'','2026-01-11 06:28:16');
INSERT INTO `stock_transfers` VALUES('9','4','Cookie','1000.00','kg','belete','pending',NULL,'','2026-01-11 21:02:48');
INSERT INTO `stock_transfers` VALUES('10','4','Juice','100.00','l','belete','pending',NULL,'','2026-01-11 21:39:16');
INSERT INTO `stock_transfers` VALUES('11','4','ዳቦ','200.00','pcs','belete','pending',NULL,'','2026-01-11 21:59:47');
INSERT INTO `stock_transfers` VALUES('12','4','ብረስት','1000.00','kg','belete','pending',NULL,'','2026-01-21 01:04:17');

DROP TABLE IF EXISTS `transaction_items`;
CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaction_items` VALUES('1','2','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('2','2','15','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('3','2','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('4','2','31','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('5','3','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('6','3','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('7','3','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('8','3','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('9','4','30','Coca Cola','2.00','30.00','60.00');
INSERT INTO `transaction_items` VALUES('10','4','22','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('11','4','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('12','4','25','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('13','4','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('14','4','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('15','4','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('16','4','28','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('17','4','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('18','5','19','Bread','2.00','10.00','20.00');
INSERT INTO `transaction_items` VALUES('19','5','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('20','5','30','Coca Cola','8.00','30.00','240.00');
INSERT INTO `transaction_items` VALUES('21','5','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('22','5','22','Coca Cola','2.00','30.00','60.00');
INSERT INTO `transaction_items` VALUES('23','5','1','Coffee','2.00','25.00','50.00');
INSERT INTO `transaction_items` VALUES('24','5','25','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('25','5','9','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('26','6','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('27','7','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('28','8','19','Bread','6.00','10.00','60.00');
INSERT INTO `transaction_items` VALUES('29','9','19','Bread','8.00','10.00','80.00');
INSERT INTO `transaction_items` VALUES('30','10','19','Bread','7.00','10.00','70.00');
INSERT INTO `transaction_items` VALUES('31','11','19','Bread','6.00','10.00','60.00');
INSERT INTO `transaction_items` VALUES('32','12','29','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('33','13','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('34','13','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('35','13','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('36','14','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('37','14','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('38','14','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('39','14','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('40','14','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('41','15','19','Bread','1.00','10.00','10.00');
INSERT INTO `transaction_items` VALUES('42','15','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('43','15','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('44','15','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('45','15','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('46','15','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('47','16','19','Bread','1.00','10.00','10.00');
INSERT INTO `transaction_items` VALUES('48','16','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('49','16','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('50','16','3','fggggggggggggggggg','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('51','16','17','kb,,,,,j','8.00','100.00','800.00');
INSERT INTO `transaction_items` VALUES('52','16','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('53','16','20','Egg','4.00','5.00','20.00');
INSERT INTO `transaction_items` VALUES('54','17','19','Bread','1.00','10.00','10.00');
INSERT INTO `transaction_items` VALUES('55','17','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('56','17','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('57','17','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('58','17','12','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('59','17','28','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('60','17','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('61','17','3','fggggggggggggggggg','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('62','18','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('63','18','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('64','18','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('65','18','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('66','18','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('67','19','6','Coca Cola','2.00','30.00','60.00');
INSERT INTO `transaction_items` VALUES('68','19','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('69','19','22','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('70','19','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('71','19','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('72','19','9','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('73','20','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('74','21','28','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('75','22','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('76','22','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('77','23','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('78','23','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('79','23','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('80','24','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('81','24','28','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('82','24','15','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('83','25','14','Coca Cola','2.00','30.00','60.00');
INSERT INTO `transaction_items` VALUES('84','25','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('85','25','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('86','25','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('87','25','12','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('88','25','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('89','25','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('90','25','15','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('91','25','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('92','25','22','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('93','26','19','Bread','7.00','10.00','70.00');
INSERT INTO `transaction_items` VALUES('94','26','6','Coca Cola','2.00','30.00','60.00');
INSERT INTO `transaction_items` VALUES('95','26','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('96','26','22','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('97','26','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('98','26','9','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('99','26','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('100','26','12','Egg','2.00','5.00','10.00');
INSERT INTO `transaction_items` VALUES('101','26','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('102','26','28','Egg','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('103','26','26','Tea','1.00','15.00','15.00');
INSERT INTO `transaction_items` VALUES('104','26','13','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('105','27','19','Bread','1.00','10.00','10.00');
INSERT INTO `transaction_items` VALUES('106','27','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('107','27','14','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('108','27','22','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('109','27','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('110','27','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('111','27','9','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('112','27','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('113','27','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('114','27','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('115','27','12','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('116','27','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('117','27','28','Egg','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('118','27','3','fggggggggggggggggg','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('119','27','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('120','27','15','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('121','27','23','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('122','27','31','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('123','27','17','kb,,,,,j','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('124','27','2','Tea','1.00','15.00','15.00');
INSERT INTO `transaction_items` VALUES('125','27','10','Tea','1.00','15.00','15.00');
INSERT INTO `transaction_items` VALUES('126','27','18','Tea','1.00','15.00','15.00');
INSERT INTO `transaction_items` VALUES('127','27','26','Tea','1.00','15.00','15.00');
INSERT INTO `transaction_items` VALUES('128','27','5','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('129','27','13','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('130','27','21','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('131','27','29','Water','1.00','20.00','20.00');
INSERT INTO `transaction_items` VALUES('132','27','11','ዳቦ','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('133','27','27','ዳቦ','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('134','28','4','Egg','14000.00','5.00','70000.00');
INSERT INTO `transaction_items` VALUES('135','29','10','Tea','5000.00','15.00','75000.00');
INSERT INTO `transaction_items` VALUES('136','30','7','Juice','100.00','35.00','3500.00');
INSERT INTO `transaction_items` VALUES('137','31','31','Juice','100.00','35.00','3500.00');
INSERT INTO `transaction_items` VALUES('138','32','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('139','33','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('140','34','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('141','35','7','Juice','500.00','35.00','17500.00');
INSERT INTO `transaction_items` VALUES('142','36','7','Juice','1000.00','35.00','35000.00');
INSERT INTO `transaction_items` VALUES('143','37','7','Juice','1000.00','35.00','35000.00');
INSERT INTO `transaction_items` VALUES('144','38','7','Juice','1000.00','35.00','35000.00');
INSERT INTO `transaction_items` VALUES('145','39','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('146','40','7','Juice','1000.00','35.00','35000.00');
INSERT INTO `transaction_items` VALUES('147','41','7','Juice','50.00','35.00','1750.00');
INSERT INTO `transaction_items` VALUES('148','42','7','Juice','50.00','35.00','1750.00');
INSERT INTO `transaction_items` VALUES('149','43','7','Juice','50.00','35.00','1750.00');
INSERT INTO `transaction_items` VALUES('150','44','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('151','45','7','Juice','50.00','35.00','1750.00');
INSERT INTO `transaction_items` VALUES('152','46','31','Juice','20.00','35.00','700.00');
INSERT INTO `transaction_items` VALUES('153','47','11','ዳቦ','50000.00','25.00','1250000.00');
INSERT INTO `transaction_items` VALUES('154','48','11','ዳቦ','20000.00','25.00','500000.00');
INSERT INTO `transaction_items` VALUES('155','49','11','ዳቦ','20000.00','25.00','500000.00');
INSERT INTO `transaction_items` VALUES('156','50','15','Juice','8.00','35.00','280.00');
INSERT INTO `transaction_items` VALUES('157','51','7','Juice','6.00','35.00','210.00');
INSERT INTO `transaction_items` VALUES('158','52','7','Juice','10.00','35.00','350.00');
INSERT INTO `transaction_items` VALUES('159','53','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('160','54','7','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('161','55','15','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('162','56','23','Juice','4.00','35.00','140.00');
INSERT INTO `transaction_items` VALUES('163','57','7','Juice','10.00','35.00','350.00');
INSERT INTO `transaction_items` VALUES('164','58','15','Juice','2.00','35.00','70.00');
INSERT INTO `transaction_items` VALUES('165','59','7','Juice','2.00','35.00','70.00');
INSERT INTO `transaction_items` VALUES('166','60','31','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('167','61','29','Water','1000.00','20.00','20000.00');
INSERT INTO `transaction_items` VALUES('168','62','31','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('169','63','11','ዳቦ','10000000.00','25.00','99999999.99');
INSERT INTO `transaction_items` VALUES('170','64','23','Juice','13.00','35.00','455.00');
INSERT INTO `transaction_items` VALUES('171','65','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('172','66','6','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('173','67','31','Juice','1.00','35.00','35.00');
INSERT INTO `transaction_items` VALUES('174','68','27','ዳቦ','10000000.00','100.00','99999999.99');
INSERT INTO `transaction_items` VALUES('175','69','9','Coffee','1000.00','25.00','25000.00');
INSERT INTO `transaction_items` VALUES('176','70','11','ዳቦ','1000000.00','25.00','25000000.00');
INSERT INTO `transaction_items` VALUES('177','71','1','Coffee','10.00','25.00','250.00');
INSERT INTO `transaction_items` VALUES('178','72','30','Coca Cola','1.00','30.00','30.00');
INSERT INTO `transaction_items` VALUES('179','72','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('180','72','9','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('181','72','16','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('182','72','24','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('183','72','32','Cookie','1.00','8.00','8.00');
INSERT INTO `transaction_items` VALUES('184','72','4','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('185','72','12','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('186','72','20','Egg','1.00','5.00','5.00');
INSERT INTO `transaction_items` VALUES('187','73','11','ዳቦ','1000000.00','25.00','25000000.00');
INSERT INTO `transaction_items` VALUES('188','74','38','444445','1.00','2223.00','2223.00');
INSERT INTO `transaction_items` VALUES('189','75','39','12121212','1000.00','1000.00','1000000.00');
INSERT INTO `transaction_items` VALUES('190','76','22','Coca Cola','4.00','30.00','120.00');
INSERT INTO `transaction_items` VALUES('191','77','29','Water','8990.00','20.00','179800.00');
INSERT INTO `transaction_items` VALUES('192','78','29','Water','6.00','20.00','120.00');
INSERT INTO `transaction_items` VALUES('193','79','1','Coffee','1.00','25.00','25.00');
INSERT INTO `transaction_items` VALUES('194','80','40','ወተት','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('195','81','40','ወተት','10.00','100.00','1000.00');
INSERT INTO `transaction_items` VALUES('196','82','32','ሌግ','1.00','720.00','720.00');
INSERT INTO `transaction_items` VALUES('197','83','40','ወተት','1.00','100.00','100.00');
INSERT INTO `transaction_items` VALUES('198','84','32','ሌግ','1.00','720.00','720.00');
INSERT INTO `transaction_items` VALUES('199','85','32','ሌግ','1.00','720.00','720.00');
INSERT INTO `transaction_items` VALUES('200','86','32','ሌግ','1.00','720.00','720.00');
INSERT INTO `transaction_items` VALUES('201','87','32','ሌግ','1.00','720.00','720.00');
INSERT INTO `transaction_items` VALUES('202','88','32','ሌግ','1.54','720.00','1108.80');
INSERT INTO `transaction_items` VALUES('203','89','32','ሌግ','7.00','800.00','5600.00');
INSERT INTO `transaction_items` VALUES('204','90','32','ሌግ','6.00','800.00','4800.00');
INSERT INTO `transaction_items` VALUES('205','91','20','ብረስት','100.00','1500.00','150000.00');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(100) NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transactions` VALUES('1','100.50','150.00','49.50','cash','1','Seller_1','2026-01-10 13:40:53');
INSERT INTO `transactions` VALUES('2','110.00','200.00','90.00','cash','1','Seller_1','2026-01-10 13:41:39');
INSERT INTO `transactions` VALUES('3','93.00','93.00','0.00','cash','1','Seller_1','2026-01-10 13:41:48');
INSERT INTO `transactions` VALUES('4','174.00','174.00','0.00','cash','1','Seller_1','2026-01-10 13:50:34');
INSERT INTO `transactions` VALUES('5','480.00','480.00','0.00','cash','1','Seller_1','2026-01-10 13:51:05');
INSERT INTO `transactions` VALUES('6','30.00','30.00','0.00','cash','1','Seller_1','2026-01-10 14:05:50');
INSERT INTO `transactions` VALUES('7','30.00','30.00','0.00','cash','1','Seller_1','2026-01-10 14:06:10');
INSERT INTO `transactions` VALUES('8','60.00','60.00','0.00','cash','1','Seller_1','2026-01-10 14:32:56');
INSERT INTO `transactions` VALUES('9','80.00','80.00','0.00','cash','1','Seller_1','2026-01-10 14:33:02');
INSERT INTO `transactions` VALUES('10','70.00','70.00','0.00','cash','1','Seller_1','2026-01-10 14:33:05');
INSERT INTO `transactions` VALUES('11','60.00','60.00','0.00','cash','1','Seller_1','2026-01-10 14:33:09');
INSERT INTO `transactions` VALUES('12','20.00','20.00','0.00','cash','1','Seller_1','2026-01-10 14:36:19');
INSERT INTO `transactions` VALUES('13','90.00','90.00','0.00','cash','1','Seller_1','2026-01-10 16:10:23');
INSERT INTO `transactions` VALUES('14','101.00','0.00','-101.00','cash','1','Seller_1','2026-01-10 18:05:31');
INSERT INTO `transactions` VALUES('15','116.00','0.00','-116.00','telebirr-cash','1','Seller_1','2026-01-10 18:05:43');
INSERT INTO `transactions` VALUES('16','998.00','0.00','-998.00','cbe-cash','1','Seller_1','2026-01-10 18:19:38');
INSERT INTO `transactions` VALUES('17','201.00','0.00','-201.00','telebirr-cash','1','Seller_1','2026-01-10 18:33:48');
INSERT INTO `transactions` VALUES('18','76.00','0.00','-76.00','telebirr-cash','1','Seller_1','2026-01-10 18:36:40');
INSERT INTO `transactions` VALUES('19','200.00','0.00','-200.00','cash','1','Seller_1','2026-01-10 18:37:03');
INSERT INTO `transactions` VALUES('20','30.00','0.00','-30.00','cash','1','Seller_1','2026-01-11 10:11:31');
INSERT INTO `transactions` VALUES('21','5.00','5.00','0.00','cash','3','tesfahun bayih','2026-01-11 12:36:09');
INSERT INTO `transactions` VALUES('22','60.00','0.00','-60.00','cash','2','Super Administrator','2026-01-11 14:18:05');
INSERT INTO `transactions` VALUES('23','90.00','0.00','-90.00','cash','5','admin','2026-01-11 16:58:08');
INSERT INTO `transactions` VALUES('24','48.00','0.00','-48.00','telebirr','5','admin','2026-01-11 17:38:29');
INSERT INTO `transactions` VALUES('25','224.00','0.00','-224.00','telebirr','1','belete','2026-01-12 08:25:13');
INSERT INTO `transactions` VALUES('26','403.00','500.00','97.00','cbe-cash','4','Roza','2026-01-12 10:10:23');
INSERT INTO `transactions` VALUES('27','919.00','0.00','-919.00','abyssinia','4','Roza','2026-01-14 09:15:48');
INSERT INTO `transactions` VALUES('28','70000.00','0.00','-70000.00','cash','4','Roza','2026-01-14 09:18:23');
INSERT INTO `transactions` VALUES('29','75000.00','0.00','-75000.00','cash','4','Roza','2026-01-14 10:46:48');
INSERT INTO `transactions` VALUES('30','3500.00','0.00','-3500.00','cash','4','Roza','2026-01-14 10:48:02');
INSERT INTO `transactions` VALUES('31','3500.00','0.00','-3500.00','cash','4','Roza','2026-01-14 10:48:18');
INSERT INTO `transactions` VALUES('32','35.00','0.00','-35.00','cash','4','Roza','2026-01-14 10:51:39');
INSERT INTO `transactions` VALUES('33','35.00','0.00','-35.00','cash','4','Roza','2026-01-14 11:44:49');
INSERT INTO `transactions` VALUES('34','35.00','0.00','-35.00','cash','4','Roza','2026-01-14 11:50:20');
INSERT INTO `transactions` VALUES('35','17500.00','0.00','-17500.00','cash','4','Roza','2026-01-14 11:50:34');
INSERT INTO `transactions` VALUES('36','35000.00','0.00','-35000.00','cash','1','belete','2026-01-14 12:05:19');
INSERT INTO `transactions` VALUES('37','35000.00','0.00','-35000.00','cash','1','belete','2026-01-14 12:34:41');
INSERT INTO `transactions` VALUES('38','35000.00','0.00','-35000.00','cash','1','belete','2026-01-14 13:04:25');
INSERT INTO `transactions` VALUES('39','35.00','0.00','-35.00','cash','1','belete','2026-01-14 13:06:18');
INSERT INTO `transactions` VALUES('40','35000.00','35000.00','0.00','cash','1','belete','2026-01-14 13:17:58');
INSERT INTO `transactions` VALUES('41','1750.00','1750.00','0.00','cash','1','belete','2026-01-14 15:54:34');
INSERT INTO `transactions` VALUES('42','1750.00','1750.00','0.00','cash','1','belete','2026-01-14 15:55:31');
INSERT INTO `transactions` VALUES('43','1750.00','1750.00','0.00','cash','1','belete','2026-01-14 15:56:10');
INSERT INTO `transactions` VALUES('44','35.00','0.00','-35.00','cash','1','belete','2026-01-14 15:56:40');
INSERT INTO `transactions` VALUES('45','1750.00','0.00','-1750.00','cash','1','belete','2026-01-14 15:56:50');
INSERT INTO `transactions` VALUES('46','700.00','0.00','-700.00','cash','1','belete','2026-01-14 16:09:17');
INSERT INTO `transactions` VALUES('47','1250000.00','0.00','-1250000.00','cash','1','belete','2026-01-14 16:09:31');
INSERT INTO `transactions` VALUES('48','500000.00','0.00','-500000.00','cash','1','belete','2026-01-14 16:14:27');
INSERT INTO `transactions` VALUES('49','500000.00','0.00','-500000.00','cash','1','belete','2026-01-14 16:14:41');
INSERT INTO `transactions` VALUES('50','280.00','0.00','-280.00','cash','1','belete','2026-01-14 16:15:16');
INSERT INTO `transactions` VALUES('51','210.00','0.00','-210.00','cash','1','belete','2026-01-14 16:15:37');
INSERT INTO `transactions` VALUES('52','350.00','0.00','-350.00','cash','1','belete','2026-01-14 16:15:57');
INSERT INTO `transactions` VALUES('53','35.00','0.00','-35.00','cash','1','belete','2026-01-14 16:16:08');
INSERT INTO `transactions` VALUES('54','35.00','0.00','-35.00','cash','1','belete','2026-01-14 16:16:24');
INSERT INTO `transactions` VALUES('55','35.00','0.00','-35.00','cash','1','belete','2026-01-14 16:16:29');
INSERT INTO `transactions` VALUES('56','140.00','0.00','-140.00','cash','1','belete','2026-01-14 16:23:24');
INSERT INTO `transactions` VALUES('57','350.00','0.00','-350.00','cash','1','belete','2026-01-14 16:23:41');
INSERT INTO `transactions` VALUES('58','70.00','0.00','-70.00','cash','1','belete','2026-01-14 16:24:00');
INSERT INTO `transactions` VALUES('59','70.00','0.00','-70.00','cash','1','belete','2026-01-14 16:26:40');
INSERT INTO `transactions` VALUES('60','35.00','0.00','-35.00','cash','4','Roza','2026-01-15 09:06:55');
INSERT INTO `transactions` VALUES('61','20000.00','0.00','-20000.00','cash','4','Roza','2026-01-15 09:09:38');
INSERT INTO `transactions` VALUES('62','35.00','0.00','-35.00','cash','1','belete','2026-01-15 10:55:57');
INSERT INTO `transactions` VALUES('63','99999999.99','0.00','-99999999.99','cash','1','belete','2026-01-15 11:50:06');
INSERT INTO `transactions` VALUES('64','455.00','0.00','-455.00','cash','4','Roza','2026-01-15 14:39:28');
INSERT INTO `transactions` VALUES('65','30.00','0.00','-30.00','cash','4','Roza','2026-01-15 15:20:10');
INSERT INTO `transactions` VALUES('66','30.00','0.00','-30.00','cash','4','Roza','2026-01-15 15:20:19');
INSERT INTO `transactions` VALUES('67','35.00','0.00','-35.00','cash','1','belete','2026-01-15 15:30:42');
INSERT INTO `transactions` VALUES('68','99999999.99','0.00','-99999999.99','cash','1','belete','2026-01-15 15:32:49');
INSERT INTO `transactions` VALUES('69','25000.00','0.00','-25000.00','cash','4','Roza','2026-01-16 08:27:44');
INSERT INTO `transactions` VALUES('70','25000000.00','0.00','-25000000.00','cash','1','belete','2026-01-16 08:28:13');
INSERT INTO `transactions` VALUES('71','250.00','0.00','-250.00','cash','1','belete','2026-01-16 08:29:36');
INSERT INTO `transactions` VALUES('72','119.00','0.00','-119.00','cash','4','Roza','2026-01-16 13:58:27');
INSERT INTO `transactions` VALUES('73','25000000.00','0.00','-25000000.00','cash','1','belete','2026-01-16 15:53:32');
INSERT INTO `transactions` VALUES('74','2223.00','0.00','-2223.00','cash','1','belete','2026-01-16 15:53:46');
INSERT INTO `transactions` VALUES('75','1000000.00','0.00','-1000000.00','cash','1','belete','2026-01-16 22:23:31');
INSERT INTO `transactions` VALUES('76','120.00','0.00','-120.00','cash','4','Roza','2026-01-17 09:08:08');
INSERT INTO `transactions` VALUES('77','179800.00','0.00','-179800.00','cash','4','Roza','2026-01-17 10:33:12');
INSERT INTO `transactions` VALUES('78','120.00','0.00','-120.00','cash','4','Roza','2026-01-17 10:33:32');
INSERT INTO `transactions` VALUES('79','25.00','0.00','-25.00','cash','1','belete','2026-01-17 12:54:30');
INSERT INTO `transactions` VALUES('80','100.00','0.00','-100.00','cash','1','belete','2026-01-17 14:16:02');
INSERT INTO `transactions` VALUES('81','1000.00','0.00','-1000.00','cash','1','belete','2026-01-17 16:14:24');
INSERT INTO `transactions` VALUES('82','720.00','0.00','-720.00','cash','4','Roza','2026-01-21 08:17:57');
INSERT INTO `transactions` VALUES('83','100.00','0.00','-100.00','cash','1','belete','2026-01-21 09:20:22');
INSERT INTO `transactions` VALUES('84','720.00','0.00','-720.00','cash','1','belete','2026-01-21 09:51:20');
INSERT INTO `transactions` VALUES('85','720.00','0.00','-720.00','telebirr','1','belete','2026-01-21 09:53:08');
INSERT INTO `transactions` VALUES('86','720.00','0.00','-720.00','abyssinia','1','belete','2026-01-21 10:14:16');
INSERT INTO `transactions` VALUES('87','720.00','0.00','-720.00','cbe','4','Roza','2026-01-21 11:58:37');
INSERT INTO `transactions` VALUES('88','1108.80','1200.00','91.20','telebirr','4','Roza','2026-01-21 11:59:08');
INSERT INTO `transactions` VALUES('89','5600.00','0.00','-5600.00','cash','1','belete','2026-01-21 12:53:55');
INSERT INTO `transactions` VALUES('90','4800.00','0.00','-4800.00','cash','4','Roza','2026-01-21 13:11:17');
INSERT INTO `transactions` VALUES('91','150000.00','0.00','-150000.00','cash','4','Roza','2026-01-21 14:38:44');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','seller') DEFAULT 'seller',
  `location_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES('1','belete','123456','belete','super_admin',NULL,NULL,'2026-01-08 23:41:24','1');
INSERT INTO `users` VALUES('3','tesfa','359684721','tesfahun bayih','super_admin','1','1','2026-01-09 00:50:19','1');
INSERT INTO `users` VALUES('4','Roza','123','aleltu','seller','1','3','2026-01-09 01:32:46','1');
INSERT INTO `users` VALUES('8','sileshi','123456','sileshi','admin','1','1','2026-01-20 23:41:24','1');

SET FOREIGN_KEY_CHECKS=1;
