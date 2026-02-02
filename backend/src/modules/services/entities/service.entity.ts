import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Company } from '../../companies/entities/company.entity';
import { ServiceCategory } from './service-category.entity';

@Entity('services')
export class Service extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @ManyToOne(() => Company, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'company_id' })
  company: Company;

  @Column({ type: 'uuid' })
  category_id: string;

  @ManyToOne(() => ServiceCategory, (category) => category.services, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'category_id' })
  category: ServiceCategory;

  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ type: 'decimal', precision: 10, scale: 2, default: 0 })
  unit_price: number;

  @Column({ type: 'varchar', length: 50, nullable: true })
  unit: string | null; // e.g. "adet", "saat", "m2"
}
