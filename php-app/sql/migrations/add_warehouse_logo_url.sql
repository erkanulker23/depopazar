-- Depo logosu (liste ve etiketlerde)

ALTER TABLE `warehouses`
  ADD COLUMN `logo_url` VARCHAR(512) DEFAULT NULL AFTER `name`;
