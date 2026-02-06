-- DepoPazar / Depotakip veritabanı şeması (NestJS entity'lerden)
-- MySQL 5.7+ / 8.0. Karakter seti: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `project_name` VARCHAR(255) DEFAULT NULL,
  `logo_url` VARCHAR(512) DEFAULT NULL,
  `contract_template_url` VARCHAR(512) DEFAULT NULL,
  `insurance_template_url` VARCHAR(512) DEFAULT NULL,
  `primary_color` VARCHAR(100) DEFAULT NULL,
  `secondary_color` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `whatsapp_number` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `mersis_number` VARCHAR(50) DEFAULT NULL,
  `trade_registry_number` VARCHAR(50) DEFAULT NULL,
  `tax_office` VARCHAR(255) DEFAULT NULL,
  `package_type` VARCHAR(50) DEFAULT 'basic',
  `max_warehouses` INT DEFAULT 0,
  `max_rooms` INT DEFAULT 0,
  `max_customers` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `subscription_expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_companies_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users
CREATE TABLE IF NOT EXISTS `users` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('super_admin','company_owner','company_staff','data_entry','accounting','customer') DEFAULT 'customer',
  `company_id` CHAR(36) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_company_id` (`company_id`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- warehouses
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `company_id` CHAR(36) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `total_floors` INT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_warehouses_company_id` (`company_id`),
  KEY `idx_warehouses_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_warehouses_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `room_number` VARCHAR(100) NOT NULL,
  `warehouse_id` CHAR(36) NOT NULL,
  `area_m2` DECIMAL(10,2) NOT NULL,
  `monthly_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('empty','occupied','reserved','locked') DEFAULT 'empty',
  `floor` VARCHAR(50) DEFAULT NULL,
  `block` VARCHAR(50) DEFAULT NULL,
  `corridor` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rooms_warehouse_id` (`warehouse_id`),
  KEY `idx_rooms_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_rooms_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `user_id` CHAR(36) DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `identity_number` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_customers_company_id` (`company_id`),
  KEY `idx_customers_user_id` (`user_id`),
  KEY `idx_customers_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- contracts
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `contract_number` VARCHAR(100) NOT NULL UNIQUE,
  `customer_id` CHAR(36) NOT NULL,
  `room_id` CHAR(36) NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `monthly_price` DECIMAL(10,2) NOT NULL,
  `payment_frequency_months` INT DEFAULT 1,
  `terms` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `terminated_at` DATETIME DEFAULT NULL,
  `transportation_fee` DECIMAL(10,2) DEFAULT 0,
  `pickup_location` VARCHAR(255) DEFAULT NULL,
  `sold_by_user_id` CHAR(36) DEFAULT NULL,
  `discount` DECIMAL(10,2) DEFAULT 0,
  `driver_name` VARCHAR(100) DEFAULT NULL,
  `driver_phone` VARCHAR(20) DEFAULT NULL,
  `vehicle_plate` VARCHAR(20) DEFAULT NULL,
  `contract_pdf_url` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contracts_customer_id` (`customer_id`),
  KEY `idx_contracts_room_id` (`room_id`),
  KEY `idx_contracts_sold_by` (`sold_by_user_id`),
  KEY `idx_contracts_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_contracts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contracts_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contracts_sold_by` FOREIGN KEY (`sold_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- contract_monthly_prices
CREATE TABLE IF NOT EXISTS `contract_monthly_prices` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `contract_id` CHAR(36) NOT NULL,
  `month` VARCHAR(7) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cmp_contract_id` (`contract_id`),
  CONSTRAINT `fk_cmp_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- contract_staff
CREATE TABLE IF NOT EXISTS `contract_staff` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `contract_id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contract_staff_contract` (`contract_id`),
  KEY `idx_contract_staff_user` (`user_id`),
  CONSTRAINT `fk_cs_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- bank_accounts
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `bank_name` VARCHAR(255) NOT NULL,
  `account_holder_name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `iban` VARCHAR(34) DEFAULT NULL,
  `branch_name` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bank_accounts_company_id` (`company_id`),
  CONSTRAINT `fk_bank_accounts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `payment_number` VARCHAR(100) NOT NULL UNIQUE,
  `contract_id` CHAR(36) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `type` ENUM('warehouse','transportation','other') DEFAULT 'warehouse',
  `due_date` DATETIME NOT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `payment_method` VARCHAR(100) DEFAULT NULL,
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `days_overdue` INT DEFAULT 0,
  `bank_account_id` CHAR(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_contract_id` (`contract_id`),
  KEY `idx_payments_bank_account_id` (`bank_account_id`),
  CONSTRAINT `fk_payments_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- items
CREATE TABLE IF NOT EXISTS `items` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `room_id` CHAR(36) NOT NULL,
  `contract_id` CHAR(36) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `quantity` INT DEFAULT NULL,
  `unit` VARCHAR(50) DEFAULT NULL,
  `condition` VARCHAR(50) DEFAULT 'new',
  `photo_url` TEXT DEFAULT NULL,
  `stored_at` DATETIME DEFAULT NULL,
  `removed_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_room_id` (`room_id`),
  KEY `idx_items_contract_id` (`contract_id`),
  CONSTRAINT `fk_items_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `user_id` CHAR(36) DEFAULT NULL,
  `customer_id` CHAR(36) DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_customer_id` (`customer_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- company_mail_settings, company_sms_settings, company_paytr_settings
CREATE TABLE IF NOT EXISTS `company_mail_settings` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `smtp_host` VARCHAR(255) DEFAULT NULL,
  `smtp_port` INT DEFAULT NULL,
  `smtp_secure` TINYINT(1) DEFAULT 0,
  `smtp_username` VARCHAR(255) DEFAULT NULL,
  `smtp_password` VARCHAR(255) DEFAULT NULL,
  `from_email` VARCHAR(255) DEFAULT NULL,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `contract_created_template` TEXT DEFAULT NULL,
  `payment_received_template` TEXT DEFAULT NULL,
  `contract_expiring_template` TEXT DEFAULT NULL,
  `payment_reminder_template` TEXT DEFAULT NULL,
  `welcome_template` TEXT DEFAULT NULL,
  `notify_customer_on_contract` TINYINT(1) DEFAULT 1,
  `notify_customer_on_payment` TINYINT(1) DEFAULT 1,
  `notify_customer_on_overdue` TINYINT(1) DEFAULT 1,
  `notify_admin_on_contract` TINYINT(1) DEFAULT 1,
  `notify_admin_on_payment` TINYINT(1) DEFAULT 1,
  `admin_contract_created_template` TEXT DEFAULT NULL,
  `admin_payment_received_template` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mail_company` (`company_id`),
  CONSTRAINT `fk_mail_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_sms_settings` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `username` VARCHAR(255) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `sender_id` VARCHAR(50) DEFAULT NULL,
  `api_url` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 0,
  `test_mode` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_company` (`company_id`),
  CONSTRAINT `fk_sms_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_paytr_settings` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `merchant_id` VARCHAR(255) DEFAULT NULL,
  `merchant_key` VARCHAR(255) DEFAULT NULL,
  `merchant_salt` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 0,
  `test_mode` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paytr_company` (`company_id`),
  CONSTRAINT `fk_paytr_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transportation_jobs
CREATE TABLE IF NOT EXISTS `transportation_jobs` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `customer_id` CHAR(36) NOT NULL,
  `pickup_province` VARCHAR(100) DEFAULT NULL,
  `pickup_district` VARCHAR(100) DEFAULT NULL,
  `pickup_neighborhood` VARCHAR(100) DEFAULT NULL,
  `pickup_floor_status` VARCHAR(50) DEFAULT NULL,
  `pickup_elevator_status` VARCHAR(50) DEFAULT NULL,
  `pickup_room_count` INT DEFAULT NULL,
  `pickup_address` TEXT DEFAULT NULL,
  `delivery_province` VARCHAR(100) DEFAULT NULL,
  `delivery_district` VARCHAR(100) DEFAULT NULL,
  `delivery_neighborhood` VARCHAR(100) DEFAULT NULL,
  `delivery_floor_status` VARCHAR(50) DEFAULT NULL,
  `delivery_elevator_status` VARCHAR(50) DEFAULT NULL,
  `delivery_room_count` INT DEFAULT NULL,
  `delivery_address` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL,
  `vat_rate` DECIMAL(5,2) DEFAULT 20.00,
  `price_includes_vat` TINYINT(1) DEFAULT 0,
  `contract_pdf_url` TEXT DEFAULT NULL,
  `job_type` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `job_date` DATETIME DEFAULT NULL,
  `is_paid` TINYINT(1) DEFAULT 0,
  `vehicle_plate` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tj_company_id` (`company_id`),
  KEY `idx_tj_customer_id` (`customer_id`),
  CONSTRAINT `fk_tj_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tj_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transportation_job_staff
CREATE TABLE IF NOT EXISTS `transportation_job_staff` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `transportation_job_id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tjs_job_id` (`transportation_job_id`),
  KEY `idx_tjs_user_id` (`user_id`),
  CONSTRAINT `fk_tjs_job` FOREIGN KEY (`transportation_job_id`) REFERENCES `transportation_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tjs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vehicles (araçlar: plaka, kasko/muayene tarihi, kasa m³)
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

-- vehicle_traffic_insurances
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

-- vehicle_kaskos
CREATE TABLE IF NOT EXISTS `vehicle_kaskos` (
  `id` CHAR(36) NOT NULL,
  `vehicle_id` CHAR(36) NOT NULL,
  `policy_number` VARCHAR(100) DEFAULT NULL,
  `insurer_name` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `premium_amount` DECIMAL(12,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vk_vehicle_id` (`vehicle_id`),
  KEY `idx_vk_deleted_at` (`deleted_at`),
  KEY `idx_vk_end_date` (`end_date`),
  CONSTRAINT `fk_vk_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vehicle_accidents
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

-- vehicle_traffic_insurance_documents
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

-- vehicle_kasko_documents
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

-- vehicle_accident_documents
CREATE TABLE IF NOT EXISTS `vehicle_accident_documents` (
  `id` CHAR(36) NOT NULL,
  `accident_id` CHAR(36) NOT NULL,
  `document_kind` VARCHAR(30) NOT NULL DEFAULT 'diger',
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

-- service_categories
CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sc_company_id` (`company_id`),
  CONSTRAINT `fk_sc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- services
CREATE TABLE IF NOT EXISTS `services` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `category_id` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `unit_price` DECIMAL(10,2) DEFAULT 0,
  `unit` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_services_company_id` (`company_id`),
  KEY `idx_services_category_id` (`category_id`),
  CONSTRAINT `fk_services_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- proposals
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `customer_id` CHAR(36) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'draft',
  `total_amount` DECIMAL(10,2) DEFAULT 0,
  `currency` VARCHAR(10) DEFAULT 'TRY',
  `valid_until` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `transport_terms` TEXT DEFAULT NULL,
  `pickup_address` TEXT DEFAULT NULL,
  `delivery_address` TEXT DEFAULT NULL,
  `pdf_url` VARCHAR(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proposals_company_id` (`company_id`),
  KEY `idx_proposals_customer_id` (`customer_id`),
  CONSTRAINT `fk_proposals_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_proposals_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- proposal_items
CREATE TABLE IF NOT EXISTS `proposal_items` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `proposal_id` CHAR(36) NOT NULL,
  `service_id` CHAR(36) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pi_proposal_id` (`proposal_id`),
  KEY `idx_pi_service_id` (`service_id`),
  CONSTRAINT `fk_pi_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
