import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  Query,
  UseGuards,
  BadRequestException,
  ForbiddenException,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { ContractsService, ContractListFilters } from './contracts.service';
import { CompaniesService } from '../companies/companies.service';
import { CustomersService } from '../customers/customers.service';
import { RoomsService } from '../rooms/rooms.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { parsePagination } from '../../common/utils/pagination';

@ApiTags('Contracts')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('contracts')
export class ContractsController {
  constructor(
    private readonly contractsService: ContractsService,
    private readonly companiesService: CompaniesService,
    private readonly customersService: CustomersService,
    private readonly roomsService: RoomsService,
  ) {}

  private async ensureContractAccess(contractId: string, user: any): Promise<void> {
    const contract = await this.contractsService.findOne(contractId);
    if (user.role === UserRole.SUPER_ADMIN) return;
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId || contract.customer?.company_id !== companyId) {
      throw new ForbiddenException('Bu sözleşmeye erişim yetkiniz yok.');
    }
  }

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create a new contract' })
  async create(@Body() createContractDto: any, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    const customerId = createContractDto.customer_id;
    const roomId = createContractDto.room_id;
    if (!customerId || !roomId) {
      throw new BadRequestException('Müşteri ve oda zorunludur.');
    }
    const customer = await this.customersService.findOne(customerId);
    if (customer.company_id !== companyId) {
      throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
    }
    const room = await this.roomsService.findOne(roomId);
    const wh = room?.warehouse as { company_id?: string } | undefined;
    if (!wh?.company_id || wh.company_id !== companyId) {
      throw new ForbiddenException('Bu odaya erişim yetkiniz yok.');
    }
    return this.contractsService.create(createContractDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get contracts (paginated, filterable)' })
  async findAll(
    @CurrentUser() user: any,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
    @Query('status') status?: 'all' | 'active' | 'terminated',
    @Query('paymentStatus') paymentStatus?: 'all' | 'has_payment' | 'no_payment',
    @Query('debtStatus') debtStatus?: 'all' | 'has_debt' | 'no_debt',
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    const params = parsePagination(page, limit);
    const filters: ContractListFilters = {};
    if (status && ['all', 'active', 'terminated'].includes(status)) filters.status = status;
    if (paymentStatus && ['all', 'has_payment', 'no_payment'].includes(paymentStatus)) filters.paymentStatus = paymentStatus;
    if (debtStatus && ['all', 'has_debt', 'no_debt'].includes(debtStatus)) filters.debtStatus = debtStatus;
    return this.contractsService.findAllPaginated(companyId, params, Object.keys(filters).length ? filters : undefined);
  }

  @Get('customers-with-multiple-contracts')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Find customers with multiple active contracts' })
  async findCustomersWithMultipleActiveContracts(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    return this.contractsService.findCustomersWithMultipleActiveContracts(companyId);
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get contract by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    return this.contractsService.findOne(id);
  }

  @Get(':id/total-debt')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get total debt for contract' })
  async getTotalDebt(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    const totalDebt = await this.contractsService.getTotalDebt(id);
    const paidAmount = await this.contractsService.getPaidAmount(id);
    return {
      totalDebt,
      paidAmount,
      remainingDebt: totalDebt - paidAmount,
    };
  }

  @Get(':id/payment-consistency')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Check payment and monthly price consistency for contract' })
  async checkPaymentConsistency(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    return this.contractsService.checkPaymentConsistency(id);
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Update contract' })
  async update(@Param('id') id: string, @Body() updateContractDto: any, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    return this.contractsService.update(id, updateContractDto);
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete contract' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    await this.contractsService.remove(id);
    return { message: 'Contract deleted successfully' };
  }

  @Post(':id/terminate')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Terminate contract (end storage)' })
  async terminate(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureContractAccess(id, user);
    return this.contractsService.terminate(id);
  }

  @Post('bulk-delete')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete multiple contracts' })
  async bulkDelete(@Body() body: { ids: string[] }, @CurrentUser() user: any) {
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    const results = { success: 0, failed: 0, errors: [] as string[] };
    for (const id of body.ids || []) {
      try {
        await this.ensureContractAccess(id, user);
        await this.contractsService.remove(id);
        results.success++;
      } catch (e: any) {
        results.failed++;
        results.errors.push(`${id}: ${e.message || 'Bilinmeyen hata'}`);
      }
    }
    if (results.failed > 0) {
      return {
        message: `${results.success} sözleşme başarıyla silindi, ${results.failed} sözleşme silinemedi`,
        details: results.errors,
        success: results.success,
        failed: results.failed,
      };
    }
    return { message: `${results.success} sözleşme başarıyla silindi` };
  }

  @Post('bulk-terminate')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Terminate multiple contracts (end storage)' })
  async bulkTerminate(@Body() body: { ids: string[] }, @CurrentUser() user: any) {
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    const results = { success: 0, failed: 0, errors: [] as string[] };
    for (const id of body.ids || []) {
      try {
        await this.ensureContractAccess(id, user);
        await this.contractsService.terminate(id);
        results.success++;
      } catch (e: any) {
        results.failed++;
        results.errors.push(`${id}: ${e.message || 'Bilinmeyen hata'}`);
      }
    }
    if (results.failed > 0) {
      return {
        message: `${results.success} sözleşme başarıyla sonlandırıldı, ${results.failed} sözleşme sonlandırılamadı`,
        details: results.errors,
        success: results.success,
        failed: results.failed,
      };
    }
    return { message: `${results.success} sözleşme başarıyla sonlandırıldı` };
  }
}
