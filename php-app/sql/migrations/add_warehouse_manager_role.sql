-- Depo Sorumlusu kullanıcı rolü
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM(
    'super_admin',
    'company_owner',
    'company_staff',
    'data_entry',
    'accounting',
    'warehouse_manager',
    'customer'
  ) DEFAULT 'customer';
