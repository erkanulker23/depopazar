-- Aynı anda birden fazla taksit tahsil edildi uyarısının kullanıcı tarafından onaylanması

CREATE TABLE IF NOT EXISTS `bulk_paid_acknowledgments` (
  `id` CHAR(36) NOT NULL,
  `customer_id` CHAR(36) NOT NULL,
  `contract_id` CHAR(36) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `acknowledged_by_user_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bulk_paid_ack_contract_paid` (`contract_id`, `paid_at`),
  KEY `idx_bulk_paid_ack_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
