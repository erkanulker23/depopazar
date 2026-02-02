import { Controller, Post, Get, Delete, UseGuards, Res, Param, Body, BadRequestException, NotFoundException } from '@nestjs/common';
import { BackupService } from './backup.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { Response } from 'express';
import * as fs from 'fs';
import * as path from 'path';

@ApiTags('Backup')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('backups')
export class BackupController {
  constructor(private readonly backupService: BackupService) {}

  @Post()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Create database backup' })
  async createBackup() {
    const filename = await this.backupService.createBackup();
    return { message: 'Backup created', filename };
  }

  @Get()
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'List backups' })
  async listBackups() {
    return this.backupService.listBackups();
  }
  
  @Get(':filename')
  @Roles(UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'Download backup' })
  async downloadBackup(@Param('filename') filename: string, @Res() res: Response) {
      const backupDir = path.join(process.cwd(), 'backups');
      const filePath = path.join(backupDir, filename);
      
      // Basic path traversal prevention
      if (filename.includes('..') || filename.includes('/')) {
         res.status(400).send('Invalid filename');
         return;
      }
      
      if (!fs.existsSync(filePath)) {
          res.status(404).send('Backup not found');
          return;
      }
      
      res.download(filePath);
  }

  @Delete(':filename')
  @Roles(UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'Delete backup' })
  async deleteBackup(@Param('filename') filename: string) {
      await this.backupService.deleteBackup(filename);
      return { message: 'Backup deleted' };
  }
}
