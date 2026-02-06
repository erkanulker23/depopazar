-- Kaza belgeleri: ruhsat, kimlik, kaza fotoğrafı vb.
CREATE TABLE IF NOT EXISTS `vehicle_accident_documents` (
  `id` CHAR(36) NOT NULL,
  `accident_id` CHAR(36) NOT NULL,
  `document_kind` VARCHAR(30) NOT NULL DEFAULT 'diger' COMMENT 'ruhsat, kimlik, kaza_foto, diger',
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vad_accident_id` (`accident_id`),
  KEY `idx_vad_document_kind` (`document_kind`),
  CONSTRAINT `fk_vad_accident` FOREIGN KEY (`accident_id`) REFERENCES `vehicle_accidents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
