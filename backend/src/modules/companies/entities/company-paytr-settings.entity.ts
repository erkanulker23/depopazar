import { Entity, Column, OneToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Company } from './company.entity';

@Entity('company_paytr_settings')
export class CompanyPaytrSettings extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @OneToOne(() => Company)
  @JoinColumn({ name: 'company_id' })
  company: Company;

  // PayTR API Settings
  @Column({ type: 'varchar', length: 255, nullable: true })
  merchant_id: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  merchant_key: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  merchant_salt: string | null;

  @Column({ type: 'boolean', default: false })
  is_active: boolean;

  @Column({ type: 'boolean', default: false })
  test_mode: boolean; // Test modu için
}
