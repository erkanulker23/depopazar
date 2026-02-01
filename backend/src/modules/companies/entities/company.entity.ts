import { Entity, Column, OneToMany } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { User } from '../../users/entities/user.entity';
import { Warehouse } from '../../warehouses/entities/warehouse.entity';
import { BankAccount } from './bank-account.entity';

@Entity('companies')
export class Company extends BaseEntity {
  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'varchar', length: 255, unique: true })
  slug: string; // URL-friendly company identifier

  /** Uygulama içinde görünen proje/marka adı (örn. sidebar, login). Boşsa "DepoPazar" kullanılır. */
  @Column({ type: 'varchar', length: 255, nullable: true })
  project_name: string | null;

  @Column({ type: 'varchar', length: 512, nullable: true })
  logo_url: string | null;

  @Column({ type: 'varchar', length: 100, nullable: true })
  primary_color: string | null;

  @Column({ type: 'varchar', length: 100, nullable: true })
  secondary_color: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  email: string | null;

  @Column({ type: 'varchar', length: 20, nullable: true })
  phone: string | null;

  @Column({ type: 'varchar', length: 20, nullable: true })
  whatsapp_number: string | null;

  @Column({ type: 'text', nullable: true })
  address: string | null;

  @Column({ type: 'varchar', length: 50, nullable: true })
  mersis_number: string | null;

  @Column({ type: 'varchar', length: 50, nullable: true })
  trade_registry_number: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  tax_office: string | null;

  @Column({ type: 'varchar', length: 50, default: 'basic' })
  package_type: string; // basic, premium, enterprise

  @Column({ type: 'integer', default: 0 })
  max_warehouses: number;

  @Column({ type: 'integer', default: 0 })
  max_rooms: number;

  @Column({ type: 'integer', default: 0 })
  max_customers: number;

  @Column({ type: 'boolean', default: true })
  is_active: boolean;

  @Column({ type: 'datetime', nullable: true })
  subscription_expires_at: Date | null;

  // Relations
  @OneToMany(() => User, (user) => user.company)
  users: User[];

  @OneToMany(() => Warehouse, (warehouse) => warehouse.company)
  warehouses: Warehouse[];

  @OneToMany(() => BankAccount, (bankAccount) => bankAccount.company)
  bankAccounts: BankAccount[];
}
