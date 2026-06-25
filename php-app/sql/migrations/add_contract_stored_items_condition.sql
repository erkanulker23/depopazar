-- Depo girişinde girilen eşyanın durumu (sıfır, paketlenmiş, ikinci el, hasarlı)
DELIMITER //
DROP PROCEDURE IF EXISTS add_contract_stored_items_condition//
CREATE PROCEDURE add_contract_stored_items_condition()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'stored_items_condition'
  ) THEN
    ALTER TABLE `contracts`
      ADD COLUMN `stored_items_condition` ENUM('sifir','paketlenmis','ikinci_el','hasarli') DEFAULT NULL
      COMMENT 'Giriş yapılan ürün durumu' AFTER `notes`;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'stored_items_condition_note'
  ) THEN
    ALTER TABLE `contracts`
      ADD COLUMN `stored_items_condition_note` TEXT DEFAULT NULL
      COMMENT 'Hasarlı seçildiğinde hasar açıklaması' AFTER `stored_items_condition`;
  END IF;
END//
DELIMITER ;
CALL add_contract_stored_items_condition();
DROP PROCEDURE IF EXISTS add_contract_stored_items_condition;
