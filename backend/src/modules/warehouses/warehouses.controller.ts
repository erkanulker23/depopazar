import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException, ForbiddenException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { WarehousesService } from './warehouses.service';
import { CompaniesService } from '../companies/companies.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { CreateWarehouseDto } from './dto/create-warehouse.dto';

@ApiTags('Warehouses')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('warehouses')
export class WarehousesController {
  constructor(
    private readonly warehousesService: WarehousesService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create a new warehouse' })
  async create(@Body() createWarehouseDto: CreateWarehouseDto, @CurrentUser() user: any) {
    try {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil. Lütfen önce şirket oluşturun.');
      }
      const { company_id: _skip, ...dto } = createWarehouseDto as CreateWarehouseDto & { company_id?: string };
      return await this.warehousesService.create({ ...dto, company_id: companyId });
    } catch (error: any) {
      if (error instanceof BadRequestException) throw error;
      throw new BadRequestException(error?.message || 'Depo oluşturulamadı');
    }
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all warehouses' })
  async findAll(@CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }
    return this.warehousesService.findAll(companyId);
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Delete warehouse' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    const warehouse = await this.warehousesService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || warehouse.company_id !== companyId) {
        throw new ForbiddenException('Bu depoya erişim yetkiniz yok.');
      }
    }
    const roomsWithContracts = warehouse.rooms?.filter((room: any) => {
      const activeContracts = room.contracts?.filter((c: any) => c.is_active) || [];
      return activeContracts.length > 0;
    }) || [];
    if (roomsWithContracts.length > 0) {
      throw new BadRequestException(
        'Bu depoda aktif sözleşmeli odalar var. Depoyu silebilmek için önce sözleşmeleri sonlandırmanız gerekiyor.',
      );
    }
    await this.warehousesService.remove(id);
    return { message: 'Warehouse deleted successfully' };
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get warehouse by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    const warehouse = await this.warehousesService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || warehouse.company_id !== companyId) {
        throw new ForbiddenException('Bu depoya erişim yetkiniz yok.');
      }
    }
    return warehouse;
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Update warehouse' })
  async update(@Param('id') id: string, @Body() updateWarehouseDto: any, @CurrentUser() user: any) {
    const warehouse = await this.warehousesService.findOne(id);
    if (user.role !== UserRole.SUPER_ADMIN) {
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      if (!companyId || warehouse.company_id !== companyId) {
        throw new ForbiddenException('Bu depoya erişim yetkiniz yok.');
      }
    }
    const { company_id: _skip, ...dto } = updateWarehouseDto || {};
    return this.warehousesService.update(id, dto);
  }
}
