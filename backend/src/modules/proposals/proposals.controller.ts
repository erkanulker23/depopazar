import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards, BadRequestException } from '@nestjs/common';
import { ProposalsService } from './proposals.service';
import { CreateProposalDto } from './dto/create-proposal.dto';
import { UpdateProposalDto } from './dto/update-proposal.dto';
import { ApiBearerAuth, ApiTags, ApiOperation } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../users/entities/user.entity';
import { CompaniesService } from '../companies/companies.service';
import { UserRole } from '../../common/enums/user-role.enum';

@ApiTags('Proposals')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('proposals')
export class ProposalsController {
  constructor(
    private readonly proposalsService: ProposalsService,
    private readonly companiesService: CompaniesService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create proposal' })
  async create(@CurrentUser() user: User, @Body() createDto: CreateProposalDto) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.proposalsService.create(companyId, createDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.COMPANY_STAFF, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all proposals' })
  async findAll(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    const isSuperAdmin = user?.role === UserRole.SUPER_ADMIN || String(user?.role ?? '').toLowerCase() === 'super_admin';
    if (!companyId) {
      if (isSuperAdmin) return [];
      throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    }
    return this.proposalsService.findAll(companyId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get proposal by id' })
  async findOne(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.proposalsService.findOne(companyId, id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update proposal' })
  async update(
    @CurrentUser() user: User,
    @Param('id') id: string,
    @Body() updateDto: UpdateProposalDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.proposalsService.update(companyId, id, updateDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Delete proposal' })
  async remove(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new BadRequestException('Bu kullanıcının bir firması atanmamış. Lütfen kullanıcıyı bir firmaya atayın veya firma oluşturun.');
    return this.proposalsService.remove(companyId, id);
  }
}
