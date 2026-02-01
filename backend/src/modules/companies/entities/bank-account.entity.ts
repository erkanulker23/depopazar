import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Company } from './company.entity';

@Entity('bank_accounts')
export class BankAccount extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @ManyToOne(() => Company, (company) => company.bankAccounts)
  @JoinColumn({ name: 'company_id' })
  company: Company;

  @Column({ type: 'varchar', length: 255 })
  bank_name: string; // Banka adı (örn: Ziraat Bankası)

  @Column({ type: 'varchar', length: 255 })
  account_holder_name: string; // Hesap sahibi adı

  @Column({ type: 'varchar', length: 50 })
  account_number: string; // Hesap numarası

  @Column({ type: 'varchar', length: 34, nullable: true })
  iban: string | null; // IBAN (opsiyonel)

  @Column({ type: 'varchar', length: 255, nullable: true })
  branch_name: string | null; // Şube adı (opsiyonel)

  @Column({ type: 'boolean', default: true })
  is_active: boolean; // Aktif mi?
}
