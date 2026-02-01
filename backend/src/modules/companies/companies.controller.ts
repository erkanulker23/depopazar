import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  UseGuards,
  UseInterceptors,
  UploadedFile,
  BadRequestException,
} from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { ApiTags, ApiOperation, ApiBearerAuth, ApiConsumes, ApiBody } from '@nestjs/swagger';
import { CompaniesService } from './companies.service';
import { CompanyMailSettingsService } from './company-mail-settings.service';
import { CompanyPaytrSettingsService } from './company-paytr-settings.service';
import { CompanySmsSettingsService } from './company-sms-settings.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { UpdateCompanyDto } from './dto/update-company.dto';
import { UpdateMailSettingsDto } from './dto/update-mail-settings.dto';
import { UpdatePaytrSettingsDto } from './dto/update-paytr-settings.dto';
import { UpdateSmsSettingsDto } from './dto/update-sms-settings.dto';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../users/entities/user.entity';
import {
  validateLogoFile,
  saveLogo,
  removeLogoFile,
} from './logo-upload.helper';

@ApiTags('Companies')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('companies')
export class CompaniesController {
  constructor(
    private readonly companiesService: CompaniesService,
    private readonly mailSettingsService: CompanyMailSettingsService,
    private readonly paytrSettingsService: CompanyPaytrSettingsService,
    private readonly smsSettingsService: CompanySmsSettingsService,
  ) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'Create a new company' })
  async create(@Body() createCompanyDto: any) {
    return this.companiesService.create(createCompanyDto);
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'Get all companies' })
  async findAll() {
    return this.companiesService.findAll();
  }

  @Get('current/company')
  @ApiOperation({ summary: 'Get current user company' })
  async getCurrentCompany(@CurrentUser() user: User) {
    // Super admin doesn't have a company, return null or first company
    if (!user.company_id) {
      if (user.role === UserRole.SUPER_ADMIN) {
        const companies = await this.companiesService.findAll();
        return companies.length > 0 ? companies[0] : null;
      }
      throw new BadRequestException('User has no company');
    }
    return this.companiesService.findOne(user.company_id);
  }

  @Patch('current/company')
  @ApiOperation({ summary: 'Update current user company' })
  async updateCurrentCompany(
    @CurrentUser() user: User,
    @Body() updateCompanyDto: UpdateCompanyDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.companiesService.update(companyId, updateCompanyDto);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get company by ID' })
  async findOne(@Param('id') id: string) {
    return this.companiesService.findOne(id);
  }

  @Patch(':id')
  @ApiOperation({ summary: 'Update company information' })
  async update(
    @Param('id') id: string,
    @Body() updateCompanyDto: UpdateCompanyDto,
    @CurrentUser() user: User,
  ) {
    // Only super admin or company owner can update
    if (user.role !== UserRole.SUPER_ADMIN && user.company_id !== id) {
      throw new Error('Unauthorized');
    }
    return this.companiesService.update(id, updateCompanyDto);
  }

  @Post('current/logo')
  @UseInterceptors(FileInterceptor('logo', { limits: { fileSize: 2 * 1024 * 1024 } }))
  @ApiOperation({ summary: 'Upload company logo' })
  @ApiConsumes('multipart/form-data')
  @ApiBody({
    schema: {
      type: 'object',
      properties: { logo: { type: 'string', format: 'binary' } },
    },
  })
  async uploadLogo(
    @CurrentUser() user: User,
    @UploadedFile() file: Express.Multer.File,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    try {
      validateLogoFile(file);
    } catch (e: any) {
      throw new BadRequestException(e.message);
    }
    const company = await this.companiesService.findOne(companyId);
    await removeLogoFile(company.logo_url);
    const path = await saveLogo(companyId, file);
    return this.companiesService.updateLogo(companyId, path);
  }

  @Delete('current/logo')
  @ApiOperation({ summary: 'Remove company logo' })
  async deleteLogo(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    const company = await this.companiesService.findOne(companyId);
    await removeLogoFile(company.logo_url);
    return this.companiesService.clearLogo(companyId);
  }

  @Get('current/mail-settings')
  @ApiOperation({ summary: 'Get current company mail settings' })
  async getMailSettings(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.mailSettingsService.getOrCreate(companyId);
  }

  @Patch('current/mail-settings')
  @ApiOperation({ summary: 'Update current company mail settings' })
  async updateMailSettings(
    @CurrentUser() user: User,
    @Body() updateDto: UpdateMailSettingsDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.mailSettingsService.update(companyId, updateDto);
  }

  @Post('current/mail-settings/test')
  @ApiOperation({ summary: 'Test mail connection' })
  async testMailConnection(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.mailSettingsService.testConnection(companyId);
  }

  @Get('current/paytr-settings')
  @ApiOperation({ summary: 'Get current company PayTR settings' })
  async getPaytrSettings(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.paytrSettingsService.getOrCreate(companyId);
  }

  @Patch('current/paytr-settings')
  @ApiOperation({ summary: 'Update current company PayTR settings' })
  async updatePaytrSettings(
    @CurrentUser() user: User,
    @Body() updateDto: UpdatePaytrSettingsDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.paytrSettingsService.update(companyId, updateDto);
  }

  @Get('current/sms-settings')
  @ApiOperation({ summary: 'Get current company SMS settings' })
  async getSmsSettings(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.smsSettingsService.getOrCreate(companyId);
  }

  @Patch('current/sms-settings')
  @ApiOperation({ summary: 'Update current company SMS settings' })
  async updateSmsSettings(
    @CurrentUser() user: User,
    @Body() updateDto: UpdateSmsSettingsDto,
  ) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.smsSettingsService.update(companyId, updateDto);
  }

  @Post('current/sms-settings/test')
  @ApiOperation({ summary: 'Test SMS connection' })
  async testSmsConnection(@CurrentUser() user: User) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId) {
      throw new BadRequestException('User has no company');
    }
    return this.smsSettingsService.testConnection(companyId);
  }
}
