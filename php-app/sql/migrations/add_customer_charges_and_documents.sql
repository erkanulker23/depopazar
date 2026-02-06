-- Müşteri ek borç (Borçlandır) ve müşteri belgeleri (Belge Ekle)

-- customer_charges: Sözleşme dışı manuel borç kayıtları
CREATE TABLE IF NOT EXISTS `customer_charges` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `customer_id` CHAR(36) NOT NULL,
  `company_id` CHAR(36) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('pending','paid','cancelled') DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_charges_customer_id` (`customer_id`),
  KEY `idx_customer_charges_company_id` (`company_id`),
  KEY `idx_customer_charges_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customer_charges_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_charges_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- customer_documents: Müşteriye yüklenen belgeler
CREATE TABLE IF NOT EXISTS `customer_documents` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `customer_id` CHAR(36) NOT NULL,
  `company_id` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(512) NOT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_documents_customer_id` (`customer_id`),
  KEY `idx_customer_documents_company_id` (`company_id`),
  KEY `idx_customer_documents_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customer_documents_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_documents_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
