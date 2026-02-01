import { Controller, Get, Post, Body, Patch, Param, Delete, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { NotificationsService } from './notifications.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@ApiTags('Notifications')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('notifications')
export class NotificationsController {
  constructor(private readonly notificationsService: NotificationsService) {}

  @Get()
  @ApiOperation({ summary: 'Get all notifications for current user' })
  async findAll(@CurrentUser() user: any) {
    console.log(`[NotificationsController] Getting notifications for user:`, {
      id: user?.id,
      email: user?.email,
      role: user?.role,
      company_id: user?.company_id,
    });
    
    if (!user?.id) {
      console.error('[NotificationsController] No user ID found!');
      return [];
    }
    
    const notifications = await this.notificationsService.findAll(user.id);
    console.log(`[NotificationsController] Returning ${notifications.length} notifications for user ${user.id}`);
    
    if (notifications.length > 0) {
      console.log(`[NotificationsController] First notification:`, {
        id: notifications[0].id,
        user_id: notifications[0].user_id,
        title: notifications[0].title,
        type: notifications[0].type,
      });
    }
    
    return notifications;
  }

  @Get('all')
  @ApiOperation({ summary: 'Get all notifications (for debugging)' })
  async findAllNotifications(@CurrentUser() user: any) {
    return this.notificationsService.findAllNotificationsForCompany(user?.company_id);
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
