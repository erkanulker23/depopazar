-- Ödeme kaynağına Nakit (işten alınan ödeme) ekler.
-- Nakliye masraflarında "nakit" seçilebilir.
-- Çalıştırmak: php scripts/run-migrations.php

ALTER TABLE `expenses`
  MODIFY COLUMN `payment_source_type` ENUM('bank_account','credit_card','nakit') NOT NULL;
