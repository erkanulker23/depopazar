-- Çıkış belgesi oluşturulma zamanı
SET @db := DATABASE();

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'exit_document_at'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `contracts` ADD COLUMN `exit_document_at` DATETIME DEFAULT NULL COMMENT ''Çıkış belgesi oluşturulma zamanı'' AFTER `terminated_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
