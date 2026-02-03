import { Injectable, Logger, BadRequestException } from '@nestjs/common';
import * as nodemailer from 'nodemailer';
import { CompanyMailSettings } from './entities/company-mail-settings.entity';

export interface MailOptions {
  to: string | string[];
  subject: string;
  html: string;
  text?: string;
}

@Injectable()
export class MailService {
  private readonly logger = new Logger(MailService.name);

  async createTransporter(settings: CompanyMailSettings): Promise<nodemailer.Transporter> {
    if (!settings.is_active || !settings.smtp_host || !settings.smtp_port) {
      throw new BadRequestException('Mail settings are not configured or not active');
    }

    const transporter = nodemailer.createTransport({
      host: settings.smtp_host,
      port: settings.smtp_port,
      secure: settings.smtp_secure, // true for 465, false for other ports
      auth: settings.smtp_username && settings.smtp_password
        ? {
            user: settings.smtp_username,
            pass: settings.smtp_password,
          }
        : undefined,
    });

    return transporter;
  }

  async sendMail(settings: CompanyMailSettings, options: MailOptions): Promise<void> {
    try {
      const transporter = await this.createTransporter(settings);

      const mailOptions = {
        from: settings.from_email && settings.from_name
          ? `${settings.from_name} <${settings.from_email}>`
          : settings.from_email || 'noreply@depopazar.com',
        to: Array.isArray(options.to) ? options.to.join(', ') : options.to,
        subject: options.subject,
        html: options.html,
        text: options.text || options.html.replace(/<[^>]*>/g, ''),
      };

      const info = await transporter.sendMail(mailOptions);
      this.logger.log(`Email sent successfully: ${info.messageId}`);
    } catch (error) {
      this.logger.error(`Failed to send email: ${error.message}`, error.stack);
      throw error;
    }
  }

  async testConnection(settings: CompanyMailSettings): Promise<boolean> {
    try {
      const transporter = await this.createTransporter(settings);
      await transporter.verify();
      this.logger.log('SMTP connection verified successfully');
      return true;
    } catch (error) {
      this.logger.error(`SMTP connection test failed: ${error.message}`, error.stack);
      return false;
    }
  }

  // Template rendering helpers
  renderTemplate(template: string, variables: Record<string, any>): string {
    let rendered = template;
    for (const [key, value] of Object.entries(variables)) {
      const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
      rendered = rendered.replace(regex, String(value || ''));
    }
    return rendered;
  }

  // Default templates
  getDefaultContractCreatedTemplate(): string {
    return `
      <h2>Yeni Sözleşme Oluşturuldu</h2>
      <p>Sayın {{customer_name}},</p>
      <p>Sizinle yeni bir depo sözleşmesi oluşturulmuştur.</p>
      <p><strong>Sözleşme No:</strong> {{contract_number}}</p>
      <p><strong>Oda:</strong> {{room_number}}</p>
      <p><strong>Aylık Ücret:</strong> {{monthly_price}} TL</p>
      <p><strong>Başlangıç Tarihi:</strong> {{start_date}}</p>
      <p><strong>Bitiş Tarihi:</strong> {{end_date}}</p>
      <p>Teşekkürler,<br>{{company_name}}</p>
    `;
  }

  getDefaultPaymentReceivedTemplate(): string {
    return `
      <h2>Ödeme Alındı</h2>
      <p>Sayın {{customer_name}},</p>
      <p>Ödemeniz başarıyla alınmıştır.</p>
      <p><strong>Ödeme No:</strong> {{payment_number}}</p>
      <p><strong>Tutar:</strong> {{amount}} TL</p>
      <p><strong>Tarih:</strong> {{payment_date}}</p>
      <p>Teşekkürler,<br>{{company_name}}</p>
    `;
  }

  getDefaultContractExpiringTemplate(): string {
    return `
      <h2>Sözleşme Süresi Dolmak Üzere</h2>
      <p>Sayın {{customer_name}},</p>
      <p>Sözleşmenizin süresi yakında dolacaktır.</p>
      <p><strong>Sözleşme No:</strong> {{contract_number}}</p>
      <p><strong>Bitiş Tarihi:</strong> {{end_date}}</p>
      <p>Lütfen sözleşmenizi yenilemek için bizimle iletişime geçin.</p>
      <p>Teşekkürler,<br>{{company_name}}</p>
    `;
  }

  getDefaultPaymentReminderTemplate(): string {
    return `
      <h2>Ödeme Hatırlatması</h2>
      <p>Sayın {{customer_name}},</p>
      <p>Bekleyen ödemeniz bulunmaktadır.</p>
      <p><strong>Ödeme No:</strong> {{payment_number}}</p>
      <p><strong>Tutar:</strong> {{amount}} TL</p>
      <p><strong>Son Ödeme Tarihi:</strong> {{due_date}}</p>
      <p>Lütfen ödemenizi zamanında yapın.</p>
      <p>Teşekkürler,<br>{{company_name}}</p>
    `;
  }

  getDefaultWelcomeTemplate(): string {
    return `
      <h2>Hoş Geldiniz!</h2>
      <p>Sayın {{customer_name}},</p>
      <p>DepoPazar sistemine hoş geldiniz!</p>
      <p>Hesabınız başarıyla oluşturulmuştur.</p>
      <p>Teşekkürler,<br>{{company_name}}</p>
    `;
  }

  getDefaultAdminContractCreatedTemplate(): string {
    return `
      <h2>Yeni Sözleşme Bildirimi</h2>
      <p>Sisteme yeni bir sözleşme eklendi.</p>
      <p><strong>Müşteri:</strong> {{customer_name}}</p>
      <p><strong>Sözleşme No:</strong> {{contract_number}}</p>
      <p><strong>Oda:</strong> {{room_number}}</p>
      <p><strong>Tutar:</strong> {{monthly_price}} TL</p>
      <p><strong>Tarih:</strong> {{date}}</p>
    `;
  }

  getDefaultAdminPaymentReceivedTemplate(): string {
    return `
      <h2>Ödeme Alındı Bildirimi</h2>
      <p>Yeni bir ödeme alındı.</p>
      <p><strong>Müşteri:</strong> {{customer_name}}</p>
      <p><strong>Ödeme No:</strong> {{payment_number}}</p>
      <p><strong>Tutar:</strong> {{amount}} TL</p>
      <p><strong>Tarih:</strong> {{payment_date}}</p>
    `;
  }
}
