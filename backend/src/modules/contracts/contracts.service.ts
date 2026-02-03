import { Injectable, NotFoundException, BadRequestException, ConflictException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Contract } from './entities/contract.entity';
import { ContractMonthlyPrice } from './entities/contract-monthly-price.entity';
import { ContractStaff } from './entities/contract-staff.entity';
import { RoomsService } from '../rooms/rooms.service';
import { RoomStatus } from '../../common/enums/room-status.enum';
import { Payment } from '../payments/entities/payment.entity';
import { PaymentStatus } from '../../common/enums/payment-status.enum';
import { PaymentType } from '../../common/enums/payment-type.enum';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';
import { CompanyMailSettingsService } from '../companies/company-mail-settings.service';
import { CompanySmsSettingsService } from '../companies/company-sms-settings.service';
import { MailService } from '../companies/mail.service';
import { SmsService } from '../companies/sms.service';
import { CompaniesService } from '../companies/companies.service';
import {
  type PaginationParams,
  type PaginatedResult,
  toPaginatedResult,
} from '../../common/utils/pagination';
import { CreateContractDto } from './dto/create-contract.dto';

/** Filtre: all | active | terminated */
export type ContractStatusFilter = 'all' | 'active' | 'terminated';
/** Filtre: all | has_payment | no_payment - en az bir ödeme yapılmış / hiç ödeme yok */
export type ContractPaymentFilter = 'all' | 'has_payment' | 'no_payment';
/** Filtre: all | has_debt | no_debt - kalan borç var / yok */
export type ContractDebtFilter = 'all' | 'has_debt' | 'no_debt';

export interface ContractListFilters {
  status?: ContractStatusFilter;
  paymentStatus?: ContractPaymentFilter;
  debtStatus?: ContractDebtFilter;
}

@Injectable()
export class ContractsService {
  private readonly logger = new Logger(ContractsService.name);

  constructor(
    @InjectRepository(Contract)
    private contractsRepository: Repository<Contract>,
    @InjectRepository(ContractMonthlyPrice)
    private monthlyPricesRepository: Repository<ContractMonthlyPrice>,
    @InjectRepository(ContractStaff)
    private contractStaffRepository: Repository<ContractStaff>,
    @InjectRepository(Payment)
    private paymentsRepository: Repository<Payment>,
    private roomsService: RoomsService,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
    private readonly mailSettingsService: CompanyMailSettingsService,
    private readonly smsSettingsService: CompanySmsSettingsService,
    private readonly mailService: MailService,
    private readonly smsService: SmsService,
    private readonly companiesService: CompaniesService,
  ) {}

  async create(createContractDto: CreateContractDto): Promise<Contract> {
    const { monthly_prices, staff_ids, ...contractData } = createContractDto;
    
    // Müşterinin zaten aktif bir sözleşmesi var mı kontrol et
    const existingActiveContract = await this.contractsRepository.findOne({
      where: {
        customer_id: contractData.customer_id,
        is_active: true,
      },
    });

    if (existingActiveContract) {
      throw new ConflictException(
        `Bu müşterinin zaten aktif bir sözleşmesi bulunmaktadır (Sözleşme No: ${existingActiveContract.contract_number}). ` +
        `Yeni sözleşme oluşturmadan önce mevcut sözleşmeyi sonlandırmanız gerekmektedir.`
      );
    }
    
    // Contract oluştur
    const contract = this.contractsRepository.create(contractData);
    const saved = (await this.contractsRepository.save(contract)) as unknown as Contract;

    // Aylık fiyatları ekle
    if (monthly_prices && Array.isArray(monthly_prices)) {
      const monthlyPriceEntities = monthly_prices.map((mp: any) =>
        this.monthlyPricesRepository.create({
          contract_id: saved.id,
          month: mp.month,
          price: mp.price,
          notes: mp.notes || null,
        }),
      );
      await this.monthlyPricesRepository.save(monthlyPriceEntities);
    }

    // Personelleri ekle (çoklu)
    if (staff_ids && Array.isArray(staff_ids) && staff_ids.length > 0) {
      const contractStaffEntities = staff_ids.map((userId: string) =>
        this.contractStaffRepository.create({
          contract_id: saved.id,
          user_id: userId,
        }),
      );
      await this.contractStaffRepository.save(contractStaffEntities);
    }

    // Aylık ödemeleri otomatik oluştur
    await this.createPaymentsForContract(saved, monthly_prices);

    // Odayı dolu yap (müşteriye ata)
    if (saved.room_id) {
      await this.roomsService.update(saved.room_id, { status: RoomStatus.OCCUPIED });
    }

    // Bildirim oluştur: Yeni satış (sözleşme) oluşturulduğunda
    const contractWithRelations = await this.findOne(saved.id);
    try {
      const usersToNotify: Array<{ id: string; email?: string }> = [];
      if (contractWithRelations.customer?.company_id) {
        usersToNotify.push(...(await this.usersService.findByCompanyId(contractWithRelations.customer.company_id)));
      }
      usersToNotify.push(...(await this.usersService.findAllSuperAdmins()));
      for (const user of usersToNotify) {
        try {
          await this.notificationsService.create({
            user_id: user.id,
            customer_id: contractWithRelations.customer.id,
            type: NotificationType.CONTRACT_CREATED,
            title: 'Yeni Satış Yapıldı',
            message: `${contractWithRelations.customer.first_name} ${contractWithRelations.customer.last_name} adlı müşteriye ${contractWithRelations.contract_number} numaralı yeni satış yapıldı.`,
            is_read: false,
            metadata: {
              contract_id: contractWithRelations.id,
              contract_number: contractWithRelations.contract_number,
              customer_id: contractWithRelations.customer.id,
              customer_name: `${contractWithRelations.customer.first_name} ${contractWithRelations.customer.last_name}`,
              room_id: contractWithRelations.room_id,
              monthly_price: contractWithRelations.monthly_price,
            },
          });
        } catch (error: unknown) {
          this.logger.warn(`Notification failed for user ${user.id}: ${error instanceof Error ? error.message : String(error)}`);
        }
      }
    } catch (error: unknown) {
      this.logger.warn('Notification creation failed', error instanceof Error ? error.message : String(error));
    }

    // Admin Bildirimi Gönder
    try {
      if (contractWithRelations.customer?.company_id) {
        const companyId = contractWithRelations.customer.company_id;
        const mailSettings = await this.mailSettingsService.findByCompanyId(companyId);
        
        if (mailSettings && mailSettings.is_active && mailSettings.notify_admin_on_contract) {
          const company = await this.companiesService.findOne(companyId);
          if (company.email) {
            const template = mailSettings.admin_contract_created_template || this.mailService.getDefaultAdminContractCreatedTemplate();
            const variables = {
              customer_name: `${contractWithRelations.customer.first_name} ${contractWithRelations.customer.last_name}`,
              contract_number: contractWithRelations.contract_number,
              room_number: contractWithRelations.room?.room_number || '-',
              monthly_price: contractWithRelations.monthly_price,
              date: new Date().toLocaleDateString('tr-TR'),
            };
            const html = this.mailService.renderTemplate(template, variables);
            
            await this.mailService.sendMail(mailSettings, {
              to: company.email,
              subject: `Yeni Sözleşme: ${contractWithRelations.contract_number}`,
              html: html,
            });
            this.logger.log(`Admin notification sent to ${company.email}`);
          }
        }
      }
    } catch (error) {
      this.logger.error('Error sending admin notification', error instanceof Error ? error.stack : String(error));
    }

    // Ödeme kontrolü ve bildirim gönderme
    await this.checkAndSendPaymentNotifications(contractWithRelations);

    return contractWithRelations;
  }

  async findAll(companyId?: string): Promise<Contract[]> {
    if (!companyId) {
      return this.contractsRepository.find({
        relations: ['customer', 'room', 'payments', 'payments.bank_account', 'sold_by_user', 'monthly_prices', 'contract_staff', 'contract_staff.user'],
      });
    }
    return this.contractsRepository
      .createQueryBuilder('contract')
      .leftJoinAndSelect('contract.customer', 'customer')
      .leftJoinAndSelect('contract.room', 'room')
      .leftJoinAndSelect('contract.payments', 'payments')
      .leftJoinAndSelect('payments.bank_account', 'bank_account')
      .leftJoinAndSelect('contract.sold_by_user', 'sold_by_user')
      .leftJoinAndSelect('contract.monthly_prices', 'monthly_prices')
      .leftJoinAndSelect('contract.contract_staff', 'contract_staff')
      .leftJoinAndSelect('contract_staff.user', 'cs_user')
      .where('customer.company_id = :companyId', { companyId })
      .getMany();
  }

  /**
   * Borç bilgisi ile sözleşme id listesi (filtre için)
   */
  private async getContractIdsByDebtFilter(
    companyId: string,
    debtStatus: ContractDebtFilter,
  ): Promise<string[] | null> {
    if (debtStatus === 'all') return null;
    const raw = await this.contractsRepository.query(
      `
      SELECT c.id,
        (COALESCE((SELECT SUM(mp.price) FROM contract_monthly_prices mp WHERE mp.contract_id = c.id), 0)
          + COALESCE(c.transportation_fee, 0) - COALESCE(c.discount, 0)) AS total_debt,
        (COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.contract_id = c.id AND p.status = 'paid'), 0)) AS paid_amount
      FROM contracts c
      INNER JOIN customers cust ON cust.id = c.customer_id
      WHERE cust.company_id = ?
      `,
      [companyId],
    );
    const ids: string[] = [];
    for (const row of raw) {
      const remaining = Number(row.total_debt || 0) - Number(row.paid_amount || 0);
      if (debtStatus === 'has_debt' && remaining > 0) ids.push(row.id);
      if (debtStatus === 'no_debt' && remaining <= 0) ids.push(row.id);
    }
    return ids;
  }

  async findAllPaginated(
    companyId: string,
    params: PaginationParams,
    filters?: ContractListFilters,
  ): Promise<PaginatedResult<Contract>> {
    const qb = this.contractsRepository
      .createQueryBuilder('contract')
      .leftJoinAndSelect('contract.customer', 'customer')
      .leftJoinAndSelect('contract.room', 'room')
      .leftJoinAndSelect('contract.payments', 'payments')
      .leftJoinAndSelect('payments.bank_account', 'bank_account')
      .leftJoinAndSelect('contract.sold_by_user', 'sold_by_user')
      .leftJoinAndSelect('contract.monthly_prices', 'monthly_prices')
      .leftJoinAndSelect('contract.contract_staff', 'contract_staff')
      .leftJoinAndSelect('contract_staff.user', 'cs_user')
      .where('customer.company_id = :companyId', { companyId });

    // Durum: aktif / sonlandırılan
    if (filters?.status === 'active') {
      qb.andWhere('contract.is_active = :isActive', { isActive: true });
    } else if (filters?.status === 'terminated') {
      qb.andWhere('contract.is_active = :isActive', { isActive: false });
    }

    // Ödeme yapanlar: en az bir ödemesi (paid) olanlar
    if (filters?.paymentStatus === 'has_payment') {
      qb.andWhere(
        `EXISTS (SELECT 1 FROM payments p2 WHERE p2.contract_id = contract.id AND p2.status = 'paid')`,
      );
    } else if (filters?.paymentStatus === 'no_payment') {
      qb.andWhere(
        `NOT EXISTS (SELECT 1 FROM payments p2 WHERE p2.contract_id = contract.id AND p2.status = 'paid')`,
      );
    }

    // Borç filtresi: önce eşleşen id'leri al, sonra IN ile filtrele
    const debtFilter = filters?.debtStatus;
    if (debtFilter && debtFilter !== 'all') {
      const debtIds = await this.getContractIdsByDebtFilter(companyId, debtFilter);
      if (debtIds && debtIds.length === 0) {
        return toPaginatedResult([], 0, params);
      }
      if (debtIds && debtIds.length > 0) {
        qb.andWhere('contract.id IN (:...debtIds)', { debtIds });
      }
    }

    qb
      .orderBy('contract.created_at', 'DESC')
      .skip(params.skip)
      .take(params.take);

    const [data, total] = await qb.getManyAndCount();
    return toPaginatedResult(data, total, params);
  }

  /**
   * Birden fazla aktif sözleşmesi olan müşterileri bulur (company scope)
   */
  async findCustomersWithMultipleActiveContracts(companyId?: string): Promise<Array<{
    customer_id: string;
    customer_name: string;
    customer_email: string;
    active_contracts_count: number;
    contracts: Contract[];
  }>> {
    let allContracts: Contract[];
    if (companyId) {
      allContracts = await this.contractsRepository
        .createQueryBuilder('contract')
        .leftJoinAndSelect('contract.customer', 'customer')
        .leftJoinAndSelect('contract.room', 'room')
        .leftJoinAndSelect('contract.payments', 'payments')
        .where('contract.is_active = :active', { active: true })
        .andWhere('customer.company_id = :companyId', { companyId })
        .getMany();
    } else {
      allContracts = await this.contractsRepository.find({
        where: { is_active: true },
        relations: ['customer', 'room', 'payments'],
      });
    }

    // Müşteri ID'sine göre grupla
    const contractsByCustomer = new Map<string, Contract[]>();
    allContracts.forEach((contract) => {
      const customerId = contract.customer_id;
      if (!contractsByCustomer.has(customerId)) {
        contractsByCustomer.set(customerId, []);
      }
      contractsByCustomer.get(customerId)!.push(contract);
    });

    // Birden fazla aktif sözleşmesi olan müşterileri filtrele
    const result: Array<{
      customer_id: string;
      customer_name: string;
      customer_email: string;
      active_contracts_count: number;
      contracts: Contract[];
    }> = [];

    contractsByCustomer.forEach((contracts, customerId) => {
      if (contracts.length > 1) {
        const customer = contracts[0].customer;
        result.push({
          customer_id: customerId,
          customer_name: `${customer.first_name} ${customer.last_name}`,
          customer_email: customer.email,
          active_contracts_count: contracts.length,
          contracts: contracts,
        });
      }
    });

    return result;
  }

  async findOne(id: string): Promise<Contract> {
    // Query builder kullanarak daha optimize sorgu
    const contract = await this.contractsRepository
      .createQueryBuilder('contract')
      .leftJoinAndSelect('contract.customer', 'customer')
      .leftJoinAndSelect('customer.user', 'customer_user')
      .leftJoinAndSelect('customer.company', 'company')
      .leftJoinAndSelect('contract.room', 'room')
      .leftJoinAndSelect('room.warehouse', 'warehouse')
      .leftJoinAndSelect('contract.payments', 'payments')
      .leftJoinAndSelect('payments.bank_account', 'bank_account')
      .leftJoinAndSelect('contract.sold_by_user', 'sold_by_user')
      .leftJoinAndSelect('contract.monthly_prices', 'monthly_prices')
      .leftJoinAndSelect('contract.contract_staff', 'contract_staff')
      .leftJoinAndSelect('contract_staff.user', 'contract_staff_user')
      .where('contract.id = :id', { id })
      .getOne();

    if (!contract) {
      throw new NotFoundException('Sözleşme bulunamadı');
    }

    return contract;
  }

  async getTotalDebt(contractId: string): Promise<number> {
    const contract = await this.findOne(contractId);
    const monthlyPrices = contract.monthly_prices || [];
    const totalMonthly = monthlyPrices.reduce((sum, mp) => sum + Number(mp.price), 0);
    const transportationFee = Number(contract.transportation_fee || 0);
    const discount = Number(contract.discount || 0);
    // Toplam borç = Aylık toplam + Nakliye - İndirim
    return totalMonthly + transportationFee - discount;
  }

  async getPaidAmount(contractId: string): Promise<number> {
    const contract = await this.findOne(contractId);
    const paidPayments = contract.payments?.filter((p) => p.status === 'paid') || [];
    return paidPayments.reduce((sum, p) => sum + Number(p.amount), 0);
  }

  /**
   * Sözleşme için ödeme ve aylık fiyat tutarsızlıklarını kontrol eder
   * 2 aylık kiralama için 7 ödeme gibi durumları tespit eder
   */
  async checkPaymentConsistency(contractId: string): Promise<{
    monthlyPricesCount: number;
    paymentsCount: number;
    duplicateMonths: string[];
    issues: string[];
  }> {
    const contract = await this.findOne(contractId);
    const monthlyPrices = contract.monthly_prices || [];
    const payments = contract.payments || [];

    const monthlyPricesCount = monthlyPrices.length;
    const paymentsCount = payments.length;

    // Aynı ay için birden fazla monthly_price kaydı var mı?
    const monthCounts = new Map<string, number>();
    monthlyPrices.forEach((mp) => {
      const count = monthCounts.get(mp.month) || 0;
      monthCounts.set(mp.month, count + 1);
    });

    const duplicateMonths: string[] = [];
    monthCounts.forEach((count, month) => {
      if (count > 1) {
        duplicateMonths.push(`${month} (${count} kayıt)`);
      }
    });

    const issues: string[] = [];
    
    if (monthlyPricesCount !== paymentsCount) {
      issues.push(
        `Aylık fiyat sayısı (${monthlyPricesCount}) ile ödeme sayısı (${paymentsCount}) eşleşmiyor.`
      );
    }

    if (duplicateMonths.length > 0) {
      issues.push(
        `Aynı ay için birden fazla aylık fiyat kaydı bulunuyor: ${duplicateMonths.join(', ')}`
      );
    }

    // Sözleşme süresini kontrol et
    const startDate = new Date(contract.start_date);
    const endDate = new Date(contract.end_date);
    const monthsDiff = Math.ceil(
      (endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24 * 30)
    );

    if (monthlyPricesCount > monthsDiff) {
      issues.push(
        `Sözleşme süresi yaklaşık ${monthsDiff} ay, ancak ${monthlyPricesCount} aylık fiyat kaydı var.`
      );
    }

    return {
      monthlyPricesCount,
      paymentsCount,
      duplicateMonths,
      issues,
    };
  }

  async update(id: string, updateData: Partial<Contract>): Promise<Contract> {
    await this.contractsRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const contract = await this.contractsRepository.findOne({
      where: { id },
    });

    if (!contract) {
      throw new NotFoundException('Sözleşme bulunamadı. Lütfen geçerli bir sözleşme ID\'si kullandığınızdan emin olun.');
    }

    await this.contractsRepository.delete(id);
  }

  async terminate(id: string): Promise<Contract> {
    const contract = await this.contractsRepository.findOne({
      where: { id },
      relations: ['customer', 'room', 'payments', 'sold_by_user', 'monthly_prices', 'contract_staff', 'contract_staff.user'],
      withDeleted: false,
    });

    if (!contract) {
      throw new NotFoundException('Sözleşme bulunamadı. Lütfen geçerli bir sözleşme ID\'si kullandığınızdan emin olun.');
    }

    // Sözleşmeyi sonlandır
    contract.is_active = false;
    contract.terminated_at = new Date();
    
    // Odayı boşalt
    if (contract.room_id) {
      await this.roomsService.update(contract.room_id, {
        status: RoomStatus.EMPTY,
      });
    }
    
    return this.contractsRepository.save(contract);
  }

  async bulkTerminate(ids: string[]): Promise<void> {
    // Bu metod artık controller'da hata yönetimi ile çağrılıyor
    for (const id of ids) {
      await this.terminate(id);
    }
  }

  /**
   * Sözleşme için aylık ödemeleri otomatik oluşturur
   */
  private async createPaymentsForContract(
    contract: Contract,
    monthlyPrices: any[],
  ): Promise<void> {
    if (!monthlyPrices || monthlyPrices.length === 0) {
      return;
    }

    // Mevcut ödemeleri kontrol et - eğer zaten ödemeler varsa, yeni ödeme oluşturma
    const existingPayments = await this.paymentsRepository.find({
      where: { contract_id: contract.id },
    });

    if (existingPayments.length > 0) {
      // Zaten ödemeler var, yeni ödeme oluşturma
      return;
    }

    const payments: Payment[] = [];
    const currentYear = new Date().getFullYear();
    const processedMonths = new Set<string>(); // Aynı ay için tekrar ödeme oluşturmayı engellemek için
    
    // Mevcut ödeme sayısını al (benzersiz numara için)
    const allPaymentsCount = await this.paymentsRepository.count();
    let paymentCounter = allPaymentsCount + 1;

    // 1. Nakliye ödemesini oluştur (eğer varsa)
    if (contract.transportation_fee && Number(contract.transportation_fee) > 0) {
      let paymentNumber = `PAY-${currentYear}-TR-${String(paymentCounter).padStart(4, '0')}`;
      
      // Benzersizlik kontrolü
      let existingPayment = await this.paymentsRepository.findOne({
        where: { payment_number: paymentNumber },
      });
      
      while (existingPayment) {
        paymentCounter++;
        paymentNumber = `PAY-${currentYear}-TR-${String(paymentCounter).padStart(4, '0')}`;
        existingPayment = await this.paymentsRepository.findOne({
          where: { payment_number: paymentNumber },
        });
      }

      const transportationPayment = this.paymentsRepository.create({
        payment_number: paymentNumber,
        contract_id: contract.id,
        amount: Number(contract.transportation_fee),
        status: PaymentStatus.PENDING,
        type: PaymentType.TRANSPORTATION,
        due_date: new Date(contract.start_date), // Nakliye ödemesi sözleşme başlangıcında
        paid_at: null,
        notes: 'Nakliye Ücreti',
      });
      payments.push(transportationPayment);
      paymentCounter++;
    }

    // 2. Aylık kira ödemelerini oluştur
    let remainingDiscount = Number(contract.discount || 0);

    for (const mp of monthlyPrices) {
      // Month formatı: "YYYY-MM"
      const monthKey = mp.month; // "2024-01" formatında
      
      // Aynı ay için zaten ödeme oluşturulmuşsa, atla
      if (processedMonths.has(monthKey)) {
        continue;
      }
      
      const [year, month] = monthKey.split('-').map(Number);
      
      // Vade tarihi: Ayın son günü (month 0 = önceki ayın son günü, bu yüzden month+1 kullanıyoruz)
      const dueDate = new Date(year, month, 0); // Ayın son günü
      dueDate.setHours(23, 59, 59, 999);

      // Benzersiz ödeme numarası oluştur
      let paymentNumber = `PAY-${currentYear}-${String(paymentCounter).padStart(4, '0')}`;
      
      // Benzersizlik kontrolü
      let existingPayment = await this.paymentsRepository.findOne({
        where: { payment_number: paymentNumber },
      });
      
      while (existingPayment) {
        paymentCounter++;
        paymentNumber = `PAY-${currentYear}-${String(paymentCounter).padStart(4, '0')}`;
        existingPayment = await this.paymentsRepository.findOne({
          where: { payment_number: paymentNumber },
        });
      }

      // Ödeme durumu: Vade tarihi geçmişse overdue, değilse pending
      const now = new Date();
      let status = PaymentStatus.PENDING;
      let daysOverdue = 0;

      if (dueDate < now) {
        status = PaymentStatus.OVERDUE;
        daysOverdue = Math.floor((now.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24));
      }

      let amount = Number(mp.price);
      
      // İndirimi aylık ödemelere uygula
      if (remainingDiscount > 0) {
        if (amount >= remainingDiscount) {
          amount -= remainingDiscount;
          remainingDiscount = 0;
        } else {
          remainingDiscount -= amount;
          amount = 0;
        }
      }

      const payment = this.paymentsRepository.create({
        payment_number: paymentNumber,
        contract_id: contract.id,
        amount: amount,
        status: amount === 0 ? PaymentStatus.PAID : status,
        type: PaymentType.WAREHOUSE,
        due_date: dueDate,
        paid_at: amount === 0 ? new Date() : null,
        payment_method: amount === 0 ? 'discount' : null,
        transaction_id: null,
        notes: mp.notes || null,
        days_overdue: status === PaymentStatus.OVERDUE ? daysOverdue : 0,
      });

      payments.push(payment);
      processedMonths.add(monthKey); // Bu ayı işaretle
      paymentCounter++;
    }

    if (payments.length > 0) {
      await this.paymentsRepository.save(payments);
    }
  }

  /**
   * Müşterinin ödemesi varsa ödeme bildirimi gönderir (mail ve SMS)
   */
  private async checkAndSendPaymentNotifications(contract: Contract): Promise<void> {
    try {
      // Müşterinin ödemelerini kontrol et
      const contractWithPayments = await this.findOne(contract.id);
      const payments = contractWithPayments.payments || [];
      
      // Bekleyen veya gecikmiş ödemeleri bul (Sadece 3 gün kala veya geçmiş olanlar)
      const now = new Date();
      const threeDaysLater = new Date();
      threeDaysLater.setDate(now.getDate() + 3);

      const pendingPayments = payments.filter(
        (p) => {
          if (p.status === PaymentStatus.OVERDUE) return true;
          if (p.status === PaymentStatus.PENDING) {
            const dueDate = new Date(p.due_date);
            // Vadeye 3 gün veya daha az kalmışsa hatırla
            return dueDate <= threeDaysLater;
          }
          return false;
        }
      );

      // Eğer ödeme yoksa işlemi sonlandır
      if (pendingPayments.length === 0) {
        this.logger.log(`No pending payments for contract ${contract.contract_number}`);
        return;
      }

      // Müşteri bilgilerini al
      const customer = contractWithPayments.customer;
      if (!customer) {
        this.logger.error(`Customer not found for contract ${contract.contract_number}`);
        return;
      }

      // Şirket ID'sini al
      const companyId = customer.company_id;
      if (!companyId) {
        this.logger.error(`Company ID not found for customer ${customer.id}`);
        return;
      }

      // Mail ayarlarını kontrol et
      const mailSettings = await this.mailSettingsService.findByCompanyId(companyId);
      if (!mailSettings || !mailSettings.is_active || !mailSettings.smtp_host || !mailSettings.smtp_port) {
        this.logger.warn(`Mail settings not configured for company ${companyId}`);
        return;
      }

      // SMS ayarlarını kontrol et
      const smsSettings = await this.smsSettingsService.findByCompanyId(companyId);
      if (!smsSettings || !smsSettings.is_active || !smsSettings.username || !smsSettings.password || !smsSettings.sender_id) {
        this.logger.warn(`SMS settings not configured for company ${companyId}`);
        return;
      }

      // Toplam borç tutarını hesapla
      const totalDebt = pendingPayments.reduce((sum, p) => sum + Number(p.amount), 0);
      const overduePayments = pendingPayments.filter((p) => p.status === PaymentStatus.OVERDUE);
      const hasOverdue = overduePayments.length > 0;

      // Şirket bilgisini al
      let companyName = 'DepoPazar';
      try {
        const company = await this.companiesService.findOne(companyId);
        companyName = company.name;
      } catch (error) {
        this.logger.warn(`Could not fetch company name for ${companyId}`);
      }

      // Mail gönder
      try {
        const mailTemplate = mailSettings.payment_reminder_template || this.mailService.getDefaultPaymentReminderTemplate();
        const mailVariables = {
          customer_name: `${customer.first_name} ${customer.last_name}`,
          contract_number: contract.contract_number,
          total_debt: totalDebt.toFixed(2),
          payment_count: pendingPayments.length,
          overdue_count: overduePayments.length,
          company_name: companyName,
        };

        const mailHtml = this.mailService.renderTemplate(mailTemplate, mailVariables);
        const mailSubject = hasOverdue 
          ? `Gecikmiş Ödeme Hatırlatması - ${contract.contract_number}`
          : `Ödeme Hatırlatması - ${contract.contract_number}`;

        await this.mailService.sendMail(mailSettings, {
          to: customer.email,
          subject: mailSubject,
          html: mailHtml,
        });

        this.logger.log(`Payment reminder email sent to ${customer.email}`);
      } catch (error: unknown) {
        this.logger.error('Error sending payment reminder email', error instanceof Error ? error.message : String(error));
        throw error;
      }

      // SMS gönder (müşterinin telefon numarası varsa)
      if (customer.phone) {
        try {
          const smsMessage = hasOverdue
            ? `Sayın ${customer.first_name} ${customer.last_name}, ${contract.contract_number} numaralı sözleşmeniz için ${overduePayments.length} adet gecikmiş ödemeniz bulunmaktadır. Toplam borç: ${totalDebt.toFixed(2)} TL. Lütfen ödemenizi yapınız.`
            : `Sayın ${customer.first_name} ${customer.last_name}, ${contract.contract_number} numaralı sözleşmeniz için ${pendingPayments.length} adet bekleyen ödemeniz bulunmaktadır. Toplam borç: ${totalDebt.toFixed(2)} TL. Lütfen ödemenizi yapınız.`;

          await this.smsService.sendSms(smsSettings, {
            to: customer.phone,
            message: smsMessage,
          });

          this.logger.log(`Payment reminder SMS sent to ${customer.phone}`);
        } catch (error: unknown) {
          this.logger.error('Error sending payment reminder SMS', error instanceof Error ? error.message : String(error));
          throw error;
        }
      } else {
        this.logger.warn(`Customer ${customer.id} has no phone number, skipping SMS`);
      }
    } catch (error: unknown) {
      this.logger.error('Error in payment notification', error instanceof Error ? error.message : String(error));
      if (error instanceof BadRequestException) {
        throw error;
      }
      throw new BadRequestException(
        `Ödeme bildirimi gönderilirken bir hata oluştu: ${error instanceof Error ? error.message : 'Bilinmeyen hata'}`
      );
    }
  }
}
