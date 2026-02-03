import { Injectable, BadRequestException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as crypto from 'crypto';
import { CompanyPaytrSettings } from '../companies/entities/company-paytr-settings.entity';

export interface PaytrPaymentRequest {
  merchant_id: string;
  merchant_key: string;
  merchant_salt: string;
  email: string;
  payment_amount: number;
  merchant_oid: string; // Unique order ID
  user_name: string;
  user_address: string;
  user_phone: string;
  merchant_ok_url?: string;
  merchant_fail_url?: string;
  user_basket?: string; // Base64 encoded basket
  test_mode?: string; // '1' for test mode
  currency?: string; // 'TL' default
  installment_count?: number;
  timeout_limit?: number;
  no_installment?: string; // '1' to disable installments
  max_installment?: number;
  user_ip?: string;
  lang?: string; // 'tr' or 'en'
}

export interface PaytrTokenResponse {
  status: string;
  token: string;
  message?: string;
}

@Injectable()
export class PaytrService {
  private readonly paytrApiUrl = 'https://www.paytr.com/odeme/api/get-token';

  constructor(private configService: ConfigService) {}

  /**
   * PayTR hash oluşturma
   */
  private createHash(data: string, salt: string): string {
    return crypto
      .createHash('sha256')
      .update(data + salt)
      .digest('base64');
  }

  /**
   * PayTR ödeme token'ı alma
   */
  async getPaymentToken(
    settings: CompanyPaytrSettings,
    paymentData: {
      email: string;
      amount: number;
      orderId: string;
      userName: string;
      userAddress: string;
      userPhone: string;
      userIp?: string;
      okUrl?: string;
      failUrl?: string;
    },
  ): Promise<PaytrTokenResponse> {
    if (!settings.merchant_id || !settings.merchant_key || !settings.merchant_salt) {
      throw new BadRequestException('PayTR ayarları eksik');
    }

    const merchantOid = paymentData.orderId;
    const paymentAmount = Math.round(paymentData.amount * 100); // Kuruş cinsinden

    // User basket (ürün bilgisi) - Base64 encoded
    const userBasket = Buffer.from(
      JSON.stringify([
        ['Ödeme', paymentData.amount.toFixed(2)],
      ]),
    ).toString('base64');

    // Hash oluşturma
    const hashString = `${settings.merchant_id}${merchantOid}${paymentAmount}${paymentData.userIp || '127.0.0.1'}${userBasket}${settings.merchant_salt}`;
    const paytrToken = this.createHash(hashString, settings.merchant_salt);

    const requestData: PaytrPaymentRequest = {
      merchant_id: settings.merchant_id,
      merchant_key: settings.merchant_key,
      merchant_salt: settings.merchant_salt,
      email: paymentData.email,
      payment_amount: paymentAmount,
      merchant_oid: merchantOid,
      user_name: paymentData.userName,
      user_address: paymentData.userAddress,
      user_phone: paymentData.userPhone,
      user_basket: userBasket,
      merchant_ok_url: paymentData.okUrl || `${this.configService.get('FRONTEND_URL')}/odemeler/basarili`,
      merchant_fail_url: paymentData.failUrl || `${this.configService.get('FRONTEND_URL')}/odemeler/hata`,
      test_mode: settings.test_mode ? '1' : '0',
      currency: 'TL',
      timeout_limit: 30,
      no_installment: '0',
      max_installment: 0,
      user_ip: paymentData.userIp || '127.0.0.1',
      lang: 'tr',
    };

    try {
      const response = await fetch(this.paytrApiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(
          Object.entries(requestData).reduce((acc, [key, value]) => {
            if (value !== undefined && value !== null) {
              acc[key] = String(value);
            }
            return acc;
          }, {} as Record<string, string>),
        ).toString(),
      });

      const result = await response.text();
      
      // PayTR response formatı: status=success&token=xxxxx veya status=failed&reason=xxxxx
      const params = new URLSearchParams(result);
      const status = params.get('status');
      const token = params.get('token');
      const message = params.get('reason') || params.get('message');

      if (status === 'success' && token) {
        return {
          status: 'success',
          token,
        };
      } else {
        throw new BadRequestException(message || 'PayTR token alınamadı');
      }
    } catch (error: any) {
      throw new BadRequestException(
        error.message || 'PayTR ile iletişim kurulamadı',
      );
    }
  }

  /**
   * PayTR callback hash doğrulama
   */
  verifyCallback(
    settings: CompanyPaytrSettings,
    merchantOid: string,
    status: string,
    totalAmount: string,
    hash: string,
  ): boolean {
    if (!settings.merchant_salt) {
      return false;
    }

    const hashString = `${merchantOid}${settings.merchant_salt}${status}${totalAmount}`;
    const calculatedHash = this.createHash(hashString, settings.merchant_salt);

    return calculatedHash === hash;
  }
}
