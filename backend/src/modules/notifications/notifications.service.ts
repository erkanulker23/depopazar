import { Injectable, NotFoundException, BadRequestException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { In, Repository } from 'typeorm';
import { Notification } from './entities/notification.entity';

@Injectable()
export class NotificationsService {
  private readonly logger = new Logger(NotificationsService.name);

  constructor(
    @InjectRepository(Notification)
    private notificationsRepository: Repository<Notification>,
  ) {}

  async create(createNotificationDto: Partial<Notification>): Promise<Notification> {
    try {
      if (!createNotificationDto.user_id) {
        throw new BadRequestException('user_id is required for notification');
      }
      const notification = this.notificationsRepository.create(createNotificationDto);
      const saved = await this.notificationsRepository.save(notification);
      return saved;
    } catch (error: unknown) {
      if (error instanceof BadRequestException) throw error;
      this.logger.error('Error creating notification', error instanceof Error ? error.stack : String(error));
      throw error;
    }
  }

  async findAll(userId?: string, customerId?: string): Promise<Notification[]> {
    const queryBuilder = this.notificationsRepository.createQueryBuilder('notification');
    if (userId) {
      queryBuilder.where('notification.user_id = :userId', { userId });
    }
    if (customerId) {
      queryBuilder.andWhere('notification.customer_id = :customerId', { customerId });
    }
    queryBuilder.andWhere('notification.deleted_at IS NULL');
    queryBuilder.orderBy('notification.created_at', 'DESC');
    return queryBuilder.getMany();
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

  async markAllAsRead(userId: string): Promise<{ count: number }> {
    const result = await this.notificationsRepository.update(
      { user_id: userId, is_read: false },
      { is_read: true, read_at: new Date() },
    );
    return { count: result.affected ?? 0 };
  }

  async removeAll(userId: string): Promise<{ count: number }> {
    const toDelete = await this.notificationsRepository.find({
      where: { user_id: userId },
      select: ['id'],
    });
    const ids = toDelete.map((n) => n.id);
    if (ids.length > 0) {
      await this.notificationsRepository.delete({ id: In(ids) });
    }
    return { count: ids.length };
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
