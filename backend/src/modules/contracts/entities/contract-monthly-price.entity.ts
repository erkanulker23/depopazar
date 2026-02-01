import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Contract } from './contract.entity';

@Entity('contract_monthly_prices')
export class ContractMonthlyPrice extends BaseEntity {
  @Column({ type: 'uuid' })
  contract_id: string;

  @ManyToOne(() => Contract, (contract) => contract.monthly_prices)
  @JoinColumn({ name: 'contract_id' })
  contract: Contract;

  @Column({ type: 'varchar', length: 7 }) // Format: "2024-01" (YYYY-MM)
  month: string;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  price: number;

  @Column({ type: 'text', nullable: true })
  notes: string | null;
}
