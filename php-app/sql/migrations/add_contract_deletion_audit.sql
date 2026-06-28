-- Sözleşme silme gerekçesi ve silen kullanıcı

DELIMITER //
DROP PROCEDURE IF EXISTS add_contract_deletion_audit//
CREATE PROCEDURE add_contract_deletion_audit()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'deletion_reason'
  ) THEN
    ALTER TABLE `contracts`
      ADD COLUMN `deletion_reason` TEXT DEFAULT NULL COMMENT 'Silme gerekçesi' AFTER `terminated_at`;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'deleted_by_user_id'
  ) THEN
    ALTER TABLE `contracts`
      ADD COLUMN `deleted_by_user_id` CHAR(36) DEFAULT NULL AFTER `deletion_reason`;
  END IF;
END//
DELIMITER ;
CALL add_contract_deletion_audit();
DROP PROCEDURE IF EXISTS add_contract_deletion_audit;
