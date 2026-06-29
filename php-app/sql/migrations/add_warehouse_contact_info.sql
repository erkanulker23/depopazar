-- Depo iletişim bilgileri (telefon, WhatsApp, e-posta, web sitesi)

ALTER TABLE `warehouses`
  ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `description`,
  ADD COLUMN `whatsapp_number` VARCHAR(20) DEFAULT NULL AFTER `phone`,
  ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `whatsapp_number`,
  ADD COLUMN `website` VARCHAR(512) DEFAULT NULL AFTER `email`;
