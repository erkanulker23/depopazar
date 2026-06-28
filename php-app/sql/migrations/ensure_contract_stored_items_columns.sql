-- Eksik kaldıysa contracts ürün durumu kolonlarını ekle (DELIMITER migration atlanmış olabilir)
ALTER TABLE `contracts`
  ADD COLUMN `stored_items_condition` ENUM('sifir','paketlenmis','ikinci_el','hasarli') DEFAULT NULL
  COMMENT 'Giriş yapılan ürün durumu' AFTER `notes`,
  ADD COLUMN `stored_items_condition_note` TEXT DEFAULT NULL
  COMMENT 'Hasarlı seçildiğinde hasar açıklaması' AFTER `stored_items_condition`;
