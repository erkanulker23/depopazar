-- Tahsilatı yapan saha personeli (payments.paid_by_personnel_id)
-- Idempotent: kolon zaten varsa tekrar eklenmez.

DELIMITER //
DROP PROCEDURE IF EXISTS add_payment_paid_by_personnel_id//
CREATE PROCEDURE add_payment_paid_by_personnel_id()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'paid_by_personnel_id'
  ) THEN
    ALTER TABLE `payments`
      ADD COLUMN `paid_by_personnel_id` CHAR(36) DEFAULT NULL COMMENT 'Tahsilatı yapan saha personeli' AFTER `paid_by_user_id`,
      ADD KEY `idx_payments_paid_by_personnel_id` (`paid_by_personnel_id`);
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel') THEN
      ALTER TABLE `payments`
        ADD CONSTRAINT `fk_payments_paid_by_personnel` FOREIGN KEY (`paid_by_personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL;
    END IF;
  END IF;
END//
DELIMITER ;
CALL add_payment_paid_by_personnel_id();
DROP PROCEDURE IF EXISTS add_payment_paid_by_personnel_id;
