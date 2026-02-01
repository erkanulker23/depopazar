import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { Room } from '../../rooms/entities/room.entity';
import { Contract } from '../../contracts/entities/contract.entity';

@Entity('items')
export class Item extends BaseEntity {
  @Column({ type: 'uuid' })
  room_id: string;

  @ManyToOne(() => Room, (room) => room.items)
  @JoinColumn({ name: 'room_id' })
  room: Room;

  @Column({ type: 'uuid', nullable: true })
  contract_id: string | null;

  @ManyToOne(() => Contract, { nullable: true })
  @JoinColumn({ name: 'contract_id' })
  contract: Contract | null;

  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ type: 'integer', nullable: true })
  quantity: number | null;

  @Column({ type: 'varchar', length: 50, nullable: true })
  unit: string | null; // adet, kutu, paket, etc.

  @Column({ type: 'varchar', length: 50, nullable: true, default: 'new' })
  condition: string | null; // new, used, packaged, damaged

  @Column({ type: 'text', nullable: true })
  photo_url: string | null; // JSON array of photo URLs

  @Column({ type: 'datetime', nullable: true })
  stored_at: Date | null; // When item was stored

  @Column({ type: 'datetime', nullable: true })
  removed_at: Date | null; // When item was removed

  @Column({ type: 'text', nullable: true })
  notes: string | null;
}
