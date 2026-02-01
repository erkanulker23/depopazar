import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { TransportationJob } from './entities/transportation-job.entity';
import { TransportationJobStaff } from './entities/transportation-job-staff.entity';
import { TransportationJobsService } from './transportation-jobs.service';
import { TransportationJobsController } from './transportation-jobs.controller';
import { CompaniesModule } from '../companies/companies.module';
import { CustomersModule } from '../customers/customers.module';
import { NotificationsModule } from '../notifications/notifications.module';
import { UsersModule } from '../users/users.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([TransportationJob, TransportationJobStaff]),
    CompaniesModule,
    CustomersModule,
    NotificationsModule,
    UsersModule,
  ],
  controllers: [TransportationJobsController],
  providers: [TransportationJobsService],
  exports: [TransportationJobsService],
})
export class TransportationJobsModule {}
