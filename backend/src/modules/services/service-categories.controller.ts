import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards } from '@nestjs/common';
import { ServiceCategoriesService } from './service-categories.service';
import { CreateServiceCategoryDto } from './dto/create-service-category.dto';
import { UpdateServiceCategoryDto } from './dto/update-service-category.dto';
import { ApiBearerAuth, ApiTags, ApiOperation } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../users/entities/user.entity';
import { CompaniesService } from '../companies/companies.service';
import { UserRole } from '../../common/enums/user-role.enum';

@ApiTags('Service Categories')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('service-categories')
export class ServiceCategoriesController {
  constructor(
    private readonly categoriesService: ServiceCategoriesService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create service category' })
  async create(@CurrentUser() user: User, @Body() createDto: CreateServiceCategoryDto) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.categoriesService.create(companyId, createDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all service categories' })
  async findAll(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.categoriesService.findAll(companyId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get service category by id' })
  async findOne(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.categoriesService.findOne(companyId, id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update service category' })
  async update(
    @CurrentUser() user: User,
    @Param('id') id: string,
    @Body() updateDto: UpdateServiceCategoryDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.categoriesService.update(companyId, id, updateDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Delete service category' })
  async remove(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.categoriesService.remove(companyId, id);
  }
}
