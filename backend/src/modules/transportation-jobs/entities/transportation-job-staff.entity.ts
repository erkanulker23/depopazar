import { Entity, Column, ManyToOne, JoinColumn } from 'typeorm';
import { BaseEntity } from '../../../common/entities/base.entity';
import { TransportationJob } from './transportation-job.entity';
import { User } from '../../users/entities/user.entity';

@Entity('transportation_job_staff')
export class TransportationJobStaff extends BaseEntity {
  @Column({ type: 'uuid' })
  transportation_job_id: string;

  @ManyToOne(() => TransportationJob)
  @JoinColumn({ name: 'transportation_job_id' })
  transportation_job: TransportationJob;

  @Column({ type: 'uuid' })
  user_id: string;

  @ManyToOne(() => User)
  @JoinColumn({ name: 'user_id' })
  user: User;
}
