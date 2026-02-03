import { Injectable, NotFoundException, BadRequestException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CompanySmsSettings } from './entities/company-sms-settings.entity';
import { UpdateSmsSettingsDto } from './dto/update-sms-settings.dto';

@Injectable()
export class CompanySmsSettingsService {
  constructor(
    @InjectRepository(CompanySmsSettings)
    private smsSettingsRepository: Repository<CompanySmsSettings>,
  ) {}

  async findByCompanyId(companyId: string): Promise<CompanySmsSettings | null> {
    return this.smsSettingsRepository.findOne({
      where: { company_id: companyId },
    });
  }

  async getOrCreate(companyId: string): Promise<CompanySmsSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.smsSettingsRepository.create({
        company_id: companyId,
        is_active: false,
        test_mode: true,
        api_url: 'https://api.netgsm.com.tr',
      });
      settings = await this.smsSettingsRepository.save(settings);
    }
    
    return settings;
  }

  async update(companyId: string, updateDto: UpdateSmsSettingsDto): Promise<CompanySmsSettings> {
    let settings = await this.findByCompanyId(companyId);
    
    if (!settings) {
      settings = this.smsSettingsRepository.create({
        company_id: companyId,
        api_url: 'https://api.netgsm.com.tr',
        ...updateDto,
      });
    } else {
      Object.assign(settings, updateDto);
    }
    
    return this.smsSettingsRepository.save(settings);
  }

  async getActiveSettings(companyId: string): Promise<CompanySmsSettings | null> {
    const settings = await this.findByCompanyId(companyId);
    
    if (!settings || !settings.is_active) {
      return null;
    }

    // Tüm gerekli bilgilerin dolu olduğunu kontrol et
    if (!settings.username || !settings.password || !settings.sender_id) {
      return null;
    }

    return settings;
  }

  async testConnection(companyId: string): Promise<{ success: boolean; message: string }> {
    const settings = await this.findByCompanyId(companyId);
    
    if (!settings || !settings.is_active) {
      throw new NotFoundException('SMS ayarları bulunamadı veya aktif değil');
    }

    if (!settings.username || !settings.password || !settings.sender_id) {
      throw new NotFoundException('SMS ayarları eksik. Lütfen kullanıcı adı, şifre ve gönderen başlığını kontrol edin.');
    }

    try {
      // NetGSM API test endpoint'i - bakiye sorgulama ile test edebiliriz
      const apiUrl = settings.api_url || 'https://api.netgsm.com.tr';
      const testUrl = `${apiUrl}/balance/list/get`;
      
      const params = new URLSearchParams({
        usercode: settings.username,
        password: settings.password,
      });

      const response = await fetch(`${testUrl}?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new BadRequestException(`API yanıtı başarısız: ${response.status}`);
      }

      const data = await response.text();
      
      // NetGSM API başarılı yanıtlar genellikle sayısal değerler döner (bakiye)
      // Hata durumunda genellikle hata mesajı döner
      if (data && !isNaN(Number(data))) {
        return {
          success: true,
          message: `Bağlantı başarılı! Kalan bakiye: ${data} SMS`,
        };
      } else {
        // Hata mesajı döndüyse
        return {
          success: false,
          message: `API yanıtı: ${data}`,
        };
      }
    } catch (error: any) {
      return {
        success: false,
        message: `Bağlantı hatası: ${error.message}`,
      };
    }
  }
}
