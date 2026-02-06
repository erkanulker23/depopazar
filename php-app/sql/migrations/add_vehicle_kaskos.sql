-- Araç kasko poliçeleri (poliçe bazlı)
-- Çalıştırma: mysql -u user -p database < add_vehicle_kaskos.sql

CREATE TABLE IF NOT EXISTS `vehicle_kaskos` (
  `id` CHAR(36) NOT NULL,
  `vehicle_id` CHAR(36) NOT NULL,
  `policy_number` VARCHAR(100) DEFAULT NULL,
  `insurer_name` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `premium_amount` DECIMAL(12,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vk_vehicle_id` (`vehicle_id`),
  KEY `idx_vk_deleted_at` (`deleted_at`),
  KEY `idx_vk_end_date` (`end_date`),
  CONSTRAINT `fk_vk_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
