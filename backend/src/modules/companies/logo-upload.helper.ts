import { BadRequestException } from '@nestjs/common';
import { mkdir, writeFile, unlink } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';

const UPLOADS_ROOT = 'uploads';
const COMPANIES_DIR = 'companies';
const API_UPLOADS_PREFIX = '/api/uploads/';

const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MAX_SIZE = 2 * 1024 * 1024; // 2MB

function extFromMime(mime: string): string {
  const base = mime.split(';')[0].trim().toLowerCase();
  const map: Record<string, string> = {
    'image/jpeg': '.jpg',
    'image/png': '.png',
    'image/gif': '.gif',
    'image/webp': '.webp',
  };
  return map[base] || '.png';
}

export function validateLogoFile(file: Express.Multer.File): void {
  if (!file || !file.buffer) {
    throw new BadRequestException('Dosya yüklenmedi');
  }
  if (file.size > MAX_SIZE) {
    throw new BadRequestException('Logo en fazla 2MB olabilir');
  }
  const mime = file.mimetype.split(';')[0].trim().toLowerCase();
  if (!ALLOWED_MIMES.includes(mime)) {
    throw new BadRequestException('Sadece resim dosyaları (JPEG, PNG, GIF, WebP) yüklenebilir');
  }
}

export async function saveLogo(
  companyId: string,
  file: Express.Multer.File,
): Promise<string> {
  const dir = join(process.cwd(), UPLOADS_ROOT, COMPANIES_DIR, companyId);
  if (!existsSync(dir)) {
    await mkdir(dir, { recursive: true });
  }
  const ext = extFromMime(file.mimetype);
  const filename = `logo${ext}`;
  const filePath = join(dir, filename);
  await writeFile(filePath, file.buffer);
  return `${API_UPLOADS_PREFIX}${COMPANIES_DIR}/${companyId}/${filename}`;
}

export async function removeLogoFile(logoUrl: string | null): Promise<void> {
  if (!logoUrl || !logoUrl.startsWith(API_UPLOADS_PREFIX)) return;
  const relative = logoUrl.slice(API_UPLOADS_PREFIX.length);
  const filePath = join(process.cwd(), UPLOADS_ROOT, relative);
  if (existsSync(filePath)) {
    await unlink(filePath);
  }
}
