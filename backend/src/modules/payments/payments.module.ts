import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { PaymentsService } from './payments.service';
import { PaymentsController } from './payments.controller';
import { PaytrCallbackController } from './paytr-callback.controller';
import { Payment } from './entities/payment.entity';
import { PaytrService } from './paytr.service';
import { CompaniesModule } from '../companies/companies.module';
import { CustomersModule } from '../customers/customers.module';
import { NotificationsModule } from '../notifications/notifications.module';
import { UsersModule } from '../users/users.module';
import { ContractsModule } from '../contracts/contracts.module';

@Module({
  imports: [TypeOrmModule.forFeature([Payment]), CompaniesModule, CustomersModule, NotificationsModule, UsersModule, ContractsModule],
  controllers: [PaymentsController, PaytrCallbackController],
  providers: [PaymentsService, PaytrService],
  exports: [PaymentsService, PaytrService],
})
export class PaymentsModule {}
