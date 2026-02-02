import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { NotificationsService } from './notifications.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/user-role.enum';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@ApiTags('Notifications')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('notifications')
export class NotificationsController {
  constructor(private readonly notificationsService: NotificationsService) {}

  @Get()
  @ApiOperation({ summary: 'Get all notifications for current user' })
  async findAll(@CurrentUser() user: any) {
    if (!user?.id) {
      return [];
    }
    
    return this.notificationsService.findAll(user.id);
  }

  @Get('all')
  @Roles(UserRole.SUPER_ADMIN, UserRole.COMPANY_OWNER)
  @ApiOperation({ summary: 'Get all notifications (for debugging)' })
  async findAllNotifications(@CurrentUser() user: any) {
    return this.notificationsService.findAllNotificationsForCompany(user?.company_id);
  }

  @Patch('mark-all-read')
  @ApiOperation({ summary: 'Mark all notifications as read' })
  async markAllAsRead(@CurrentUser() user: any) {
    if (!user?.id) return { count: 0 };
    return this.notificationsService.markAllAsRead(user.id);
  }

  @Delete('delete-all')
  @ApiOperation({ summary: 'Delete all notifications for current user' })
  async deleteAll(@CurrentUser() user: any) {
    if (!user?.id) return { count: 0 };
    return this.notificationsService.removeAll(user.id);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get notification by ID' })
  async findOne(@Param('id') id: string) {
    return this.notificationsService.findOne(id);
  }

  @Patch(':id/read')
  @ApiOperation({ summary: 'Mark notification as read' })
  async markAsRead(@Param('id') id: string) {
    return this.notificationsService.markAsRead(id);
  }
}
