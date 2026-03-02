-- Depo teklifinde hangi depo için teklif verildiğini tutar (sadece proposal_type = 'depo' iken dolu)
ALTER TABLE `proposals`
  ADD COLUMN `warehouse_id` CHAR(36) DEFAULT NULL AFTER `proposal_type`,
  ADD KEY `idx_proposals_warehouse_id` (`warehouse_id`),
  ADD CONSTRAINT `fk_proposals_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;
