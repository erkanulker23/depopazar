ALTER TABLE `users`
  ADD COLUMN `photo_url` TEXT DEFAULT NULL
  COMMENT 'Profil fotoğrafı (/uploads/users/...)'
  AFTER `phone`;
