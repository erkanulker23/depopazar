import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { CompaniesService } from './companies.service';
import { CompaniesController } from './companies.controller';
import { Company } from './entities/company.entity';
import { CompanyMailSettings } from './entities/company-mail-settings.entity';
import { CompanyPaytrSettings } from './entities/company-paytr-settings.entity';
import { CompanySmsSettings } from './entities/company-sms-settings.entity';
import { BankAccount } from './entities/bank-account.entity';
import { CompanyMailSettingsService } from './company-mail-settings.service';
import { CompanyPaytrSettingsService } from './company-paytr-settings.service';
import { CompanySmsSettingsService } from './company-sms-settings.service';
import { BankAccountsService } from './bank-accounts.service';
import { BankAccountsController } from './bank-accounts.controller';
import { MailService } from './mail.service';
import { SmsService } from './sms.service';

@Module({
  imports: [TypeOrmModule.forFeature([Company, CompanyMailSettings, CompanyPaytrSettings, CompanySmsSettings, BankAccount])],
  controllers: [CompaniesController, BankAccountsController],
  providers: [CompaniesService, CompanyMailSettingsService, CompanyPaytrSettingsService, CompanySmsSettingsService, BankAccountsService, MailService, SmsService],
  exports: [CompaniesService, CompanyMailSettingsService, CompanyPaytrSettingsService, CompanySmsSettingsService, BankAccountsService, MailService, SmsService],
})
export class CompaniesModule {}
