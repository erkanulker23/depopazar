import { DataSource } from 'typeorm';
import { config } from 'dotenv';
import { getEnvPath } from '../common/config/env-path';
import { entities } from '../database/entities';

config({ path: getEnvPath() });

export default new DataSource({
  type: 'mysql',
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number.parseInt(process.env.DB_PORT || '3307', 10),
  username: process.env.DB_USERNAME || 'root',
  password: process.env.DB_PASSWORD || '123456',
  database: process.env.DB_DATABASE || 'depopazar',
  entities,
  migrations: [__dirname + '/../migrations/*{.ts,.js}'],
  synchronize: false,
  logging: true,
  charset: 'utf8mb4',
  timezone: '+00:00',
});
