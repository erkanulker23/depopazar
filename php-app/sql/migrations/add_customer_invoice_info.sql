-- Müşteri: Fatura bilgisi (opsiyonel, fatura kesiminde kullanılır)
ALTER TABLE `customers` ADD COLUMN `invoice_info` TEXT DEFAULT NULL COMMENT 'Fatura unvanı, vergi no, vergi dairesi vb.' AFTER `notes`;
