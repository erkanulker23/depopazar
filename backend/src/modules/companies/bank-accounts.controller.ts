import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { BankAccountsService } from './bank-accounts.service';
import { CompaniesService } from './companies.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';

@ApiTags('Bank Accounts')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('bank-accounts')
export class BankAccountsController {
  constructor(
    private readonly bankAccountsService: BankAccountsService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Create a new bank account' })
  async create(@Body() createBankAccountDto: any, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.bankAccountsService.create(createBankAccountDto, companyId);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all bank accounts for company' })
  async findAll(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.bankAccountsService.findAll(companyId);
  }

  @Get('active')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get active bank accounts for company' })
  async findActive(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.bankAccountsService.findActive(companyId);
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get bank account by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.bankAccountsService.findOne(id, companyId);
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Update bank account' })
  async update(@Param('id') id: string, @Body() updateBankAccountDto: any, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    return this.bankAccountsService.update(id, updateBankAccountDto, companyId);
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Delete bank account' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('User has no company');
    }
    await this.bankAccountsService.remove(id, companyId);
    return { message: 'Banka hesabı başarıyla silindi' };
  }
}
