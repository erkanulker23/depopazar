-- proposals tablosuna alış/teslim adresi sütunları (tek teklif detay formu için)
-- Çalıştırmak: mysql -u user -p database < add_proposal_addresses.sql

SET NAMES utf8mb4;

ALTER TABLE `proposals`
  ADD COLUMN `pickup_address` TEXT DEFAULT NULL AFTER `notes`,
  ADD COLUMN `delivery_address` TEXT DEFAULT NULL AFTER `pickup_address`;
