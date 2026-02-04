-- vehicle_plate alanını çoklu plaka için genişlet (virgülle ayrılmış)
-- Çalıştırmak: mysql -u user -p database < vehicle_plate_extend.sql

ALTER TABLE `transportation_jobs`
  MODIFY COLUMN `vehicle_plate` VARCHAR(500) DEFAULT NULL;
