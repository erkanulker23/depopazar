-- Araçlar tablosu: plaka, model yılı, kasko tarihi, muayene tarihi, kasa m³
-- Deploy'da deploy.sh otomatik çalıştırır. Manuel: mysql -u user -p database < add_vehicles_table.sql
-- CREATE IF NOT EXISTS kullanıldığı için tekrar çalıştırmak güvenlidir.

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `plate` VARCHAR(20) NOT NULL,
  `model_year` INT DEFAULT NULL,
  `kasko_date` DATE DEFAULT NULL,
  `inspection_date` DATE DEFAULT NULL,
  `cargo_volume_m3` DECIMAL(6,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicles_company_plate` (`company_id`, `plate`),
  KEY `idx_vehicles_company_id` (`company_id`),
  KEY `idx_vehicles_deleted_at` (`deleted_at`),
  KEY `idx_vehicles_kasko_date` (`kasko_date`),
  KEY `idx_vehicles_inspection_date` (`inspection_date`),
  CONSTRAINT `fk_vehicles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
