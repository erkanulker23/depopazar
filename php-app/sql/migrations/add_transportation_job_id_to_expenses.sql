-- Nakliye işine bağlı masraflar için expenses tablosuna transportation_job_id ekler.
-- Idempotent: kolon zaten varsa tekrar eklenmez (birden fazla çalıştırma güvenli).
-- Çalıştırmak: php scripts/run-migrations.php veya deploy sırasında otomatik.

DELIMITER //
DROP PROCEDURE IF EXISTS add_transportation_job_id_to_expenses//
CREATE PROCEDURE add_transportation_job_id_to_expenses()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'transportation_job_id'
  ) THEN
    ALTER TABLE `expenses`
      ADD COLUMN `transportation_job_id` CHAR(36) DEFAULT NULL COMMENT 'Nakliye işi (varsa)' AFTER `created_by_user_id`,
      ADD KEY `idx_expenses_transportation_job_id` (`transportation_job_id`);
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transportation_jobs') THEN
      ALTER TABLE `expenses`
        ADD CONSTRAINT `fk_expenses_transportation_job` FOREIGN KEY (`transportation_job_id`) REFERENCES `transportation_jobs` (`id`) ON DELETE SET NULL;
    END IF;
  END IF;
END//
DELIMITER ;
CALL add_transportation_job_id_to_expenses();
DROP PROCEDURE IF EXISTS add_transportation_job_id_to_expenses;
