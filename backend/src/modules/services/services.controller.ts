import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException } from '@nestjs/common';
import { ServicesService } from './services.service';
import { CreateServiceDto } from './dto/create-service.dto';
import { UpdateServiceDto } from './dto/update-service.dto';
import { ApiBearerAuth, ApiTags, ApiOperation } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../users/entities/user.entity';
import { CompaniesService } from '../companies/companies.service';
import { UserRole } from '../../common/enums/user-role.enum';

@ApiTags('Services')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('services')
export class ServicesController {
  constructor(
    private readonly servicesService: ServicesService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.COMPANY_STAFF, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create service' })
  async create(@CurrentUser() user: User, @Body() createDto: CreateServiceDto) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.servicesService.create(companyId, createDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.COMPANY_STAFF, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all services' })
  async findAll(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    const isSuperAdmin = user?.role === UserRole.SUPER_ADMIN || String(user?.role ?? '').toLowerCase() === 'super_admin';
    if (!companyId) {
      if (isSuperAdmin) return [];
      throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    }
    return this.servicesService.findAll(companyId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get service by id' })
  async findOne(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.servicesService.findOne(companyId, id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update service' })
  async update(
    @CurrentUser() user: User,
    @Param('id') id: string,
    @Body() updateDto: UpdateServiceDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.servicesService.update(companyId, id, updateDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Delete service' })
  async remove(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.servicesService.remove(companyId, id);
  }
}
