import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Proposal } from './proposal.entity';
import { Service } from '../../services/entities/service.entity';

@Entity('proposal_items')
export class ProposalItem extends BaseEntity {
  @Column({ type: 'uuid' })
  proposal_id: string;

  @ManyToOne(() => Proposal, (proposal) => proposal.items, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'proposal_id' })
  proposal: Proposal;

  @Column({ type: 'uuid', nullable: true })
  service_id: string | null;

  @ManyToOne(() => Service, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn({ name: 'service_id' })
  service: Service | null;

  @Column({ type: 'varchar', length: 255 })
  name: string; // Service name or custom item name

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  quantity: number;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  unit_price: number;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  total_price: number;
}
