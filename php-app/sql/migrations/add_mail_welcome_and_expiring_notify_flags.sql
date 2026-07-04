-- Hoş geldin ve sözleşme bitiş hatırlatması bildirim tercihleri
SET @db := DATABASE();

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'company_mail_settings' AND COLUMN_NAME = 'notify_customer_on_welcome'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `company_mail_settings` ADD COLUMN `notify_customer_on_welcome` TINYINT(1) DEFAULT 1 AFTER `notify_customer_on_overdue`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'company_mail_settings' AND COLUMN_NAME = 'notify_customer_on_contract_expiring'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `company_mail_settings` ADD COLUMN `notify_customer_on_contract_expiring` TINYINT(1) DEFAULT 1 AFTER `notify_customer_on_welcome`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
