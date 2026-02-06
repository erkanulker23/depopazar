-- Araç trafik sigortaları (poliçe bazlı)
-- Çalıştırma: mysql -u user -p database < add_vehicle_traffic_insurances.sql

CREATE TABLE IF NOT EXISTS `vehicle_traffic_insurances` (
  `id` CHAR(36) NOT NULL,
  `vehicle_id` CHAR(36) NOT NULL,
  `policy_number` VARCHAR(100) DEFAULT NULL,
  `insurer_name` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vti_vehicle_id` (`vehicle_id`),
  KEY `idx_vti_deleted_at` (`deleted_at`),
  KEY `idx_vti_end_date` (`end_date`),
  CONSTRAINT `fk_vti_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
