import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Warehouse } from '../warehouses/entities/warehouse.entity';
import { Room } from '../rooms/entities/room.entity';
import { Payment } from '../payments/entities/payment.entity';
import { Contract } from '../contracts/entities/contract.entity';
import { BankAccount } from '../companies/entities/bank-account.entity';
import { RoomStatus } from '../../common/enums/room-status.enum';
import { PaymentStatus } from '../../common/enums/payment-status.enum';

@Injectable()
export class ReportsService {
  constructor(
    @InjectRepository(Warehouse)
    private readonly warehousesRepository: Repository<Warehouse>,
    @InjectRepository(Room)
    private readonly roomsRepository: Repository<Room>,
    @InjectRepository(Payment)
    private readonly paymentsRepository: Repository<Payment>,
    @InjectRepository(Contract)
    private readonly contractsRepository: Repository<Contract>,
    @InjectRepository(BankAccount)
    private readonly bankAccountsRepository: Repository<BankAccount>,
  ) {}

  async getOccupancyReport(companyId: string) {
    try {
      if (!companyId) {
        throw new Error('Şirket ID bulunamadı');
      }

      const warehouses = await this.warehousesRepository.find({
        where: { company_id: companyId },
        relations: ['rooms'],
      });

      let totalRooms = 0;
      let occupiedRooms = 0;
      let emptyRooms = 0;
      let reservedRooms = 0;
      let lockedRooms = 0;

      warehouses.forEach((warehouse) => {
        if (warehouse.rooms && Array.isArray(warehouse.rooms)) {
          warehouse.rooms.forEach((room) => {
            totalRooms++;
            switch (room.status) {
              case RoomStatus.OCCUPIED:
                occupiedRooms++;
                break;
              case RoomStatus.EMPTY:
                emptyRooms++;
                break;
              case RoomStatus.RESERVED:
                reservedRooms++;
                break;
              case RoomStatus.LOCKED:
                lockedRooms++;
                break;
            }
          });
        }
      });

      return {
        total_rooms: totalRooms,
        occupied_rooms: occupiedRooms,
        empty_rooms: emptyRooms,
        reserved_rooms: reservedRooms,
        locked_rooms: lockedRooms,
        occupancy_rate: totalRooms > 0 ? (occupiedRooms / totalRooms) * 100 : 0,
      };
    } catch (error: any) {
      console.error('[ReportsService] Error in getOccupancyReport:', error);
      throw new Error('Doluluk raporu oluşturulurken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }

  async getMonthlyRevenueReport(companyId: string, year: number, month: number) {
    try {
      if (!companyId) {
        throw new Error('Şirket ID bulunamadı');
      }

      if (!year || !month || month < 1 || month > 12) {
        throw new Error('Geçersiz yıl veya ay değeri');
      }

      const startDate = new Date(year, month - 1, 1);
      const endDate = new Date(year, month, 0, 23, 59, 59);

      const payments = await this.paymentsRepository
        .createQueryBuilder('payment')
        .leftJoinAndSelect('payment.contract', 'contract')
        .leftJoin('contract.customer', 'customer')
        .where('payment.status = :status', { status: PaymentStatus.PAID })
        .andWhere('payment.paid_at >= :startDate', { startDate })
        .andWhere('payment.paid_at <= :endDate', { endDate })
        .andWhere('customer.company_id = :companyId', { companyId })
        .getMany();

      const totalRevenue = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);

      return {
        year,
        month,
        total_revenue: totalRevenue,
        total_payments: payments.length,
        payments: payments.map((p) => ({
          id: p.id,
          amount: p.amount,
          paid_at: p.paid_at,
          contract_number: p.contract?.contract_number || null,
        })),
      };
    } catch (error: any) {
      console.error('[ReportsService] Error in getMonthlyRevenueReport:', error);
      throw new Error('Gelir raporu oluşturulurken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }

  async getPaymentsByBankAccount(companyId: string, bankAccountId?: string) {
    const query = this.paymentsRepository
      .createQueryBuilder('payment')
      .leftJoinAndSelect('payment.contract', 'contract')
      .leftJoinAndSelect('payment.bank_account', 'bank_account')
      .leftJoin('contract.customer', 'customer')
      .where('payment.status = :status', { status: PaymentStatus.PAID })
      .andWhere('payment.payment_method = :method', { method: 'bank_transfer' })
      .andWhere('customer.company_id = :companyId', { companyId });

    if (bankAccountId) {
      query.andWhere('payment.bank_account_id = :bankAccountId', { bankAccountId });
    } else {
      query.andWhere('payment.bank_account_id IS NOT NULL');
    }

    const payments = await query.getMany();

    // Group by bank account
    const groupedByBankAccount: { [key: string]: any } = {};
    let totalAmount = 0;

    for (const payment of payments) {
      const bankAccountId = payment.bank_account_id || 'unknown';
      if (!groupedByBankAccount[bankAccountId]) {
        groupedByBankAccount[bankAccountId] = {
          bank_account: payment.bank_account
            ? {
                id: payment.bank_account.id,
                bank_name: payment.bank_account.bank_name,
                account_number: payment.bank_account.account_number,
                account_holder_name: payment.bank_account.account_holder_name,
              }
            : null,
          payments: [],
          total_amount: 0,
          count: 0,
        };
      }
      groupedByBankAccount[bankAccountId].payments.push({
        id: payment.id,
        payment_number: payment.payment_number,
        amount: payment.amount,
        paid_at: payment.paid_at,
        transaction_id: payment.transaction_id,
        contract_number: payment.contract?.contract_number ?? null,
      });
      groupedByBankAccount[bankAccountId].total_amount += Number(payment.amount);
      groupedByBankAccount[bankAccountId].count += 1;
      totalAmount += Number(payment.amount);
    }

    return {
      total_amount: totalAmount,
      total_payments: payments.length,
      by_bank_account: Object.values(groupedByBankAccount),
    };
  }

  /**
   * Banka Hesap Raporu: Hangi banka hesabına ne kadar para girmiş, ne zaman, hangi müşteriden - tüm detaylar
   * Banka hesaplarına gelen ödemeleri müşteri bazında gruplandırır
   */
  async getBankAccountPaymentsByCustomer(
    companyId: string,
    bankAccountId?: string,
    startDate?: string,
    endDate?: string,
  ) {
    const query = this.paymentsRepository
      .createQueryBuilder('payment')
      .leftJoinAndSelect('payment.contract', 'contract')
      .leftJoinAndSelect('contract.customer', 'customer')
      .leftJoinAndSelect('payment.bank_account', 'bank_account')
      .where('payment.status = :status', { status: PaymentStatus.PAID })
      .andWhere('payment.bank_account_id IS NOT NULL')
      .andWhere('payment.paid_at IS NOT NULL')
      .andWhere('customer.company_id = :companyId', { companyId });

    if (bankAccountId) {
      query.andWhere('payment.bank_account_id = :bankAccountId', { bankAccountId });
    }
    if (startDate) {
      query.andWhere('payment.paid_at >= :startDate', { startDate: new Date(startDate) });
    }
    if (endDate) {
      const end = new Date(endDate);
      end.setHours(23, 59, 59, 999);
      query.andWhere('payment.paid_at <= :endDate', { endDate: end });
    }

    const payments = await query.orderBy('payment.paid_at', 'DESC').getMany();

    // Group by bank account, then by customer
    const groupedByBankAccount: { [key: string]: any } = {};
    let grandTotal = 0;

    for (const payment of payments) {
      const baId = payment.bank_account_id || 'unknown';
      const customerId = payment.contract?.customer?.id || 'unknown';
      
      // Initialize bank account if not exists
      if (!groupedByBankAccount[baId]) {
        groupedByBankAccount[baId] = {
          bank_account: payment.bank_account
            ? {
                id: payment.bank_account.id,
                bank_name: payment.bank_account.bank_name,
                account_number: payment.bank_account.account_number,
                account_holder_name: payment.bank_account.account_holder_name,
                iban: payment.bank_account.iban,
                branch_name: payment.bank_account.branch_name,
              }
            : null,
          customers: {},
          total_amount: 0,
          total_payments: 0,
        };
      }

      // Initialize customer if not exists
      if (!groupedByBankAccount[baId].customers[customerId]) {
        groupedByBankAccount[baId].customers[customerId] = {
          customer: payment.contract?.customer
            ? {
                id: payment.contract.customer.id,
                first_name: payment.contract.customer.first_name,
                last_name: payment.contract.customer.last_name,
                email: payment.contract.customer.email,
                phone: payment.contract.customer.phone,
              }
            : null,
          payments: [],
          total_amount: 0,
          payment_count: 0,
        };
      }

      // Add payment details
      const paymentData = {
        id: payment.id,
        payment_number: payment.payment_number,
        amount: Number(payment.amount),
        paid_at: payment.paid_at,
        transaction_id: payment.transaction_id,
        payment_method: payment.payment_method,
        notes: payment.notes,
        contract_number: payment.contract?.contract_number,
        contract_id: payment.contract?.id,
      };

      groupedByBankAccount[baId].customers[customerId].payments.push(paymentData);
      groupedByBankAccount[baId].customers[customerId].total_amount += paymentData.amount;
      groupedByBankAccount[baId].customers[customerId].payment_count += 1;
      
      groupedByBankAccount[baId].total_amount += paymentData.amount;
      groupedByBankAccount[baId].total_payments += 1;
      grandTotal += paymentData.amount;
    }

    // Convert customers object to array
    const result = Object.values(groupedByBankAccount).map((ba: any) => ({
      ...ba,
      customers: Object.values(ba.customers),
    }));

    return {
      total_amount: grandTotal,
      total_payments: payments.length,
      bank_accounts: result,
    };
  }
}
