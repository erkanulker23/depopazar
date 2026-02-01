import { ExceptionFilter, Catch, ArgumentsHost, HttpException, HttpStatus } from '@nestjs/common';
import { Request, Response } from 'express';

// İngilizce hata mesajlarını Türkçe'ye çeviren mapping
const errorTranslations: { [key: string]: string } = {
  'Internal server error': 'Sunucu hatası oluştu',
  'Unauthorized': 'Yetkisiz erişim',
  'Forbidden': 'Erişim reddedildi',
  'Not Found': 'Bulunamadı',
  'Bad Request': 'Geçersiz istek',
  'Validation failed': 'Doğrulama hatası',
  'User not found or token invalid': 'Kullanıcı bulunamadı veya token geçersiz',
  'Kullanıcı aktif değil veya yetkisiz erişim': 'Kullanıcı aktif değil veya yetkisiz erişim',
  'Company not found': 'Şirket bulunamadı',
  'Customer not found': 'Müşteri bulunamadı',
  'Contract not found': 'Sözleşme bulunamadı',
  'Payment not found': 'Ödeme bulunamadı',
  'Room not found': 'Oda bulunamadı',
  'Warehouse not found': 'Depo bulunamadı',
  'Transportation job not found': 'Nakliye işi bulunamadı',
  'Item not found': 'Eşya bulunamadı',
  'Notification not found': 'Bildirim bulunamadı',
  'Mail settings not found or not active': 'Mail ayarları bulunamadı veya aktif değil',
};

function translateError(message: string): string {
  // Eğer mesaj zaten Türkçe karakterler içeriyorsa, çevirme
  if (/[çğıöşüÇĞIİÖŞÜ]/.test(message)) {
    return message;
  }
  
  // Mapping'de varsa çevir
  if (errorTranslations[message]) {
    return errorTranslations[message];
  }
  
  // Eğer mesaj bir object ise, message property'sini kontrol et
  if (typeof message === 'object' && message !== null) {
    const msg = (message as any).message;
    if (msg && typeof msg === 'string') {
      if (errorTranslations[msg]) {
        return errorTranslations[msg];
      }
      if (/[çğıöşüÇĞIİÖŞÜ]/.test(msg)) {
        return msg;
      }
    }
  }
  
  return message;
}

@Catch()
export class AllExceptionsFilter implements ExceptionFilter {
  catch(exception: unknown, host: ArgumentsHost) {
    const ctx = host.switchToHttp();
    const response = ctx.getResponse<Response>();
    const request = ctx.getRequest<Request>();

    const status =
      exception instanceof HttpException
        ? exception.getStatus()
        : HttpStatus.INTERNAL_SERVER_ERROR;

    let message: any;
    
    if (exception instanceof HttpException) {
      const exceptionResponse = exception.getResponse();
      if (typeof exceptionResponse === 'string') {
        message = {
          statusCode: status,
          message: translateError(exceptionResponse),
        };
      } else if (typeof exceptionResponse === 'object') {
        const responseObj = exceptionResponse as any;
        message = {
          statusCode: status,
          message: responseObj.message 
            ? (Array.isArray(responseObj.message) 
                ? responseObj.message.map((m: string) => translateError(m))
                : translateError(responseObj.message))
            : translateError('Geçersiz istek'),
          error: responseObj.error || 'Bad Request',
        };
      } else {
        message = {
          statusCode: status,
          message: translateError(String(exceptionResponse)),
        };
      }
    } else {
      let errorMessage = 'Sunucu hatası oluştu';
      let errorStack: string | undefined;
      
      if (exception instanceof Error) {
        errorMessage = exception.message;
        errorStack = exception.stack;
      } else if (exception !== null && exception !== undefined) {
        errorMessage = String(exception);
      }
      
      message = {
        statusCode: status,
        message: translateError(errorMessage),
        error: errorStack,
      };
    }

    // Log error in development
    if (process.env.NODE_ENV === 'development') {
      console.error('Exception:', exception);
      if (exception instanceof Error) {
        console.error('Stack:', exception.stack);
      }
    }

    response.status(status).json({
      ...message,
      path: request.url,
      timestamp: new Date().toISOString(),
    });
  }
}
