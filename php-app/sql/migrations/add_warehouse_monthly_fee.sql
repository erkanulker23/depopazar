-- Depo düzenlemede aylık depo ücreti alanı
-- MySQL: IF NOT EXISTS sadece MySQL 8.0.12+ desteklenir
ALTER TABLE warehouses ADD COLUMN monthly_base_fee DECIMAL(10,2) DEFAULT NULL AFTER is_active;
