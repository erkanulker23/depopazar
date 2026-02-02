import { Controller, Get, Post, Body, Patch, Param, Delete, Query, UseGuards, BadRequestException, ForbiddenException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { CustomersService } from './customers.service';
import { CompaniesService } from '../companies/companies.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { CreateCustomerDto } from './dto/create-customer.dto';
import { parsePagination } from '../../common/utils/pagination';

@ApiTags('Customers')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('customers')
export class CustomersController {
  constructor(
    private readonly customersService: CustomersService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create a new customer' })
  async create(@Body() createCustomerDto: CreateCustomerDto, @CurrentUser() user: any) {
    try {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil. Lütfen önce şirket oluşturun.');
      }
      const { company_id: _skip, ...dto } = createCustomerDto as CreateCustomerDto & { company_id?: string };
      return await this.customersService.create({ ...dto, company_id: companyId });
    } catch (error: any) {
      if (error instanceof BadRequestException) throw error;
      throw new BadRequestException(error?.message || 'Müşteri oluşturulamadı');
    }
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get customers (paginated)' })
  async findAll(
    @CurrentUser() user: any,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    const params = parsePagination(page, limit);
    return this.customersService.findAllPaginated(companyId, params);
  }

  @Post('bulk-delete')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete multiple customers' })
  async bulkDelete(@Body() body: { ids: string[] }, @CurrentUser() user: any) {
    let companyId: string | null = null;
    if (user.role !== UserRole.SUPER_ADMIN) {
      companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
      }
    }
    const results = { success: 0, failed: 0, errors: [] as string[] };
    for (const id of body.ids || []) {
      try {
        const customer = await this.customersService.findOne(id);
        if (user.role !== UserRole.SUPER_ADMIN && customer.company_id !== companyId) {
          results.failed++;
          results.errors.push(`${id}: Bu müşteriye erişim yetkiniz yok.`);
          continue;
        }
        const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
        if (activeContracts.length > 0) {
          results.failed++;
          results.errors.push(`${id}: Bu müşterinin aktif sözleşmeleri var.`);
          continue;
        }
        await this.customersService.remove(id);
        results.success++;
      } catch (error: any) {
        results.failed++;
        results.errors.push(`${id}: ${error.message || 'Bilinmeyen hata'}`);
      }
    }
    if (results.failed > 0) {
      return {
        message: `${results.success} müşteri başarıyla silindi, ${results.failed} müşteri silinemedi`,
        details: results.errors,
        success: results.success,
        failed: results.failed,
      };
    }
    return { message: `${results.success} müşteri başarıyla silindi` };
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get customer by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    const customer = await this.customersService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || customer.company_id !== companyId) {
        throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
      }
    }
    return customer;
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Update customer' })
  async update(@Param('id') id: string, @Body() updateCustomerDto: any, @CurrentUser() user: any) {
    const customer = await this.customersService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || customer.company_id !== companyId) {
        throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
      }
    }
    const { company_id: _skip, ...dto } = updateCustomerDto || {};
    return this.customersService.update(id, dto);
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete customer' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    const customer = await this.customersService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || customer.company_id !== companyId) {
        throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
      }
    }
    const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
    if (activeContracts.length > 0) {
      throw new BadRequestException(
        'Bu müşterinin aktif sözleşmeleri var. Müşteriyi silebilmek için önce sözleşmeleri sonlandırmanız gerekiyor.',
      );
    }
    await this.customersService.remove(id);
    return { message: 'Customer deleted successfully' };
  }
}
