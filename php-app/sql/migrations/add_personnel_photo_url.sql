ALTER TABLE `personnel`
  ADD COLUMN `photo_url` TEXT DEFAULT NULL
  COMMENT 'Profil fotoğrafı (/uploads/personnel/...)'
  AFTER `phone`;
