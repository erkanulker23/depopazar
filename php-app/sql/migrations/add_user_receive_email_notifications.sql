ALTER TABLE `users`
  ADD COLUMN `receive_email_notifications` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'Panel bildirim e-postaları alsın'
  AFTER `is_active`;
