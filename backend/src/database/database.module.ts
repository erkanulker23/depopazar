import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { entities } from './entities';

/** Production'da DB değerleri .env'den zorunlu; default verilmez (izolasyon kuralı). */
function requireEnv(configService: ConfigService, key: string): string {
  const v = configService.get(key);
  if (v === undefined) {
    throw new Error(`Production için ${key} .env içinde tanımlanmalıdır.`);
  }
  return String(v);
}

function getDbConfig(configService: ConfigService) {
  // NODE_ENV=production ise DB_* .env'de zorunlu; NODE_ENV=development ise default kullanılır (127.0.0.1, 3307, depopazar vb.)
  const isProd = configService.get('NODE_ENV') === 'production';
  return {
    type: 'mysql' as const,
    host: isProd
      ? requireEnv(configService, 'DB_HOST')
      : configService.get('DB_HOST', '127.0.0.1'),
    port: parseInt(
      isProd
        ? requireEnv(configService, 'DB_PORT')
        : configService.get('DB_PORT', '3307'),
      10,
    ),
    username: isProd
      ? requireEnv(configService, 'DB_USERNAME')
      : configService.get('DB_USERNAME', 'root'),
    password: isProd
      ? requireEnv(configService, 'DB_PASSWORD')
      : configService.get('DB_PASSWORD', '123456'),
    database: isProd
      ? requireEnv(configService, 'DB_DATABASE')
      : configService.get('DB_DATABASE', 'depopazar'),
    entities,
    migrations: [__dirname + '/../migrations/*{.ts,.js}'],
    synchronize: false, // Production'da kesinlikle false; sadece migration kullanılır
    logging: configService.get('NODE_ENV') === 'development',
    charset: 'utf8mb4',
    timezone: '+00:00',
  };
}

@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => getDbConfig(configService),
      inject: [ConfigService],
    }),
  ],
})
export class DatabaseModule {}
