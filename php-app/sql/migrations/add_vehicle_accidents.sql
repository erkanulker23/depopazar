-- Araç kaza kayıtları
-- Çalıştırma: mysql -u user -p database < add_vehicle_accidents.sql

CREATE TABLE IF NOT EXISTS `vehicle_accidents` (
  `id` CHAR(36) NOT NULL,
  `vehicle_id` CHAR(36) NOT NULL,
  `accident_date` DATE NOT NULL,
  `description` TEXT DEFAULT NULL,
  `damage_info` TEXT DEFAULT NULL,
  `repair_cost` DECIMAL(12,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_va_vehicle_id` (`vehicle_id`),
  KEY `idx_va_deleted_at` (`deleted_at`),
  KEY `idx_va_accident_date` (`accident_date`),
  CONSTRAINT `fk_va_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
