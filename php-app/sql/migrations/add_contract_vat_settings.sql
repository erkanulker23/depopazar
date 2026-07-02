-- Şirket varsayılan KDV oranı ve sözleşme KDV alanları
-- Mevcut sözleşmeler price_includes_vat = 1 (KDV dahil) olarak kalır.

SET @add_company_vat = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `companies` ADD COLUMN `default_vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00 COMMENT ''Varsayılan KDV oranı (%)'' AFTER `tax_office`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'default_vat_rate'
);
PREPARE stmt_company_vat FROM @add_company_vat;
EXECUTE stmt_company_vat;
DEALLOCATE PREPARE stmt_company_vat;

SET @add_contract_vat_rate = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `contracts` ADD COLUMN `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00 COMMENT ''Sözleşme KDV oranı (%)'' AFTER `discount`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'vat_rate'
);
PREPARE stmt_contract_vat_rate FROM @add_contract_vat_rate;
EXECUTE stmt_contract_vat_rate;
DEALLOCATE PREPARE stmt_contract_vat_rate;

SET @add_contract_price_includes_vat = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `contracts` ADD COLUMN `price_includes_vat` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''1=KDV dahil, 0=KDV hariç'' AFTER `vat_rate`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'price_includes_vat'
);
PREPARE stmt_contract_price_includes_vat FROM @add_contract_price_includes_vat;
EXECUTE stmt_contract_price_includes_vat;
DEALLOCATE PREPARE stmt_contract_price_includes_vat;
