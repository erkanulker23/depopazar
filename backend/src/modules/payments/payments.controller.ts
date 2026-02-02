import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException, ForbiddenException, Req } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { PaymentsService } from './payments.service';
import { PaytrService } from './paytr.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { CompanyPaytrSettingsService } from '../companies/company-paytr-settings.service';
import { CustomersService } from '../customers/customers.service';
import { CompaniesService } from '../companies/companies.service';
import { ContractsService } from '../contracts/contracts.service';
import { UserRole } from '../../common/enums/user-role.enum';
import { Request } from 'express';

@ApiTags('Payments')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('payments')
export class PaymentsController {
  constructor(
    private readonly paymentsService: PaymentsService,
    private readonly paytrService: PaytrService,
    private readonly paytrSettingsService: CompanyPaytrSettingsService,
    private readonly customersService: CustomersService,
    private readonly companiesService: CompaniesService,
    private readonly contractsService: ContractsService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Create a new payment' })
  async create(@Body() createPaymentDto: any, @CurrentUser() user: any) {
    // Validate that the contract belongs to user's company
    if (createPaymentDto.contract_id) {
      const contract = await this.contractsService.findOne(createPaymentDto.contract_id);
      if (contract?.customer) {
        const companyId = await this.companiesService.getCompanyIdForUser(user);
        if (user.role !== UserRole.SUPER_ADMIN && contract.customer.company_id !== companyId) {
          throw new ForbiddenException('Bu sözleşmeye ödeme ekleyemezsiniz');
        }
      }
    }
    return this.paymentsService.create(createPaymentDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all payments' })
  async findAll(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.paymentsService.findAll(companyId || undefined);
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get payment by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    const payment = await this.paymentsService.findOne(id);
    
    // SUPER_ADMIN can access any payment
    if (user.role === UserRole.SUPER_ADMIN) {
      return payment;
    }

    // Other users can only access payments from their company
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }

    if (payment.contract?.customer?.company_id !== companyId) {
      throw new ForbiddenException('Bu ödemeye erişim yetkiniz yok');
    }

    return payment;
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Update payment' })
  async update(@Param('id') id: string, @Body() updatePaymentDto: any, @CurrentUser() user: any) {
    const payment = await this.paymentsService.findOne(id);
    
    // SUPER_ADMIN can update any payment
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('User has no company');
      }

      // Other users can only update payments from their company
      if (payment.contract?.customer?.company_id !== companyId) {
        throw new ForbiddenException('Bu ödemeyi güncelleyemezsiniz');
      }
    }

    return this.paymentsService.update(id, updatePaymentDto);
  }

  @Post(':id/mark-as-paid')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Mark payment as paid' })
  async markAsPaid(
    @Param('id') id: string,
    @Body() body: { payment_method?: string; transaction_id?: string; notes?: string; bank_account_id?: string },
    @CurrentUser() user: any,
  ) {
    const payment = await this.paymentsService.findOne(id);
    
    // SUPER_ADMIN can mark any payment as paid
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('User has no company');
      }

      // Other users can only mark payments from their company as paid
      if (payment.contract?.customer?.company_id !== companyId) {
        throw new ForbiddenException('Bu ödemeyi işaretleyemezsiniz');
      }

      // Verify bank_account_id belongs to company if provided
      if (body.bank_account_id) {
        // This will be verified in the service if needed
      }
    }

    return this.paymentsService.markAsPaid(id, body.payment_method, body.transaction_id, body.notes, body.bank_account_id);
  }

  @Post('bulk/mark-as-paid')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Mark multiple payments as paid' })
  async markManyAsPaid(
    @Body() body: { ids: string[]; payment_method?: string; transaction_id?: string; notes?: string; bank_account_id?: string },
    @CurrentUser() user: any,
  ) {
    if (!body.ids || body.ids.length === 0) {
      throw new BadRequestException('No payment IDs provided');
    }

    const companyId = user.role !== UserRole.SUPER_ADMIN 
      ? await this.companiesService.getCompanyIdForUser(user)
      : null;

    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }

    // Verify each payment belongs to user's company
    for (const paymentId of body.ids) {
      const payment = await this.paymentsService.findOne(paymentId);
      if (user.role !== UserRole.SUPER_ADMIN && payment.contract?.customer?.company_id !== companyId) {
        throw new ForbiddenException(`Ödeme ${paymentId} işaretlenemez - yetkiniz yok`);
      }
    }

    return this.paymentsService.markManyAsPaid(body.ids, body.payment_method, body.transaction_id, body.notes, body.bank_account_id);
  }

  @Post('paytr/initiate')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING, UserRole.CUSTOMER)
  @ApiOperation({ summary: 'Initiate PayTR payment' })
  async initiatePaytrPayment(
    @CurrentUser() user: any,
    @Body() body: { payment_id: string; customer_id: string },
    @Req() req: Request,
  ) {
    if (!user.company_id) {
      throw new BadRequestException('User has no company');
    }

    const settings = await this.paytrSettingsService.getActiveSettings(user.company_id);
    if (!settings) {
      throw new BadRequestException('PayTR ayarları aktif değil veya eksik');
    }

    const payment = await this.paymentsService.findOne(body.payment_id);
    if (!payment) {
      throw new BadRequestException('Ödeme bulunamadı');
    }

    const customer = await this.customersService.findOne(body.customer_id);
    if (!customer) {
      throw new BadRequestException('Müşteri bulunamadı');
    }

    // Müşteri bilgilerini al
    const customerUser = customer.user;
    if (!customerUser) {
      throw new BadRequestException('Müşteri kullanıcı bilgisi bulunamadı');
    }

    const userIp = (req.headers['x-forwarded-for'] as string)?.split(',')[0] || 
                   (req.headers['x-real-ip'] as string) || 
                   req.ip || 
                   '127.0.0.1';

    const frontendUrl = process.env.FRONTEND_URL || 'http://depotakip-v1.test';
    
    const tokenResponse = await this.paytrService.getPaymentToken(settings, {
      email: customerUser.email,
      amount: Number(payment.amount),
      orderId: payment.id,
      userName: `${customerUser.first_name} ${customerUser.last_name}`,
      userAddress: customer.address || 'Adres belirtilmemiş',
      userPhone: customerUser.phone || customer.phone || '05000000000',
      userIp,
      okUrl: `${frontendUrl}/payments/success?payment_id=${payment.id}`,
      failUrl: `${frontendUrl}/payments/fail?payment_id=${payment.id}`,
    });

    return {
      token: tokenResponse.token,
      payment_id: payment.id,
      amount: payment.amount,
    };
  }

  @Delete('bulk/delete')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Delete multiple payments' })
  async removeMany(@Body() body: { ids: string[] }, @CurrentUser() user: any) {
    if (!body.ids || body.ids.length === 0) {
      throw new BadRequestException('No payment IDs provided');
    }

    const companyId = user.role !== UserRole.SUPER_ADMIN 
      ? await this.companiesService.getCompanyIdForUser(user)
      : null;

    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }

    // Verify each payment belongs to user's company
    for (const paymentId of body.ids) {
      const payment = await this.paymentsService.findOne(paymentId);
      if (user.role !== UserRole.SUPER_ADMIN && payment.contract?.customer?.company_id !== companyId) {
        throw new ForbiddenException(`Ödeme ${paymentId} silinemez - yetkiniz yok`);
      }
    }

    await this.paymentsService.removeMany(body.ids);
    return { message: `${body.ids.length} payment(s) deleted successfully` };
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Delete a payment' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    const payment = await this.paymentsService.findOne(id);
    
    // SUPER_ADMIN can delete any payment
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('User has no company');
      }

      // Other users can only delete payments from their company
      if (payment.contract?.customer?.company_id !== companyId) {
        throw new ForbiddenException('Bu ödemeyi silemezsiniz');
      }
    }

    await this.paymentsService.remove(id);
    return { message: 'Payment deleted successfully' };
  }

}
