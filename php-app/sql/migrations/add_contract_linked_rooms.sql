-- Tek sözleşmede birden fazla oda (bağlı odalar)

CREATE TABLE IF NOT EXISTS `contract_linked_rooms` (
  `id` CHAR(36) NOT NULL,
  `contract_id` CHAR(36) NOT NULL,
  `room_id` CHAR(36) NOT NULL,
  `monthly_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'Oda bazlı ücret (tek sözleşme toplamında)',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contract_linked_room` (`contract_id`, `room_id`),
  KEY `idx_clr_contract_id` (`contract_id`),
  KEY `idx_clr_room_id` (`room_id`),
  CONSTRAINT `fk_clr_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_clr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
