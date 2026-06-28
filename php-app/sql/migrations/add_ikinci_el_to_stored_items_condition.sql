-- Eski kurulumlarda stored_items_condition ENUM'una ikinci_el ekle
ALTER TABLE `contracts`
  MODIFY COLUMN `stored_items_condition` ENUM('sifir','paketlenmis','ikinci_el','hasarli') DEFAULT NULL
  COMMENT 'Giriş yapılan ürün durumu';
