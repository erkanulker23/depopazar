import { Injectable, NotFoundException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Customer } from './entities/customer.entity';
import { TransportationJob } from '../transportation-jobs/entities/transportation-job.entity';
import { PaymentStatus } from '../../common/enums/payment-status.enum';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';
import {
  type PaginationParams,
  type PaginatedResult,
  parsePagination,
  toPaginatedResult,
} from '../../common/utils/pagination';

@Injectable()
export class CustomersService {
  private readonly logger = new Logger(CustomersService.name);

  constructor(
    @InjectRepository(Customer)
    private readonly customersRepository: Repository<Customer>,
    @InjectRepository(TransportationJob)
    private readonly transportationJobsRepository: Repository<TransportationJob>,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
  ) {}

  async create(createCustomerDto: Partial<Customer>): Promise<Customer> {
    const customer = this.customersRepository.create(createCustomerDto);
    const savedCustomer = await this.customersRepository.save(customer);

    // Bildirim oluştur: Yeni müşteri eklendiğinde
    try {
      const usersToNotify: Array<{ id: string; email: string }> = [];
      if (savedCustomer.company_id) {
        const companyUsers = await this.usersService.findByCompanyId(savedCustomer.company_id);
        usersToNotify.push(...companyUsers);
      }
      const superAdmins = await this.usersService.findAllSuperAdmins();
      usersToNotify.push(...superAdmins);
      for (const user of usersToNotify) {
        try {
          await this.notificationsService.create({
            user_id: user.id,
            customer_id: savedCustomer.id,
            type: NotificationType.CUSTOMER_CREATED,
            title: 'Yeni Müşteri Eklendi',
            message: `${savedCustomer.first_name} ${savedCustomer.last_name} adlı yeni müşteri sisteme eklendi.`,
            is_read: false,
            metadata: {
              customer_id: savedCustomer.id,
              customer_name: `${savedCustomer.first_name} ${savedCustomer.last_name}`,
            },
          });
        } catch (error: unknown) {
          this.logger.warn(`Notification failed for user ${user.id}: ${error instanceof Error ? error.message : String(error)}`);
        }
      }
    } catch (error: unknown) {
      this.logger.warn('Notification creation failed', error instanceof Error ? error.message : String(error));
    }

    return savedCustomer;
  }

  async findAll(companyId?: string): Promise<Customer[]> {
    const where = companyId ? { company_id: companyId } : {};
    return this.customersRepository.find({
      where,
      relations: ['user', 'contracts', 'contracts.payments', 'contracts.room'],
    });
  }

  async findAllPaginated(
    companyId: string,
    params: PaginationParams,
  ): Promise<PaginatedResult<Customer>> {
    const where = { company_id: companyId };
    const [data, total] = await this.customersRepository.findAndCount({
      where,
      relations: ['user', 'contracts', 'contracts.payments', 'contracts.room'],
      skip: params.skip,
      take: params.take,
      order: { created_at: 'DESC' },
    });
    return toPaginatedResult(data, total, params);
  }

  async findOne(id: string): Promise<any> {
    const customer = await this.customersRepository.findOne({
      where: { id },
      relations: ['user', 'contracts', 'contracts.payments', 'contracts.room'],
    });

    if (!customer) {
      throw new NotFoundException('Müşteri bulunamadı');
    }

    // Calculate debt information
    const debtInfo = this.calculateDebtInfo(customer);

    // Return customer with debt information
    return {
      ...customer,
      debtInfo,
    };
  }

  private calculateDebtInfo(customer: Customer): any {
    const activeContracts = customer.contracts?.filter((c) => c.is_active) || [];
    const allPayments = activeContracts.flatMap((contract) => contract.payments || []);

    // Get unpaid payments (pending or overdue)
    const unpaidPayments = allPayments.filter(
      (payment) => payment.status === PaymentStatus.PENDING || payment.status === PaymentStatus.OVERDUE,
    );

    if (unpaidPayments.length === 0) {
      return {
        hasDebt: false,
        totalDebt: 0,
        firstDebtMonth: null,
        unpaidMonths: [],
      };
    }

    // Calculate total debt
    const totalDebt = unpaidPayments.reduce((sum, payment) => sum + Number(payment.amount), 0);

    // Extract months from unpaid payments
    const unpaidMonths = unpaidPayments.map((payment) => {
      const dueDate = new Date(payment.due_date);
      return {
        year: dueDate.getFullYear(),
        month: dueDate.getMonth() + 1, // JavaScript months are 0-indexed
        monthName: dueDate.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' }),
        amount: Number(payment.amount),
        status: payment.status,
        dueDate: payment.due_date,
        paymentNumber: payment.payment_number,
      };
    });

    // Sort by date to find the earliest debt month
    unpaidMonths.sort((a, b) => {
      if (a.year !== b.year) return a.year - b.year;
      return a.month - b.month;
    });

    const firstDebtMonth = unpaidMonths.length > 0 ? unpaidMonths[0] : null;

    // Get unique months (year-month combinations)
    const uniqueMonths = Array.from(
      new Set(unpaidMonths.map((m) => `${m.year}-${String(m.month).padStart(2, '0')}`)),
    ).map((key) => {
      const [year, month] = key.split('-').map(Number);
      const monthData = unpaidMonths.find((m) => m.year === year && m.month === month);
      return {
        year,
        month,
        monthName: monthData?.monthName || `${month}/${year}`,
        totalAmount: unpaidMonths
          .filter((m) => m.year === year && m.month === month)
          .reduce((sum, m) => sum + m.amount, 0),
      };
    });

    return {
      hasDebt: true,
      totalDebt,
      firstDebtMonth: firstDebtMonth
        ? {
            year: firstDebtMonth.year,
            month: firstDebtMonth.month,
            monthName: firstDebtMonth.monthName,
          }
        : null,
      unpaidMonths: uniqueMonths,
      unpaidPaymentsCount: unpaidPayments.length,
    };
  }

  async update(id: string, updateData: Partial<Customer>): Promise<Customer> {
    await this.customersRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    // Önce bu müşteriye bağlı nakliye işlerini sil (FK constraint hatasını önlemek için)
    await this.transportationJobsRepository.delete({ customer_id: id });
    await this.customersRepository.delete(id);
  }
}
