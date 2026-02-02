/**
 * PM2 ecosystem – DepoPazar backend
 * Her domain/subdomain için ayrı process, ayrı log (izolasyon).
 * Çalıştırmadan önce ilgili .env yüklenmeli (deploy script veya export APP_DOMAIN).
 */
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '.env') });

const appName = process.env.APP_NAME || 'DepoPazar';
const appDomain = process.env.APP_DOMAIN || 'default';
const instanceName = `depopazar-${appDomain.replace(/[^a-z0-9-]/gi, '-')}`;
const logsDir = path.join(__dirname, 'logs');

module.exports = {
  apps: [
    {
      name: instanceName,
      script: 'dist/main.js',
      cwd: __dirname,
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'development',
      },
      env_production: {
        NODE_ENV: 'production',
      },
      out_file: path.join(logsDir, `${instanceName}-out.log`),
      error_file: path.join(logsDir, `${instanceName}-error.log`),
      merge_logs: false,
      time: true,
    },
  ],
};
