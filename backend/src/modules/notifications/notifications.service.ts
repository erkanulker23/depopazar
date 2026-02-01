import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Notification } from './entities/notification.entity';

@Injectable()
export class NotificationsService {
  constructor(
    @InjectRepository(Notification)
    private notificationsRepository: Repository<Notification>,
  ) {}

  async create(createNotificationDto: Partial<Notification>): Promise<Notification> {
    console.log('[NotificationsService] Creating notification:', {
      user_id: createNotificationDto.user_id,
      type: createNotificationDto.type,
      title: createNotificationDto.title,
      message: createNotificationDto.message?.substring(0, 50) + '...',
    });
    try {
      if (!createNotificationDto.user_id) {
        console.error('[NotificationsService] ERROR: user_id is missing!');
        throw new Error('user_id is required for notification');
      }
      
      const notification = this.notificationsRepository.create(createNotificationDto);
      const saved = await this.notificationsRepository.save(notification);
      console.log('[NotificationsService] Notification created successfully:', {
        id: saved.id,
        user_id: saved.user_id,
        type: saved.type,
        title: saved.title,
      });
      return saved;
    } catch (error: any) {
      console.error('[NotificationsService] Error creating notification:', {
        message: error?.message,
        stack: error?.stack,
        dto: createNotificationDto,
      });
      throw error;
    }
  }

  async findAll(userId?: string, customerId?: string): Promise<Notification[]> {
    console.log(`[NotificationsService] Finding notifications - userId: ${userId}, customerId: ${customerId}`);
    const queryBuilder = this.notificationsRepository.createQueryBuilder('notification');
    if (userId) {
      queryBuilder.where('notification.user_id = :userId', { userId });
    }
    if (customerId) {
      queryBuilder.andWhere('notification.customer_id = :customerId', { customerId });
    }
    queryBuilder.andWhere('notification.deleted_at IS NULL');
    queryBuilder.orderBy('notification.created_at', 'DESC');
    
    const notifications = await queryBuilder.getMany();
    console.log(`[NotificationsService] Found ${notifications.length} notifications`);
    if (notifications.length > 0) {
      console.log(`[NotificationsService] First notification:`, {
        id: notifications[0].id,
        user_id: notifications[0].user_id,
        title: notifications[0].title,
        type: notifications[0].type,
      });
    }
    return notifications;
  }

  async findOne(id: string): Promise<Notification> {
    const notification = await this.notificationsRepository.findOne({
      where: { id },
    });

    if (!notification) {
      throw new NotFoundException('Bildirim bulunamadı');
    }

    return notification;
  }

  async markAsRead(id: string): Promise<Notification> {
    await this.notificationsRepository.update(id, {
      is_read: true,
      read_at: new Date(),
    });
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.notificationsRepository.delete(id);
  }

  async findAllNotificationsForCompany(companyId?: string): Promise<Notification[]> {
    const queryBuilder = this.notificationsRepository.createQueryBuilder('notification')
      .leftJoinAndSelect('notification.user', 'user')
      .where('notification.deleted_at IS NULL');

    if (companyId) {
      queryBuilder.andWhere('user.company_id = :companyId', { companyId });
    }

    queryBuilder.orderBy('notification.created_at', 'DESC');
    return queryBuilder.getMany();
  }
}
