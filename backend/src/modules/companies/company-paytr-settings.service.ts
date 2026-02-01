import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CompanyPaytrSettings } from './entities/company-paytr-settings.entity';
import { UpdatePaytrSettingsDto } from './dto/update-paytr-settings.dto';

@Injectable()
export class CompanyPaytrSettingsService {
  constructor(
    @InjectRepository(CompanyPaytrSettings)
    private paytrSettingsRepository: Repository<CompanyPaytrSettings>,
  ) {}

  async findByCompanyId(companyId: string): Promise<CompanyPaytrSettings | null> {
    return this.paytrSettingsRepository.findOne({
      where: { company_id: companyId },
    });
  }

  async getOrCreate(companyId: string): Promise<CompanyPaytrSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.paytrSettingsRepository.create({
        company_id: companyId,
        is_active: false,
        test_mode: true,
      });
      settings = await this.paytrSettingsRepository.save(settings);
    }
    
    return settings;
  }

  async update(companyId: string, updateDto: UpdatePaytrSettingsDto): Promise<CompanyPaytrSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.paytrSettingsRepository.create({
        company_id: companyId,
        ...updateDto,
      });
    } else {
      Object.assign(settings, updateDto);
    }
    
    return this.paytrSettingsRepository.save(settings);
  }

  async getActiveSettings(companyId: string): Promise<CompanyPaytrSettings | null> {
    const settings = await this.findByCompanyId(companyId);
    
    if (!settings || !settings.is_active) {
      return null;
    }

    // Tüm gerekli bilgilerin dolu olduğunu kontrol et
    if (!settings.merchant_id || !settings.merchant_key || !settings.merchant_salt) {
      return null;
    }

    return settings;
  }
}
