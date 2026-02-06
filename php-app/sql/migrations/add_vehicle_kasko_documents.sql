-- Kasko belgeleri (PDF, resim)
CREATE TABLE IF NOT EXISTS `vehicle_kasko_documents` (
  `id` CHAR(36) NOT NULL,
  `kasko_id` CHAR(36) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vkd_kasko_id` (`kasko_id`),
  CONSTRAINT `fk_vkd_kasko` FOREIGN KEY (`kasko_id`) REFERENCES `vehicle_kaskos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
