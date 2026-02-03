-- Mevcut veritabanına vehicle_plate sütunu ekler (nakliye işleri – hangi plakalı araç gitti)
-- Çalıştırmak: mysql -u user -p database < add_vehicle_plate_to_transportation_jobs.sql
-- Sütun zaten varsa bu script hata verir; tekrar çalıştırmayın.

SET NAMES utf8mb4;

ALTER TABLE `transportation_jobs`
  ADD COLUMN `vehicle_plate` VARCHAR(20) DEFAULT NULL AFTER `is_paid`;
