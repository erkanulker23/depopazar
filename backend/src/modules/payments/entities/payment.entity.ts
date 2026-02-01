import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { PaymentStatus } from '../../../common/enums/payment-status.enum';
import { Contract } from '../../contracts/entities/contract.entity';
import { BankAccount } from '../../companies/entities/bank-account.entity';

@Entity('payments')
export class Payment extends BaseEntity {
  @Column({ type: 'varchar', length: 100, unique: true })
  payment_number: string; // Örn: "PAY-2024-001"

  @Column({ type: 'uuid' })
  contract_id: string;

  @ManyToOne(() => Contract, (contract) => contract.payments)
  @JoinColumn({ name: 'contract_id' })
  contract: Contract;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  amount: number;

  @Column({
    type: 'enum',
    enum: PaymentStatus,
    default: PaymentStatus.PENDING,
  })
  status: PaymentStatus;

  @Column({ type: 'datetime' })
  due_date: Date;

  @Column({ type: 'datetime', nullable: true })
  paid_at: Date | null;

  @Column({ type: 'varchar', length: 100, nullable: true })
  payment_method: string | null; // credit_card, bank_transfer, cash, etc.

  @Column({ type: 'varchar', length: 255, nullable: true })
  transaction_id: string | null;

  @Column({ type: 'text', nullable: true })
  notes: string | null;

  @Column({ type: 'integer', default: 0 })
  days_overdue: number;

  @Column({ type: 'uuid', nullable: true })
  bank_account_id: string | null;

  @ManyToOne(() => BankAccount, { nullable: true })
  @JoinColumn({ name: 'bank_account_id' })
  bank_account: BankAccount | null;
}
