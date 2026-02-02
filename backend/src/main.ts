import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { NestExpressApplication } from '@nestjs/platform-express';
import { join } from 'path';
import { mkdir } from 'fs/promises';
import { existsSync } from 'fs';
import { AppModule } from './app.module';
import { AllExceptionsFilter } from './common/filters/http-exception.filter';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);
  const config = app.get(ConfigService);
  // Hangi .env kullanıldığını doğrulamak için (şifre yok)
  console.log(
    '[env] NODE_ENV=%s DB_DATABASE=%s (degistiriyorsaniz deploy sonrasi pm2 restart gerekir)',
    config.get('NODE_ENV'),
    config.get('DB_DATABASE'),
  );

  // Uploads klasörü kontrolü ve oluşturulması
  const uploadsDir = join(process.cwd(), 'uploads');
  if (!existsSync(uploadsDir)) {
    await mkdir(uploadsDir, { recursive: true });
  }
  
  // Statik dosyaların (resimler vb.) dışarı açılması
  app.useStaticAssets(join(process.cwd(), 'uploads'), { 
    prefix: '/api/uploads/',
    // Sunucu tarafında erişim kolaylığı için
  });

  // Global hata yakalayıcı
  app.useGlobalFilters(new AllExceptionsFilter());

  // Global doğrulama (Validation) ayarları
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: false, 
      transform: true,
    }),
  );

  // CORS: Domain/subdomain env'den; hardcoded origin yok (çoklu kurulum kuralı)
  let corsOrigins: string[] = [];
  if (process.env.CORS_ORIGINS) {
    corsOrigins = process.env.CORS_ORIGINS.split(',').map((o) => o.trim()).filter(Boolean);
  } else if (process.env.FRONTEND_URL) {
    corsOrigins = [process.env.FRONTEND_URL];
  } else if (process.env.NODE_ENV !== 'production') {
    corsOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost:3180', 'http://127.0.0.1:3180'];
  }
  if (process.env.NODE_ENV === 'production' && corsOrigins.length === 0) {
    throw new Error('Production için CORS_ORIGINS veya FRONTEND_URL tanımlanmalı.');
  }
  app.enableCors({
    origin: corsOrigins.length > 0 ? corsOrigins : true,
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
  });

  // Tüm API endpointleri /api ile başlasın
  app.setGlobalPrefix('api');

  // Swagger (API Dökümantasyonu) - Sadece development ortamında veya SWAGGER_ENABLED=true ise
  const swaggerEnabled = process.env.SWAGGER_ENABLED === 'true' || process.env.NODE_ENV !== 'production';
  if (swaggerEnabled) {
    const config = new DocumentBuilder()
      .setTitle('DepoPazar API')
      .setDescription('Eşya Depolama Firmaları için SaaS Tabanlı Depo Takip & CRM Sistemi API')
      .setVersion('1.0')
      .addBearerAuth()
      .build();
    const document = SwaggerModule.createDocument(app, config);
    SwaggerModule.setup('api/docs', app, document);
    console.log('📚 Swagger: http://localhost:' + (process.env.PORT || 4100) + '/api/docs');
  } else {
    console.log('⚠️ Swagger devre dışı (güvenlik)');
  }

  // Port ayarı (Forge'da 4100 kullanıyoruz)
  const port = process.env.PORT || 4100;
  await app.listen(port);

  console.log(`🚀 Application is running on: http://localhost:${port}`);
}

bootstrap();