import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { entities } from './entities';

@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => ({
        type: 'mysql',
        host: configService.get('DB_HOST', '127.0.0.1'),
        port: configService.get('DB_PORT', 3307),
        username: configService.get('DB_USERNAME', 'root'),
        password: configService.get('DB_PASSWORD', '123456'),
        database: configService.get('DB_DATABASE', 'depopazar'),
        entities: entities,
        migrations: [__dirname + '/../migrations/*{.ts,.js}'],
        synchronize: false, // Migration'lar kullanılıyor
        logging: configService.get('NODE_ENV') === 'development',
        charset: 'utf8mb4',
        timezone: '+00:00',
      }),
      inject: [ConfigService],
    }),
  ],
})
export class DatabaseModule {}
