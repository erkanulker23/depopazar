import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository, LessThan } from 'typeorm';
import { Payment } from './entities/payment.entity';
import { PaymentStatus } from '../../common/enums/payment-status.enum';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';

@Injectable()
export class PaymentsService {
  constructor(
    @InjectRepository(Payment)
    private paymentsRepository: Repository<Payment>,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
  ) {}

  async create(createPaymentDto: Partial<Payment>): Promise<Payment> {
    const payment = this.paymentsRepository.create(createPaymentDto);
    return this.paymentsRepository.save(payment);
  }

  async findAll(companyId?: string): Promise<Payment[]> {
    // Önce gecikmiş ödemeleri güncelle
    await this.updateOverduePayments();
    
    if (companyId) {
      return this.paymentsRepository
        .createQueryBuilder('payment')
        .leftJoinAndSelect('payment.contract', 'contract')
        .leftJoinAndSelect('contract.customer', 'customer')
        .leftJoinAndSelect('payment.bank_account', 'bank_account')
        .where('customer.company_id = :companyId', { companyId })
        .getMany();
    }
    
    return this.paymentsRepository.find({
      relations: ['contract', 'contract.customer', 'bank_account'],
    });
  }

  async findOne(id: string): Promise<Payment> {
    const payment = await this.paymentsRepository.findOne({
      where: { id },
      relations: ['contract', 'contract.customer', 'bank_account'],
    });

    if (!payment) {
      throw new NotFoundException('Ödeme bulunamadı');
    }

    return payment;
  }

  async update(id: string, updateData: Partial<Payment>): Promise<Payment> {
    const payment = await this.findOne(id);

    if (updateData.status === PaymentStatus.PAID && payment.status !== PaymentStatus.PAID) {
      updateData.paid_at = updateData.paid_at ? new Date(updateData.paid_at) : new Date();
      updateData.days_overdue = 0;

      await this.createPaymentReceivedNotification(payment);
    }

    await this.paymentsRepository.update(id, updateData);
    return this.findOne(id);
  }

  /**
   * Ödeme yapıldığında çağrılır
   */
  async markAsPaid(id: string, paymentMethod?: string, transactionId?: string, notes?: string, bankAccountId?: string): Promise<Payment> {
    const payment = await this.findOne(id);
    
    if (payment.status === PaymentStatus.PAID) {
      throw new Error('Bu ödeme zaten yapılmış');
    }

    const updateData: any = {
      status: PaymentStatus.PAID,
      paid_at: new Date(),
      payment_method: paymentMethod || null,
      transaction_id: transactionId || null,
      bank_account_id: bankAccountId || null,
      days_overdue: 0,
    };

    // Eğer notes gönderildiyse, mevcut notes'a ekle veya yeni notes olarak ayarla
    if (notes !== undefined && notes !== null && notes.trim() !== '') {
      if (payment.notes) {
        updateData.notes = `${payment.notes}\n${notes}`.trim();
      } else {
        updateData.notes = notes;
      }
    }

    await this.paymentsRepository.update(id, updateData);

    const updatedPayment = await this.findOne(id);
    
    // Ödeme yapıldığında bildirim oluştur
    await this.createPaymentReceivedNotification(updatedPayment);

    return updatedPayment;
  }

  /**
   * Gecikmiş ödemeleri otomatik olarak günceller
   */
  async updateOverduePayments(): Promise<void> {
    const now = new Date();
    
    // Vade tarihi geçmiş ve hala pending olan ödemeleri bul
    const overduePayments = await this.paymentsRepository.find({
      where: {
        status: PaymentStatus.PENDING,
        due_date: LessThan(now),
      },
      relations: ['contract', 'contract.customer'],
    });

    for (const payment of overduePayments) {
      const daysOverdue = Math.floor(
        (now.getTime() - new Date(payment.due_date).getTime()) / (1000 * 60 * 60 * 24)
      );

      await this.paymentsRepository.update(payment.id, {
        status: PaymentStatus.OVERDUE,
        days_overdue: daysOverdue,
      });

      // Gecikmiş ödeme bildirimi oluştur
      await this.createPaymentOverdueNotification(payment);
    }
  }

  /**
   * Ödeme yapıldığında bildirim oluşturur
   */
  private async createPaymentReceivedNotification(payment: Payment): Promise<void> {
    const paymentWithRelations = await this.paymentsRepository.findOne({
      where: { id: payment.id },
      relations: ['contract', 'contract.customer'],
    });

    if (!paymentWithRelations?.contract?.customer) {
      return;
    }

    const customer = paymentWithRelations.contract.customer;
    const usersToNotify: any[] = [];
    
    // Şirket kullanıcılarına bildirim gönder
    if (customer.company_id) {
      const companyUsers = await this.usersService.findByCompanyId(customer.company_id);
      usersToNotify.push(...companyUsers);
    }
    
    // Super admin kullanıcılarına da bildirim gönder
    const superAdmins = await this.usersService.findAllSuperAdmins();
    usersToNotify.push(...superAdmins);
    
    // Tüm kullanıcılara bildirim gönder
    for (const user of usersToNotify) {
      await this.notificationsService.create({
        user_id: user.id,
        customer_id: customer.id,
        type: NotificationType.PAYMENT_RECEIVED,
        title: 'Ödeme Alındı',
        message: `${customer.first_name} ${customer.last_name} adlı müşteriden ${paymentWithRelations.amount} TL ödeme alındı.`,
        is_read: false,
        metadata: {
          payment_id: paymentWithRelations.id,
          payment_number: paymentWithRelations.payment_number,
          amount: paymentWithRelations.amount,
          customer_id: customer.id,
          customer_name: `${customer.first_name} ${customer.last_name}`,
        },
      });
    }
  }

  /**
   * Gecikmiş ödeme bildirimi oluşturur
   */
  private async createPaymentOverdueNotification(payment: Payment): Promise<void> {
    if (!payment.contract?.customer) {
      return;
    }

    const customer = payment.contract.customer;
    const usersToNotify: any[] = [];
    
    // Şirket kullanıcılarına bildirim gönder
    if (customer.company_id) {
      const companyUsers = await this.usersService.findByCompanyId(customer.company_id);
      usersToNotify.push(...companyUsers);
    }
    
    // Super admin kullanıcılarına da bildirim gönder
    const superAdmins = await this.usersService.findAllSuperAdmins();
    usersToNotify.push(...superAdmins);
    
    // Tüm kullanıcılara bildirim gönder
    for (const user of usersToNotify) {
      await this.notificationsService.create({
        user_id: user.id,
        customer_id: customer.id,
        type: NotificationType.PAYMENT_OVERDUE,
        title: 'Gecikmiş Ödeme',
        message: `${customer.first_name} ${customer.last_name} adlı müşterinin ${payment.amount} TL ödemesi gecikmiş.`,
        is_read: false,
        metadata: {
          payment_id: payment.id,
          payment_number: payment.payment_number,
          amount: payment.amount,
          days_overdue: payment.days_overdue,
          customer_id: customer.id,
          customer_name: `${customer.first_name} ${customer.last_name}`,
        },
      });
    }
  }

  /**
   * Birden fazla ödemeyi yapıldı olarak işaretler
   */
  async markManyAsPaid(ids: string[], paymentMethod?: string, transactionId?: string, notes?: string, bankAccountId?: string): Promise<Payment[]> {
    const updatedPayments: Payment[] = [];
    for (const id of ids) {
      const updated = await this.markAsPaid(id, paymentMethod, transactionId, notes, bankAccountId);
      updatedPayments.push(updated);
    }
    return updatedPayments;
  }

  async remove(id: string): Promise<void> {
    await this.findOne(id);
    await this.paymentsRepository.softDelete(id);
  }

  async removeMany(ids: string[]): Promise<void> {
    if (ids.length === 0) {
      return;
    }
    await this.paymentsRepository.softDelete(ids);
  }
}
