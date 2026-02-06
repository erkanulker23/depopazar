-- Nakliye işine bağlı masraflar için expenses tablosuna transportation_job_id ekler.
-- Bir nakliye işi için yapılan tüm masraflar girilir, kar/zarar hesaplanır.
-- Çalıştırmak: php scripts/run-migrations.php (veya php-app kökünden: php scripts/run-migrations.php)
-- Not: Bu migration yalnızca bir kez çalıştırılmalıdır; kolon zaten varsa "Duplicate column" hatası alırsınız.

ALTER TABLE `expenses`
  ADD COLUMN `transportation_job_id` CHAR(36) DEFAULT NULL COMMENT 'Nakliye işi (varsa)' AFTER `created_by_user_id`,
  ADD KEY `idx_expenses_transportation_job_id` (`transportation_job_id`),
  ADD CONSTRAINT `fk_expenses_transportation_job` FOREIGN KEY (`transportation_job_id`) REFERENCES `transportation_jobs` (`id`) ON DELETE SET NULL;
