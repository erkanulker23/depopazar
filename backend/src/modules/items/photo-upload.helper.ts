import { BadRequestException } from '@nestjs/common';
import { mkdir, writeFile } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';
import { randomUUID } from 'crypto';

const UPLOADS_ROOT = 'uploads';
const ITEMS_DIR = 'items';
const API_UPLOADS_PREFIX = '/api/uploads/';

const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MAX_SIZE = 5 * 1024 * 1024; // 5MB per image

function extFromMime(mime: string): string {
  const base = mime.split(';')[0].trim().toLowerCase();
  const map: Record<string, string> = {
    'image/jpeg': '.jpg',
    'image/png': '.png',
    'image/gif': '.gif',
    'image/webp': '.webp',
  };
  return map[base] || '.jpg';
}

export function validateItemPhoto(file: Express.Multer.File): void {
  if (!file || !file.buffer) {
    throw new BadRequestException('Dosya yüklenmedi');
  }
  if (file.size > MAX_SIZE) {
    throw new BadRequestException('Eşya fotoğrafı en fazla 5MB olabilir');
  }
  const mime = file.mimetype.split(';')[0].trim().toLowerCase();
  if (!ALLOWED_MIMES.includes(mime)) {
    throw new BadRequestException('Sadece resim dosyaları (JPEG, PNG, GIF, WebP) yüklenebilir');
  }
}

export async function saveItemPhoto(file: Express.Multer.File): Promise<string> {
  const dir = join(process.cwd(), UPLOADS_ROOT, ITEMS_DIR);
  if (!existsSync(dir)) {
    await mkdir(dir, { recursive: true });
  }
  const ext = extFromMime(file.mimetype);
  const filename = `${Date.now()}-${randomUUID().slice(0, 8)}${ext}`;
  const filePath = join(dir, filename);
  await writeFile(filePath, file.buffer);
  return `${API_UPLOADS_PREFIX}${ITEMS_DIR}/${filename}`;
}
