import { Injectable, InternalServerErrorException, NotFoundException, BadRequestException, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { exec } from 'child_process';
import { promisify } from 'util';
import * as fs from 'fs';
import * as path from 'path';

const execAsync = promisify(exec);

@Injectable()
export class BackupService {
  private readonly logger = new Logger(BackupService.name);

  constructor(private configService: ConfigService) {}

  async createBackup(): Promise<string> {
    const dbHost = this.configService.get('DB_HOST');
    const dbPort = this.configService.get('DB_PORT');
    const dbUser = this.configService.get('DB_USERNAME');
    const dbPass = this.configService.get('DB_PASSWORD');
    const dbName = this.configService.get('DB_DATABASE');

    const backupDir = path.join(process.cwd(), 'backups');
    if (!fs.existsSync(backupDir)) {
      fs.mkdirSync(backupDir, { recursive: true });
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `backup-${timestamp}.sql`;
    const filePath = path.join(backupDir, filename);

    // Mysqldump command
    // Note: Assuming mysqldump is in PATH
    // Use proper quoting for password if it contains special characters
    const command = `mysqldump -h ${dbHost} -P ${dbPort} -u ${dbUser} -p"${dbPass}" ${dbName} > "${filePath}"`;

    try {
      await execAsync(command);
      return filename; // Return just filename
    } catch (error) {
      this.logger.error('Backup failed', error instanceof Error ? error.stack : String(error));
      throw new InternalServerErrorException('Backup failed');
    }
  }
  
  async listBackups(): Promise<string[]> {
      const backupDir = path.join(process.cwd(), 'backups');
      if (!fs.existsSync(backupDir)) {
        return [];
      }
      return fs.readdirSync(backupDir).filter(f => f.endsWith('.sql')).sort().reverse();
  }

  async deleteBackup(filename: string): Promise<void> {
    const backupDir = path.join(process.cwd(), 'backups');
    const filePath = path.join(backupDir, filename);
    
    if (filename.includes('..') || filename.includes('/')) {
        throw new BadRequestException('Invalid filename');
    }

    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    } else {
        throw new NotFoundException('Backup not found');
    }
  }
}
