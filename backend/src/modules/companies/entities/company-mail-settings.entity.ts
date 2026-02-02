import { Entity, Column, OneToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Company } from './company.entity';

@Entity('company_mail_settings')
export class CompanyMailSettings extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @OneToOne(() => Company)
  @JoinColumn({ name: 'company_id' })
  company: Company;

  // SMTP Settings
  @Column({ type: 'varchar', length: 255, nullable: true })
  smtp_host: string | null;

  @Column({ type: 'int', nullable: true })
  smtp_port: number | null;

  @Column({ type: 'boolean', default: false })
  smtp_secure: boolean; // true for 465, false for other ports

  @Column({ type: 'varchar', length: 255, nullable: true })
  smtp_username: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  smtp_password: string | null;

  // From Settings
  @Column({ type: 'varchar', length: 255, nullable: true })
  from_email: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  from_name: string | null;

  // Mail Templates
  @Column({ type: 'text', nullable: true })
  contract_created_template: string | null;

  @Column({ type: 'text', nullable: true })
  payment_received_template: string | null;

  @Column({ type: 'text', nullable: true })
  contract_expiring_template: string | null;

  @Column({ type: 'text', nullable: true })
  payment_reminder_template: string | null;

  @Column({ type: 'text', nullable: true })
  welcome_template: string | null;

  // Customer Notification Toggles
  @Column({ type: 'boolean', default: true })
  notify_customer_on_contract: boolean;

  @Column({ type: 'boolean', default: true })
  notify_customer_on_payment: boolean;

  @Column({ type: 'boolean', default: true })
  notify_customer_on_overdue: boolean;

  // Admin Notification Toggles
  @Column({ type: 'boolean', default: true })
  notify_admin_on_contract: boolean;

  @Column({ type: 'boolean', default: true })
  notify_admin_on_payment: boolean;

  // Admin Notification Templates
  @Column({ type: 'text', nullable: true })
  admin_contract_created_template: string | null;

  @Column({ type: 'text', nullable: true })
  admin_payment_received_template: string | null;

  @Column({ type: 'boolean', default: true })
  is_active: boolean;
}
