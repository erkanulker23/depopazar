import { Entity, Column, OneToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Company } from './company.entity';

@Entity('company_sms_settings')
export class CompanySmsSettings extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @OneToOne(() => Company)
  @JoinColumn({ name: 'company_id' })
  company: Company;

  // NetGSM API Settings
  @Column({ type: 'varchar', length: 255, nullable: true })
  username: string | null; // NetGSM API username

  @Column({ type: 'varchar', length: 255, nullable: true })
  password: string | null; // NetGSM API password

  @Column({ type: 'varchar', length: 50, nullable: true })
  sender_id: string | null; // SMS gönderen başlık (header)

  @Column({ type: 'varchar', length: 255, nullable: true })
  api_url: string | null; // NetGSM API endpoint URL (default: https://api.netgsm.com.tr)

  @Column({ type: 'boolean', default: false })
  is_active: boolean;

  @Column({ type: 'boolean', default: false })
  test_mode: boolean; // Test modu için
}
