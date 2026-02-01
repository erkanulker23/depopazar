import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Contract } from './contract.entity';
import { User } from '../../users/entities/user.entity';

@Entity('contract_staff')
export class ContractStaff extends BaseEntity {
  @Column({ type: 'uuid' })
  contract_id: string;

  @ManyToOne(() => Contract)
  @JoinColumn({ name: 'contract_id' })
  contract: Contract;

  @Column({ type: 'uuid' })
  user_id: string;

  @ManyToOne(() => User)
  @JoinColumn({ name: 'user_id' })
  user: User;
}
