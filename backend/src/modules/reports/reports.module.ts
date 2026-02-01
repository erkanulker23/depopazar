import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ReportsService } from './reports.service';
import { ReportsController } from './reports.controller';
import { Warehouse } from '../warehouses/entities/warehouse.entity';
import { Room } from '../rooms/entities/room.entity';
import { Payment } from '../payments/entities/payment.entity';
import { Contract } from '../contracts/entities/contract.entity';
import { BankAccount } from '../companies/entities/bank-account.entity';
import { CompaniesModule } from '../companies/companies.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Warehouse, Room, Payment, Contract, BankAccount]),
    CompaniesModule,
  ],
  controllers: [ReportsController],
  providers: [ReportsService],
  exports: [ReportsService],
})
export class ReportsModule {}
