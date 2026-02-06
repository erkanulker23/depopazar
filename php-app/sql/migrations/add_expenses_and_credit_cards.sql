-- Masraflar, masraf kategorileri ve kredi kartları tabloları
-- bank_accounts'a opening_balance kolonu eklenir (cari bakiye hesabı için)
-- Bu dosya idempotent: birden fazla çalıştırıldığında hata vermez.

-- bank_accounts: opening_balance kolonu yoksa ekle
DELIMITER //
DROP PROCEDURE IF EXISTS add_opening_balance_if_missing//
CREATE PROCEDURE add_opening_balance_if_missing()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_accounts' AND COLUMN_NAME = 'opening_balance'
  ) THEN
    ALTER TABLE `bank_accounts` ADD COLUMN `opening_balance` DECIMAL(12,2) DEFAULT 0 COMMENT 'Açılış bakiyesi' AFTER `is_active`;
  END IF;
END//
DELIMITER ;
CALL add_opening_balance_if_missing();
DROP PROCEDURE IF EXISTS add_opening_balance_if_missing;

-- credit_cards: kredi kartı bilgileri (ayarlarda)
CREATE TABLE IF NOT EXISTS `credit_cards` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `bank_name` VARCHAR(255) NOT NULL,
  `card_holder_name` VARCHAR(255) NOT NULL,
  `last_four_digits` VARCHAR(4) DEFAULT NULL,
  `nickname` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_credit_cards_company_id` (`company_id`),
  KEY `idx_credit_cards_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_credit_cards_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- expense_categories: masraf kategorileri
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_expense_categories_company_id` (`company_id`),
  KEY `idx_expense_categories_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_expense_categories_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- expenses: masraflar
-- payment_source_type: 'bank_account' | 'credit_card'
-- payment_source_id: bank_accounts.id veya credit_cards.id
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `category_id` CHAR(36) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `payment_source_type` ENUM('bank_account','credit_card') NOT NULL,
  `payment_source_id` CHAR(36) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by_user_id` CHAR(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_company_id` (`company_id`),
  KEY `idx_expenses_category_id` (`category_id`),
  KEY `idx_expenses_expense_date` (`expense_date`),
  KEY `idx_expenses_payment_source` (`payment_source_type`, `payment_source_id`),
  KEY `idx_expenses_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_expenses_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
