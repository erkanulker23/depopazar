import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ContractsService } from './contracts.service';
import { ContractsController } from './contracts.controller';
import { Contract } from './entities/contract.entity';
import { ContractMonthlyPrice } from './entities/contract-monthly-price.entity';
import { ContractStaff } from './entities/contract-staff.entity';
import { Payment } from '../payments/entities/payment.entity';
import { RoomsModule } from '../rooms/rooms.module';
import { NotificationsModule } from '../notifications/notifications.module';
import { UsersModule } from '../users/users.module';
import { CompaniesModule } from '../companies/companies.module';
import { CustomersModule } from '../customers/customers.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Contract, ContractMonthlyPrice, ContractStaff, Payment]),
    RoomsModule,
    NotificationsModule,
    UsersModule,
    CompaniesModule,
    CustomersModule,
  ],
  controllers: [ContractsController],
  providers: [ContractsService],
  exports: [ContractsService],
})
export class ContractsModule {}
