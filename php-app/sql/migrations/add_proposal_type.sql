-- Teklif türü: depo (Depo Teklifi) veya nakliye (Nakliye Teklifi)
ALTER TABLE `proposals`
  ADD COLUMN `proposal_type` VARCHAR(20) NOT NULL DEFAULT 'nakliye' AFTER `title`;
