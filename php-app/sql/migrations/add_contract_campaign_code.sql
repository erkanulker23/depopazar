-- Sözleşme kampanya kodu (6+1 veya 12+1 ücretsiz ay)

SET @add_campaign_col = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `contracts` ADD COLUMN `campaign_code` VARCHAR(20) DEFAULT NULL COMMENT ''Kampanya: 6_plus_1, 12_plus_1'' AFTER `discount`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'campaign_code'
);
PREPARE stmt_campaign_col FROM @add_campaign_col;
EXECUTE stmt_campaign_col;
DEALLOCATE PREPARE stmt_campaign_col;
