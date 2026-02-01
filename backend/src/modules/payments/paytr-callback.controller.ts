import { Controller, Post, Body, Req } from '@nestjs/common';
import { ApiTags, ApiOperation } from '@nestjs/swagger';
import { PaymentsService } from './payments.service';
import { PaytrService } from './paytr.service';
import { CompanyPaytrSettingsService } from '../companies/company-paytr-settings.service';
import { CustomersService } from '../customers/customers.service';
import { Request } from 'express';

@ApiTags('Payments')
@Controller('payments')
export class PaytrCallbackController {
  constructor(
    private readonly paymentsService: PaymentsService,
    private readonly paytrService: PaytrService,
    private readonly paytrSettingsService: CompanyPaytrSettingsService,
    private readonly customersService: CustomersService,
  ) {}

  @Post('paytr/callback')
  @ApiOperation({ summary: 'PayTR payment callback (Public)' })
  async paytrCallback(
    @Body() body: any,
    @Req() req: Request,
  ) {
    const {
      merchant_oid,
      status,
      total_amount,
      hash,
    } = body;

    if (!merchant_oid || !status || !total_amount || !hash) {
      return { status: 'error', message: 'Eksik parametreler' };
    }

    // Payment ID'den payment'ı bul
    const payment = await this.paymentsService.findOne(merchant_oid);
    if (!payment) {
      return { status: 'error', message: 'Ödeme bulunamadı' };
    }

    // Contract'tan customer_id'yi al
    const contract = payment.contract;
    if (!contract || !contract.customer_id) {
      return { status: 'error', message: 'Sözleşme bulunamadı' };
    }

    // Customer'tan company_id'yi al
    const customer = await this.customersService.findOne(contract.customer_id);
    if (!customer || !customer.company_id) {
      return { status: 'error', message: 'Müşteri bulunamadı' };
    }

    const settings = await this.paytrSettingsService.getActiveSettings(customer.company_id);
    if (!settings) {
      return { status: 'error', message: 'PayTR ayarları bulunamadı' };
    }

    // Hash doğrulama
    const isValid = this.paytrService.verifyCallback(
      settings,
      merchant_oid,
      status,
      total_amount,
      hash,
    );

    if (!isValid) {
      return { status: 'error', message: 'Hash doğrulaması başarısız' };
    }

    // Ödeme başarılı ise
    if (status === 'success') {
      await this.paymentsService.markAsPaid(
        payment.id,
        'credit_card',
        merchant_oid,
      );
      return { status: 'success' };
    }

    return { status: 'failed', message: 'Ödeme başarısız' };
  }
}
