import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { NestExpressApplication } from '@nestjs/platform-express';
import { join } from 'path';
import { mkdir } from 'fs/promises';
import { existsSync } from 'fs';
import { AppModule } from './app.module';
import { AllExceptionsFilter } from './common/filters/http-exception.filter';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);

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

  // CORS Ayarları - Tarayıcı hatalarını önlemek için kritik
  app.enableCors({
    origin: [
      'https://depo.awapanel.com',      // Canlı frontend adresin
      'http://depo.awapanel.com',       // HTTP versiyonu (opsiyonel)
      'http://localhost:3180',          // Yerel geliştirme portun
      'http://127.0.0.1:3180',
      'http://localhost:5173',          // Standart Vite portu (ihtiyacın olabilir)
    ],
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