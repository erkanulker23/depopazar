module.exports = {
  apps: [
    {
      name: 'depopazar-backend',
      script: 'dist/main.js',
      instances: 1, // Or 'max' for cluster mode
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'development',
      },
      env_production: {
        NODE_ENV: 'production',
      },
    },
  ],
};
