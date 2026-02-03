import { join } from 'node:path';

/**
 * Proje kök dizinini döndürür (.env dosyasının bulunduğu yer).
 * - PROJECT_ROOT ortam değişkeni set edilmişse onu kullanır (taşınabilirlik).
 * - process.cwd() 'backend' ile bitiyorsa bir üst dizini kök kabul eder.
 * - Aksi halde process.cwd() kök kabul edilir.
 */
export function getProjectRoot(): string {
  if (process.env.PROJECT_ROOT) {
    return process.env.PROJECT_ROOT;
  }
  const cwd = process.cwd();
  return cwd.endsWith('backend') ? join(cwd, '..') : cwd;
}

/** Proje kökündeki .env dosyasının tam yolu */
export function getEnvPath(): string {
  return join(getProjectRoot(), '.env');
}
