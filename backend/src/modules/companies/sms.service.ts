import { Injectable, Logger } from '@nestjs/common';
import { CompanySmsSettings } from './entities/company-sms-settings.entity';

export interface SmsOptions {
  to: string;
  message: string;
}

@Injectable()
export class SmsService {
  private readonly logger = new Logger(SmsService.name);

  async sendSms(settings: CompanySmsSettings, options: SmsOptions): Promise<void> {
    try {
      if (!settings.is_active) {
        throw new Error('SMS ayarları aktif değil');
      }

      if (!settings.username || !settings.password || !settings.sender_id) {
        throw new Error('SMS ayarları eksik. Lütfen kullanıcı adı, şifre ve gönderen başlığını kontrol edin.');
      }

      const apiUrl = settings.api_url || 'https://api.netgsm.com.tr';
      const sendUrl = `${apiUrl}/sms/send/get`;

      const params = new URLSearchParams({
        usercode: settings.username,
        password: settings.password,
        gsmno: options.to.replace(/\D/g, ''), // Sadece rakamları al
        message: options.message,
        msgheader: settings.sender_id,
      });

      // Test modunda ise gerçek SMS gönderme
      if (settings.test_mode) {
        this.logger.log(`[TEST MODE] SMS would be sent to ${options.to}: ${options.message.substring(0, 50)}...`);
        return;
      }

      const response = await fetch(`${sendUrl}?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`SMS API yanıtı başarısız: ${response.status}`);
      }

      const data = await response.text();
      
      // NetGSM API başarılı yanıtlar genellikle "00" veya mesaj ID döner
      // Hata durumunda genellikle hata kodu döner
      if (data && (data.startsWith('00') || !Number.isNaN(Number(data)))) {
        this.logger.log(`SMS sent successfully to ${options.to}: ${data}`);
      } else {
        throw new Error(`SMS gönderilemedi: ${data}`);
      }
    } catch (error) {
      this.logger.error(`Failed to send SMS: ${error.message}`, error.stack);
      throw error;
    }
  }

  async testConnection(settings: CompanySmsSettings): Promise<boolean> {
    try {
      if (!settings.is_active || !settings.username || !settings.password) {
        return false;
      }

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
        return false;
      }

      const data = await response.text();
      return data && !Number.isNaN(Number(data));
    } catch (error) {
      this.logger.error(`SMS connection test failed: ${error.message}`, error.stack);
      return false;
    }
  }
}
