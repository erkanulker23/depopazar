import { Entity, Column, ManyToOne, OneToMany, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { RoomStatus } from '../../../common/enums/room-status.enum';
import { Warehouse } from '../../warehouses/entities/warehouse.entity';
import { Contract } from '../../contracts/entities/contract.entity';
import { Item } from '../../items/entities/item.entity';

@Entity('rooms')
export class Room extends BaseEntity {
  @Column({ type: 'varchar', length: 100 })
  room_number: string; // Örn: "A-101", "B-205"

  @Column({ type: 'uuid' })
  warehouse_id: string;

  @ManyToOne(() => Warehouse, (warehouse) => warehouse.rooms)
  @JoinColumn({ name: 'warehouse_id' })
  warehouse: Warehouse;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  area_m2: number;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  monthly_price: number;

  @Column({
    type: 'enum',
    enum: RoomStatus,
    default: RoomStatus.EMPTY,
  })
  status: RoomStatus;

  @Column({ type: 'varchar', length: 50, nullable: true })
  floor: string | null;

  @Column({ type: 'varchar', length: 50, nullable: true })
  block: string | null;

  @Column({ type: 'varchar', length: 50, nullable: true })
  corridor: string | null;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ type: 'text', nullable: true })
  notes: string | null;

  // Relations
  @OneToMany(() => Contract, (contract) => contract.room)
  contracts: Contract[];

  @OneToMany(() => Item, (item) => item.room)
  items: Item[];
}
