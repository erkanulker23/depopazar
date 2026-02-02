import { DataSource } from 'typeorm';
import { config } from 'dotenv';
import { join } from 'path';

// Tek .env: proje kökü (backend/ üst dizini)
const projectRoot = process.cwd().endsWith('backend') ? join(process.cwd(), '..') : process.cwd();
config({ path: join(projectRoot, '.env') });

export default new DataSource({
  type: 'mysql',
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3307'),
  username: process.env.DB_USERNAME || 'root',
  password: process.env.DB_PASSWORD || '123456',
  database: process.env.DB_DATABASE || 'depopazar',
  entities: [__dirname + '/../**/*.entity{.ts,.js}'],
  migrations: [__dirname + '/../migrations/*{.ts,.js}'],
  synchronize: false,
  logging: true,
  charset: 'utf8mb4',
  timezone: '+00:00',
});
