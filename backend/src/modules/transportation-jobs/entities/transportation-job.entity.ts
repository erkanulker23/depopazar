import { Entity, Column, ManyToOne, OneToMany, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Customer } from '../../customers/entities/customer.entity';
import { User } from '../../users/entities/user.entity';
import { TransportationJobStaff } from './transportation-job-staff.entity';

@Entity('transportation_jobs')
export class TransportationJob extends BaseEntity {
  @Column({ type: 'uuid' })
  company_id: string;

  @Column({ type: 'uuid' })
  customer_id: string;

  @ManyToOne(() => Customer, { nullable: false })
  @JoinColumn({ name: 'customer_id' })
  customer: Customer;

  // Eşya Alındığı Yer
  @Column({ type: 'varchar', length: 100, nullable: true })
  pickup_province: string | null; // İl

  @Column({ type: 'varchar', length: 100, nullable: true })
  pickup_district: string | null; // İlçe

  @Column({ type: 'varchar', length: 100, nullable: true })
  pickup_neighborhood: string | null; // Mahalle

  @Column({ type: 'varchar', length: 50, nullable: true })
  pickup_floor_status: string | null; // Kat Durumu (örn: "Zemin", "1. Kat", "2. Kat", "Asansörlü", "Asansörsüz")

  @Column({ type: 'varchar', length: 50, nullable: true })
  pickup_elevator_status: string | null; // Asansör Durumu (örn: "Var", "Yok")

  @Column({ type: 'integer', nullable: true })
  pickup_room_count: number | null; // Oda Sayısı

  @Column({ type: 'text', nullable: true })
  pickup_address: string | null; // Açık Adres

  // Eşyanın Gittiği Adres
  @Column({ type: 'varchar', length: 100, nullable: true })
  delivery_province: string | null; // İl

  @Column({ type: 'varchar', length: 100, nullable: true })
  delivery_district: string | null; // İlçe

  @Column({ type: 'varchar', length: 100, nullable: true })
  delivery_neighborhood: string | null; // Mahalle

  @Column({ type: 'varchar', length: 50, nullable: true })
  delivery_floor_status: string | null; // Kat Durumu

  @Column({ type: 'varchar', length: 50, nullable: true })
  delivery_elevator_status: string | null; // Asansör Durumu

  @Column({ type: 'integer', nullable: true })
  delivery_room_count: number | null; // Oda Sayısı

  @Column({ type: 'text', nullable: true })
  delivery_address: string | null; // Açık Adres

  // Fiyat
  @Column({ type: 'decimal', precision: 10, scale: 2, nullable: true })
  price: number | null;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 20.0 })
  vat_rate: number; // KDV Oranı (varsayılan %20)

  @Column({ type: 'boolean', default: false })
  price_includes_vat: boolean; // KDV Dahil mi?

  // PDF Sözleşme
  @Column({ type: 'text', nullable: true })
  contract_pdf_url: string | null; // PDF Sözleşme URL'i

  // İş Türü (Evden Eve, Ofis, vb.)
  @Column({ type: 'varchar', length: 100, nullable: true })
  job_type: string | null;

  // Not alanı
  @Column({ type: 'text', nullable: true })
  notes: string | null;

  // İş durumu
  @Column({ type: 'varchar', length: 50, default: 'pending' })
  status: string; // pending, in_progress, completed, cancelled

  // İş tarihi
  @Column({ type: 'datetime', nullable: true })
  job_date: Date | null;

  // Ödeme durumu
  @Column({ type: 'boolean', default: false })
  is_paid: boolean; // Ödeme alındı mı?

  // Relations
  @OneToMany(() => TransportationJobStaff, (staff) => staff.transportation_job)
  staff: TransportationJobStaff[];
}
