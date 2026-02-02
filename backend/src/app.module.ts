import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { ThrottlerModule, ThrottlerGuard } from '@nestjs/throttler';
import { APP_GUARD } from '@nestjs/core';
import { join } from 'path';
import { DatabaseModule } from './database/database.module';
import { AuthModule } from './modules/auth/auth.module';
import { UsersModule } from './modules/users/users.module';
import { CompaniesModule } from './modules/companies/companies.module';
import { WarehousesModule } from './modules/warehouses/warehouses.module';
import { RoomsModule } from './modules/rooms/rooms.module';
import { CustomersModule } from './modules/customers/customers.module';
import { ContractsModule } from './modules/contracts/contracts.module';
import { PaymentsModule } from './modules/payments/payments.module';
import { ItemsModule } from './modules/items/items.module';
import { NotificationsModule } from './modules/notifications/notifications.module';
import { ReportsModule } from './modules/reports/reports.module';
import { TransportationJobsModule } from './modules/transportation-jobs/transportation-jobs.module';
import { ServicesModule } from './modules/services/services.module';
import { ProposalsModule } from './modules/proposals/proposals.module';
import { BackupModule } from './modules/backup/backup.module';

// Tek .env: her zaman proje kökü (backend/ ve frontend/ üst dizini)
const projectRoot = process.cwd().endsWith('backend') ? join(process.cwd(), '..') : process.cwd();
const singleEnvPath = join(projectRoot, '.env');

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: singleEnvPath,
    }),

    // Database
    DatabaseModule,

    // Rate limiting
    ThrottlerModule.forRoot([
      {
        ttl: 60000, // 1 minute
        limit: 100, // 100 requests per minute
      },
    ]),

    // Feature modules
    AuthModule,
    UsersModule,
    CompaniesModule,
    WarehousesModule,
    RoomsModule,
    CustomersModule,
    ContractsModule,
    PaymentsModule,
    ItemsModule,
    NotificationsModule,
    ReportsModule,
    TransportationJobsModule,
    ServicesModule,
    ProposalsModule,
    BackupModule,
  ],
  providers: [
    {
      provide: APP_GUARD,
      useClass: ThrottlerGuard,
    },
  ],
})
export class AppModule {}
