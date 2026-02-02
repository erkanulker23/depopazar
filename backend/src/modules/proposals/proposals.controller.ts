import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards } from '@nestjs/common';
import { ProposalsService } from './proposals.service';
import { CreateProposalDto } from './dto/create-proposal.dto';
import { UpdateProposalDto } from './dto/update-proposal.dto';
import { ApiBearerAuth, ApiTags, ApiOperation } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../users/entities/user.entity';
import { CompaniesService } from '../companies/companies.service';

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
  @ApiOperation({ summary: 'Create proposal' })
  async create(@CurrentUser() user: User, @Body() createDto: CreateProposalDto) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.proposalsService.create(companyId, createDto);
  }

  @Get()
  @ApiOperation({ summary: 'Get all proposals' })
  async findAll(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.proposalsService.findAll(companyId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get proposal by id' })
  async findOne(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
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
    if (!companyId) throw new Error('User has no company');
    return this.proposalsService.update(companyId, id, updateDto);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'Delete proposal' })
  async remove(@CurrentUser() user: User, @Param('id') id: string) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) throw new Error('User has no company');
    return this.proposalsService.remove(companyId, id);
  }
}
