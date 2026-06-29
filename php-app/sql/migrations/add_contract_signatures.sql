-- Sözleşme e-imza (müşteri + firma)

ALTER TABLE `contracts`
  ADD COLUMN `customer_signature_url` VARCHAR(512) DEFAULT NULL AFTER `contract_pdf_url`,
  ADD COLUMN `company_signature_url` VARCHAR(512) DEFAULT NULL AFTER `customer_signature_url`,
  ADD COLUMN `customer_signed_at` DATETIME DEFAULT NULL AFTER `company_signature_url`,
  ADD COLUMN `company_signed_at` DATETIME DEFAULT NULL AFTER `customer_signed_at`;
