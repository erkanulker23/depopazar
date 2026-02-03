import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  envDir: path.resolve(__dirname, '..'),
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3180,
    strictPort: true,
    allowedHosts: [
      'depotakip-v1.test',
      '.depotakip-v1.test',
      'localhost',
    ],
    proxy: {
      '/api': {
        target: 'http://localhost:4100',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
