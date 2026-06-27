-- Saha personeli (sisteme giriş yapmaz; şoför, taşımacı vb.)
CREATE TABLE IF NOT EXISTS `personnel` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `company_id` CHAR(36) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `job_type` VARCHAR(50) NOT NULL DEFAULT 'diger',
  `is_active` TINYINT(1) DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_personnel_company` (`company_id`),
  KEY `idx_personnel_job_type` (`job_type`),
  KEY `idx_personnel_deleted` (`deleted_at`),
  CONSTRAINT `fk_personnel_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transportation_job_personnel` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `transportation_job_id` CHAR(36) NOT NULL,
  `personnel_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tjp_job` (`transportation_job_id`),
  KEY `idx_tjp_personnel` (`personnel_id`),
  CONSTRAINT `fk_tjp_job` FOREIGN KEY (`transportation_job_id`) REFERENCES `transportation_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tjp_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contract_personnel` (
  `id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `contract_id` CHAR(36) NOT NULL,
  `personnel_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_contract` (`contract_id`),
  KEY `idx_cp_personnel` (`personnel_id`),
  CONSTRAINT `fk_cp_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eski kullanıcı atamalarını personel kaydına taşı (aynı UUID ile eşleme)
INSERT IGNORE INTO `personnel` (`id`, `company_id`, `first_name`, `last_name`, `phone`, `job_type`, `is_active`)
SELECT u.`id`, u.`company_id`, u.`first_name`, u.`last_name`, u.`phone`, 'diger', u.`is_active`
FROM `users` u
WHERE u.`deleted_at` IS NULL
  AND u.`company_id` IS NOT NULL
  AND u.`id` IN (
    SELECT `user_id` FROM `transportation_job_staff` WHERE `deleted_at` IS NULL
    UNION
    SELECT `user_id` FROM `contract_staff` WHERE `deleted_at` IS NULL
  );

INSERT IGNORE INTO `transportation_job_personnel` (`id`, `transportation_job_id`, `personnel_id`)
SELECT tjs.`id`, tjs.`transportation_job_id`, tjs.`user_id`
FROM `transportation_job_staff` tjs
INNER JOIN `personnel` p ON p.`id` = tjs.`user_id` AND p.`deleted_at` IS NULL
WHERE tjs.`deleted_at` IS NULL;

INSERT IGNORE INTO `contract_personnel` (`id`, `contract_id`, `personnel_id`)
SELECT cs.`id`, cs.`contract_id`, cs.`user_id`
FROM `contract_staff` cs
INNER JOIN `personnel` p ON p.`id` = cs.`user_id` AND p.`deleted_at` IS NULL
WHERE cs.`deleted_at` IS NULL;
