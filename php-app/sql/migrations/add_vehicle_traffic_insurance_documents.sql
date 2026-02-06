-- Trafik sigortası belgeleri (PDF, resim)
CREATE TABLE IF NOT EXISTS `vehicle_traffic_insurance_documents` (
  `id` CHAR(36) NOT NULL,
  `traffic_insurance_id` CHAR(36) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vtid_traffic_insurance_id` (`traffic_insurance_id`),
  CONSTRAINT `fk_vtid_traffic_insurance` FOREIGN KEY (`traffic_insurance_id`) REFERENCES `vehicle_traffic_insurances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
