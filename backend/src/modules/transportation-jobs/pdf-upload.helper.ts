import { mkdir, writeFile, unlink } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';

const UPLOADS_ROOT = 'uploads';
const TRANSPORTATION_JOBS_DIR = 'transportation-jobs';
const API_UPLOADS_PREFIX = '/api/uploads/';

const ALLOWED_MIMES = ['application/pdf'];
const MAX_SIZE = 10 * 1024 * 1024; // 10MB

export function validatePdfFile(file: Express.Multer.File): void {
  if (!file || !file.buffer) {
    throw new Error('Dosya yüklenmedi');
  }
  if (file.size > MAX_SIZE) {
    throw new Error('PDF dosyası en fazla 10MB olabilir');
  }
  const mime = file.mimetype.split(';')[0].trim().toLowerCase();
  if (!ALLOWED_MIMES.includes(mime)) {
    throw new Error('Sadece PDF dosyaları yüklenebilir');
  }
}

export async function savePdf(
  jobId: string,
  file: Express.Multer.File,
): Promise<string> {
  const dir = join(process.cwd(), UPLOADS_ROOT, TRANSPORTATION_JOBS_DIR, jobId);
  if (!existsSync(dir)) {
    await mkdir(dir, { recursive: true });
  }
  const timestamp = Date.now();
  const filename = `contract_${timestamp}.pdf`;
  const filePath = join(dir, filename);
  await writeFile(filePath, file.buffer);
  return `${API_UPLOADS_PREFIX}${TRANSPORTATION_JOBS_DIR}/${jobId}/${filename}`;
}

export async function removePdfFile(pdfUrl: string | null): Promise<void> {
  if (!pdfUrl || !pdfUrl.startsWith(API_UPLOADS_PREFIX)) return;
  const relative = pdfUrl.slice(API_UPLOADS_PREFIX.length);
  const filePath = join(process.cwd(), UPLOADS_ROOT, relative);
  if (existsSync(filePath)) {
    await unlink(filePath);
  }
}
