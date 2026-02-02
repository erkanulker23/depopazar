import { Controller, Get, Query, UseGuards, BadRequestException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { ReportsService } from './reports.service';
import { CompaniesService } from '../companies/companies.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@ApiTags('Reports')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.COMPANY_STAFF, UserRole.ACCOUNTING)
@Controller('reports')
export class ReportsController {
  constructor(
    private readonly reportsService: ReportsService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Get('occupancy')
  @ApiOperation({ summary: 'Get occupancy report' })
  async getOccupancyReport(@CurrentUser() user: any) {
    try {
      if (!user) {
        throw new BadRequestException('Kullanıcı bilgisi bulunamadı');
      }

      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
      }
      
      return await this.reportsService.getOccupancyReport(companyId);
    } catch (error: any) {
      console.error('[ReportsController] Error in getOccupancyReport:', error);
      if (error instanceof BadRequestException) {
        throw error;
      }
      throw new BadRequestException('Doluluk raporu yüklenirken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }

  @Get('revenue')
  @ApiOperation({ summary: 'Get monthly revenue report' })
  async getMonthlyRevenueReport(
    @CurrentUser() user: any,
    @Query('year') year: string,
    @Query('month') month: string,
  ) {
    try {
      if (!user) {
        throw new BadRequestException('Kullanıcı bilgisi bulunamadı');
      }

      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
      }
      
      const y = Number.parseInt(year ?? '', 10);
      const m = Number.parseInt(month ?? '', 10);
      
      if (!Number.isFinite(y) || !Number.isFinite(m) || m < 1 || m > 12) {
        throw new BadRequestException('Geçerli yıl ve ay girin (örn. year=2024, month=1).');
      }
      
      return await this.reportsService.getMonthlyRevenueReport(companyId, y, m);
    } catch (error: any) {
      console.error('[ReportsController] Error in getMonthlyRevenueReport:', error);
      if (error instanceof BadRequestException) {
        throw error;
      }
      throw new BadRequestException('Gelir raporu yüklenirken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }

  @Get('payments-by-bank-account')
  @ApiOperation({ summary: 'Get payments grouped by bank account' })
  async getPaymentsByBankAccount(
    @CurrentUser() user: any,
    @Query('bank_account_id') bankAccountId?: string,
  ) {
    try {
      if (!user) {
        throw new BadRequestException('Kullanıcı bilgisi bulunamadı');
      }

      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
      }
      
      return await this.reportsService.getPaymentsByBankAccount(companyId, bankAccountId);
    } catch (error: any) {
      console.error('[ReportsController] Error in getPaymentsByBankAccount:', error);
      if (error instanceof BadRequestException) {
        throw error;
      }
      throw new BadRequestException('Banka hesabına göre ödemeler yüklenirken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }

  @Get('bank-account-payments-by-customer')
  @ApiOperation({ summary: 'Banka Hesap Raporu: Hangi bankaya ne kadar para girmiş, ne zaman, kimden - tüm detaylar' })
  async getBankAccountPaymentsByCustomer(
    @CurrentUser() user: any,
    @Query('bank_account_id') bankAccountId?: string,
    @Query('start_date') startDate?: string,
    @Query('end_date') endDate?: string,
  ) {
    try {
      if (!user) {
        throw new BadRequestException('Kullanıcı bilgisi bulunamadı');
      }

      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
      }
      
      return await this.reportsService.getBankAccountPaymentsByCustomer(
        companyId,
        bankAccountId,
        startDate,
        endDate,
      );
    } catch (error: any) {
      console.error('[ReportsController] Error in getBankAccountPaymentsByCustomer:', error);
      if (error instanceof BadRequestException) {
        throw error;
      }
      throw new BadRequestException('Müşteri bazında banka hesabı ödemeleri yüklenirken bir hata oluştu: ' + (error.message || 'Bilinmeyen hata'));
    }
  }
}
