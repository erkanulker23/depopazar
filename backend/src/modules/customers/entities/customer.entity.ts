import { Entity, Column, OneToMany, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Contract } from '../../contracts/entities/contract.entity';
import { User } from '../../users/entities/user.entity';
import { Company } from '../../companies/entities/company.entity';

@Entity('customers')
export class Customer extends BaseEntity {
  @Column({ type: 'uuid', nullable: true })
  user_id: string | null; // If customer has user account

  @ManyToOne(() => User, { nullable: true })
  @JoinColumn({ name: 'user_id' })
  user: User | null;

  @Column({ type: 'uuid' })
  company_id: string; // Which company this customer belongs to

  @ManyToOne(() => Company, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'company_id' })
  company: Company;

  @Column({ type: 'varchar', length: 100 })
  first_name: string;

  @Column({ type: 'varchar', length: 100 })
  last_name: string;

  @Column({ type: 'varchar', length: 255 })
  email: string;

  @Column({ type: 'varchar', length: 20, nullable: true })
  phone: string | null;

  @Column({ type: 'varchar', length: 20, nullable: true })
  identity_number: string | null; // TC Kimlik No

  @Column({ type: 'text', nullable: true })
  address: string | null;

  @Column({ type: 'text', nullable: true })
  notes: string | null;

  @Column({ type: 'boolean', default: true })
  is_active: boolean;

  // Relations
  @OneToMany(() => Contract, (contract) => contract.customer)
  contracts: Contract[];
}
