// Entity imports for TypeORM
import { Company } from '../modules/companies/entities/company.entity';
import { User } from '../modules/users/entities/user.entity';
import { Warehouse } from '../modules/warehouses/entities/warehouse.entity';
import { Room } from '../modules/rooms/entities/room.entity';
import { Customer } from '../modules/customers/entities/customer.entity';
import { Contract } from '../modules/contracts/entities/contract.entity';
import { ContractMonthlyPrice } from '../modules/contracts/entities/contract-monthly-price.entity';
import { ContractStaff } from '../modules/contracts/entities/contract-staff.entity';
import { Payment } from '../modules/payments/entities/payment.entity';
import { Item } from '../modules/items/entities/item.entity';
import { Notification } from '../modules/notifications/entities/notification.entity';
import { CompanyMailSettings } from '../modules/companies/entities/company-mail-settings.entity';
import { CompanyPaytrSettings } from '../modules/companies/entities/company-paytr-settings.entity';
import { CompanySmsSettings } from '../modules/companies/entities/company-sms-settings.entity';
import { BankAccount } from '../modules/companies/entities/bank-account.entity';
import { TransportationJob } from '../modules/transportation-jobs/entities/transportation-job.entity';
import { TransportationJobStaff } from '../modules/transportation-jobs/entities/transportation-job-staff.entity';
import { ServiceCategory } from '../modules/services/entities/service-category.entity';
import { Service } from '../modules/services/entities/service.entity';
import { Proposal } from '../modules/proposals/entities/proposal.entity';
import { ProposalItem } from '../modules/proposals/entities/proposal-item.entity';

export const entities = [
  Company,
  User,
  Warehouse,
  Room,
  Customer,
  Contract,
  ContractMonthlyPrice,
  ContractStaff,
  Payment,
  Item,
  Notification,
  CompanyMailSettings,
  CompanyPaytrSettings,
  CompanySmsSettings,
  BankAccount,
  TransportationJob,
  TransportationJobStaff,
  ServiceCategory,
  Service,
  Proposal,
  ProposalItem,
];
