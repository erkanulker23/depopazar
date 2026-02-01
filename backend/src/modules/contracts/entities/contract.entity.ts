import { Entity, Column, ManyToOne, OneToMany, JoinColumn, ManyToMany, JoinTable } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Customer } from '../../customers/entities/customer.entity';
import { Room } from '../../rooms/entities/room.entity';
import { Payment } from '../../payments/entities/payment.entity';
import { User } from '../../users/entities/user.entity';
import { ContractMonthlyPrice } from './contract-monthly-price.entity';
import { ContractStaff } from './contract-staff.entity';

@Entity('contracts')
export class Contract extends BaseEntity {
  @Column({ type: 'varchar', length: 100, unique: true })
  contract_number: string; // Örn: "CNT-2024-001"

  @Column({ type: 'uuid' })
  customer_id: string;

  @ManyToOne(() => Customer, (customer) => customer.contracts)
  @JoinColumn({ name: 'customer_id' })
  customer: Customer;

  @Column({ type: 'uuid' })
  room_id: string;

  @ManyToOne(() => Room, (room) => room.contracts)
  @JoinColumn({ name: 'room_id' })
  room: Room;

  @Column({ type: 'datetime' })
  start_date: Date;

  @Column({ type: 'datetime' })
  end_date: Date;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  monthly_price: number;

  @Column({ type: 'integer', default: 1 })
  payment_frequency_months: number; // 1 = monthly, 3 = quarterly, etc.

  @Column({ type: 'text', nullable: true })
  terms: string | null;

  @Column({ type: 'text', nullable: true })
  notes: string | null;

  @Column({ type: 'boolean', default: true })
  is_active: boolean;

  @Column({ type: 'datetime', nullable: true })
  terminated_at: Date | null;

  // Yeni alanlar: Satış bilgileri
  @Column({ type: 'decimal', precision: 10, scale: 2, nullable: true, default: 0 })
  transportation_fee: number | null; // Nakliye ücreti

  @Column({ type: 'varchar', length: 255, nullable: true })
  pickup_location: string | null; // Eşyanın alındığı yer

  @Column({ type: 'uuid', nullable: true })
  sold_by_user_id: string | null; // Satışı yapan kişi

  @ManyToOne(() => User, { nullable: true })
  @JoinColumn({ name: 'sold_by_user_id' })
  sold_by_user: User | null;

  // İndirim ve nakliye bilgileri
  @Column({ type: 'decimal', precision: 10, scale: 2, nullable: true, default: 0 })
  discount: number | null; // İndirim tutarı

  @Column({ type: 'varchar', length: 100, nullable: true })
  driver_name: string | null; // Şoför adı

  @Column({ type: 'varchar', length: 20, nullable: true })
  driver_phone: string | null; // Şoför telefonu

  @Column({ type: 'varchar', length: 20, nullable: true })
  vehicle_plate: string | null; // Araç plakası

  @Column({ type: 'text', nullable: true })
  contract_pdf_url: string | null; // PDF sözleşme dosyası URL'i

  // Relations
  @OneToMany(() => Payment, (payment) => payment.contract)
  payments: Payment[];

  @OneToMany(() => ContractMonthlyPrice, (monthlyPrice) => monthlyPrice.contract)
  monthly_prices: ContractMonthlyPrice[];

  @OneToMany(() => ContractStaff, (contractStaff) => contractStaff.contract)
  contract_staff: ContractStaff[];
}
