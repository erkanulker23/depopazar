-- Müşteriyi ekleyen personel

SET @add_customer_created_by = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `customers` ADD COLUMN `created_by_user_id` CHAR(36) DEFAULT NULL COMMENT ''Müşteriyi ekleyen personel'' AFTER `user_id`, ADD KEY `idx_customers_created_by` (`created_by_user_id`), ADD CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'created_by_user_id'
);
PREPARE stmt_customer_created_by FROM @add_customer_created_by;
EXECUTE stmt_customer_created_by;
DEALLOCATE PREPARE stmt_customer_created_by;
