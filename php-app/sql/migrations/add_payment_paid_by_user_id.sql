-- Tahsilatı işleyen kullanıcı (payments.paid_by_user_id)
-- Idempotent: kolon zaten varsa tekrar eklenmez.

DELIMITER //
DROP PROCEDURE IF EXISTS add_payment_paid_by_user_id//
CREATE PROCEDURE add_payment_paid_by_user_id()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'paid_by_user_id'
  ) THEN
    ALTER TABLE `payments`
      ADD COLUMN `paid_by_user_id` CHAR(36) DEFAULT NULL COMMENT 'Tahsilatı işleyen kullanıcı' AFTER `bank_account_id`,
      ADD KEY `idx_payments_paid_by_user_id` (`paid_by_user_id`);
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users') THEN
      ALTER TABLE `payments`
        ADD CONSTRAINT `fk_payments_paid_by_user` FOREIGN KEY (`paid_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
    END IF;
  END IF;
END//
DELIMITER ;
CALL add_payment_paid_by_user_id();
DROP PROCEDURE IF EXISTS add_payment_paid_by_user_id;
