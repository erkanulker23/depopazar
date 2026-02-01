import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CompanyMailSettings } from './entities/company-mail-settings.entity';
import { UpdateMailSettingsDto } from './dto/update-mail-settings.dto';
import { MailService } from './mail.service';

@Injectable()
export class CompanyMailSettingsService {
  constructor(
    @InjectRepository(CompanyMailSettings)
    private mailSettingsRepository: Repository<CompanyMailSettings>,
    private mailService: MailService,
  ) {}

  async findByCompanyId(companyId: string): Promise<CompanyMailSettings | null> {
    return this.mailSettingsRepository.findOne({
      where: { company_id: companyId },
    });
  }

  async getOrCreate(companyId: string): Promise<CompanyMailSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.mailSettingsRepository.create({
        company_id: companyId,
        is_active: false,
      });
      settings = await this.mailSettingsRepository.save(settings);
    }
    
    return settings;
  }

  async update(companyId: string, updateDto: UpdateMailSettingsDto): Promise<CompanyMailSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.mailSettingsRepository.create({
        company_id: companyId,
        ...updateDto,
      });
    } else {
      Object.assign(settings, updateDto);
    }
    
    return this.mailSettingsRepository.save(settings);
  }

  async testConnection(companyId: string): Promise<boolean> {
    const settings = await this.findByCompanyId(companyId);
    
    if (!settings || !settings.is_active) {
      throw new NotFoundException('Mail ayarları bulunamadı veya aktif değil');
    }

    return this.mailService.testConnection(settings);
  }
}
