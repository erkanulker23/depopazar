ALTER TABLE `users`
  ADD COLUMN `managed_warehouse_id` CHAR(36) DEFAULT NULL
  COMMENT 'Depo sorumlusu rolünde sorumlu olunan depo'
  AFTER `role`,
  ADD KEY `idx_users_managed_warehouse_id` (`managed_warehouse_id`),
  ADD CONSTRAINT `fk_users_managed_warehouse` FOREIGN KEY (`managed_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;
