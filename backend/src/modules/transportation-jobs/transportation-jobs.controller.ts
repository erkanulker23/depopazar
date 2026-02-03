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
  UseInterceptors,
  UploadedFile,
  Logger,
} from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { ApiTags, ApiOperation, ApiBearerAuth, ApiConsumes, ApiBody } from '@nestjs/swagger';
import { TransportationJobsService } from './transportation-jobs.service';
import { CompaniesService } from '../companies/companies.service';
import { CustomersService } from '../customers/customers.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { parsePagination } from '../../common/utils/pagination';
import { validatePdfFile, savePdf, removePdfFile } from './pdf-upload.helper';

@ApiTags('Transportation Jobs')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('transportation-jobs')
export class TransportationJobsController {
  private readonly logger = new Logger(TransportationJobsController.name);

  constructor(
    private readonly transportationJobsService: TransportationJobsService,
    private readonly companiesService: CompaniesService,
    private readonly customersService: CustomersService,
  ) {}

  private async ensureJobAccess(jobId: string, user: any): Promise<void> {
    const job = await this.transportationJobsService.findOne(jobId);
    if (user.role === UserRole.SUPER_ADMIN) return;
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId || job.company_id !== companyId) {
      throw new ForbiddenException('Bu nakliye işine erişim yetkiniz yok.');
    }
  }

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Create a new transportation job' })
  async create(@Body() createTransportationJobDto: any, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
      throw new BadRequestException('Kullanıcı bir şirkete bağlı değil.');
    }

    const customerId = createTransportationJobDto.customer_id;
    if (!customerId) {
      throw new BadRequestException('Müşteri zorunludur.');
    }

    // Müşterinin şirkete ait olduğunu kontrol et
    if (user.role !== UserRole.SUPER_ADMIN) {
      const customer = await this.customersService.findOne(customerId);
      if (customer.company_id !== companyId) {
        throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
      }
    }

    const job = await this.transportationJobsService.create({
      ...createTransportationJobDto,
      company_id: companyId || createTransportationJobDto.company_id,
    });

    return job;
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get all transportation jobs' })
  async findAll(
    @CurrentUser() user: any,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
    @Query('year') year?: string,
    @Query('month') month?: string,
  ) {
    try {
      if (!user) {
        throw new BadRequestException('Kullanıcı bilgisi bulunamadı');
      }

      const pagination = parsePagination(page, limit);
      const companyId = await this.companiesService.getCompanyIdForUser(user);
      
      const yearFilter = year ? parseInt(year, 10) : undefined;
      const monthFilter = month ? parseInt(month, 10) : undefined;
      
      // Validate year and month if provided
      if (yearFilter !== undefined && (isNaN(yearFilter) || yearFilter < 2000 || yearFilter > 2100)) {
        throw new BadRequestException('Geçersiz yıl değeri');
      }
      if (monthFilter !== undefined && (isNaN(monthFilter) || monthFilter < 1 || monthFilter > 12)) {
        throw new BadRequestException('Geçersiz ay değeri');
      }
      
      if (user.role === UserRole.SUPER_ADMIN) {
        return await this.transportationJobsService.findAll(undefined, pagination, yearFilter, monthFilter);
      }

      if (!companyId && user.role !== UserRole.SUPER_ADMIN) {
        throw new BadRequestException('Kullanıcı bir şirkete bağlı değil');
      }

      return await this.transportationJobsService.findAll(companyId || undefined, pagination, yearFilter, monthFilter);
    } catch (error: unknown) {
      if (error instanceof BadRequestException || error instanceof ForbiddenException) throw error;
      this.logger.error('Error in findAll', error instanceof Error ? error.stack : String(error));
      throw new BadRequestException('Nakliye işleri yüklenirken bir hata oluştu: ' + (error instanceof Error ? error.message : 'Bilinmeyen hata'));
    }
  }

  @Get('customer/:customerId')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get transportation jobs by customer ID' })
  async findByCustomerId(@Param('customerId') customerId: string, @CurrentUser() user: any) {
    const companyId = await this.companiesService.getCompanyIdForUser(user);
    
    if (user.role !== UserRole.SUPER_ADMIN) {
      const customer = await this.customersService.findOne(customerId);
      if (customer.company_id !== companyId) {
        throw new ForbiddenException('Bu müşteriye erişim yetkiniz yok.');
      }
    }

    return this.transportationJobsService.findByCustomerId(customerId);
  }

  @Get(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY, UserRole.ACCOUNTING)
  @ApiOperation({ summary: 'Get transportation job by ID' })
  async findOne(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureJobAccess(id, user);
    return this.transportationJobsService.findOne(id);
  }

  @Patch(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @ApiOperation({ summary: 'Update transportation job' })
  async update(
    @Param('id') id: string,
    @Body() updateTransportationJobDto: any,
    @CurrentUser() user: any,
  ) {
    await this.ensureJobAccess(id, user);
    return this.transportationJobsService.update(id, updateTransportationJobDto);
  }

  @Post(':id/upload-pdf')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER, UserRole.DATA_ENTRY)
  @UseInterceptors(FileInterceptor('file', { limits: { fileSize: 10 * 1024 * 1024 } }))
  @ApiConsumes('multipart/form-data')
  @ApiOperation({ summary: 'Upload PDF contract for transportation job' })
  @ApiBody({
    schema: {
      type: 'object',
      properties: { file: { type: 'string', format: 'binary' } },
    },
  })
  async uploadPdf(
    @Param('id') id: string,
    @UploadedFile() file: Express.Multer.File,
    @CurrentUser() user: any,
  ) {
    await this.ensureJobAccess(id, user);
    
    if (!file) {
      throw new BadRequestException('PDF dosyası yüklenmedi');
    }

    validatePdfFile(file);
    const pdfUrl = await savePdf(id, file);
    
    const job = await this.transportationJobsService.findOne(id);
    if (job.contract_pdf_url) {
      await removePdfFile(job.contract_pdf_url);
    }
    
    await this.transportationJobsService.update(id, { contract_pdf_url: pdfUrl });
    
    return { contract_pdf_url: pdfUrl };
  }

  @Delete(':id')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Delete transportation job' })
  async remove(@Param('id') id: string, @CurrentUser() user: any) {
    await this.ensureJobAccess(id, user);
    const job = await this.transportationJobsService.findOne(id);
    if (job.contract_pdf_url) {
      await removePdfFile(job.contract_pdf_url);
    }
    await this.transportationJobsService.remove(id);
    return { message: 'Nakliye işi başarıyla silindi' };
  }
}
