-- contract_monthly_prices.month: Y-m → Y-m-d (giriş tarihi yıl dönümü vade anahtarı)
DELIMITER //
DROP PROCEDURE IF EXISTS extend_contract_monthly_prices_month//
CREATE PROCEDURE extend_contract_monthly_prices_month()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_monthly_prices' AND COLUMN_NAME = 'month'
      AND CHARACTER_MAXIMUM_LENGTH < 10
  ) THEN
    ALTER TABLE `contract_monthly_prices` MODIFY COLUMN `month` VARCHAR(10) NOT NULL;
  END IF;
END//
DELIMITER ;
CALL extend_contract_monthly_prices_month();
DROP PROCEDURE IF EXISTS extend_contract_monthly_prices_month;
