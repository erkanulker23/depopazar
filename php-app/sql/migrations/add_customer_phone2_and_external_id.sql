-- Müşteri: 2. telefon alanı + Excel eşleşmesi için external_id (bir kez çalıştırın)
ALTER TABLE `customers` ADD COLUMN `phone_2` VARCHAR(20) DEFAULT NULL AFTER `phone`;
ALTER TABLE `customers` ADD COLUMN `external_id` VARCHAR(100) DEFAULT NULL COMMENT 'Excel eşleşme ID' AFTER `identity_number`;
CREATE INDEX `idx_customers_external_id` ON `customers` (`company_id`, `external_id`);
