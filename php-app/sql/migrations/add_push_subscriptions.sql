-- Push abonelikleri: kullanıcı bildirimlere izin verdiğinde tarayıcı/cihaz subscription kaydedilir
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `endpoint` VARCHAR(512) NOT NULL,
  `p256dh_key` VARCHAR(255) NOT NULL,
  `auth_key` VARCHAR(255) NOT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_endpoint` (`endpoint`(191)),
  KEY `idx_push_subscriptions_user_id` (`user_id`),
  CONSTRAINT `fk_push_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
