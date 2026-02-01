import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Room } from './entities/room.entity';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';

@Injectable()
export class RoomsService {
  constructor(
    @InjectRepository(Room)
    private roomsRepository: Repository<Room>,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
  ) {}

  async create(createRoomDto: Partial<Room>): Promise<Room> {
    const room = this.roomsRepository.create(createRoomDto);
    const savedRoom = await this.roomsRepository.save(room);

    // Bildirim oluştur: Oda eklendiğinde
    const roomWithWarehouse = await this.roomsRepository.findOne({
      where: { id: savedRoom.id },
      relations: ['warehouse'],
    });

    try {
      console.log(`[RoomsService] Creating room - warehouse_id: ${savedRoom.warehouse_id}`);
      const usersToNotify: any[] = [];
      
      // Şirket kullanıcılarına bildirim gönder
      if (roomWithWarehouse?.warehouse?.company_id) {
        const companyUsers = await this.usersService.findByCompanyId(roomWithWarehouse.warehouse.company_id);
        usersToNotify.push(...companyUsers);
        console.log(`[RoomsService] Found ${companyUsers.length} company users`);
      }
      
      // Super admin kullanıcılarına da bildirim gönder
      const superAdmins = await this.usersService.findAllSuperAdmins();
      usersToNotify.push(...superAdmins);
      console.log(`[RoomsService] Found ${superAdmins.length} super admin users`);
      
      // Tüm kullanıcılara bildirim gönder
      for (const user of usersToNotify) {
        try {
          console.log(`[RoomsService] Creating notification for user: ${user.email}`);
          await this.notificationsService.create({
            user_id: user.id,
            type: NotificationType.ROOM_CREATED,
            title: 'Yeni Oda Eklendi',
            message: `${savedRoom.room_number} numaralı oda sisteme eklendi.`,
            is_read: false,
            metadata: {
              room_id: savedRoom.id,
              room_number: savedRoom.room_number,
              warehouse_id: savedRoom.warehouse_id,
            },
          });
          console.log(`[RoomsService] Notification created successfully`);
        } catch (error: any) {
          console.error(`[RoomsService] Error creating notification:`, error?.message || error);
        }
      }
    } catch (error: any) {
      console.error('[RoomsService] Error in notification creation:', error?.message || error);
    }

    return savedRoom;
  }

  async findAll(warehouseId?: string): Promise<Room[]> {
    const where = warehouseId ? { warehouse_id: warehouseId } : {};
    return this.roomsRepository.find({
      where,
      relations: ['warehouse', 'contracts', 'contracts.customer', 'contracts.payments'],
    });
  }

  async findOne(id: string): Promise<Room> {
    const room = await this.roomsRepository.findOne({
      where: { id },
      relations: ['warehouse', 'contracts', 'contracts.customer', 'contracts.payments'],
    });

    if (!room) {
      throw new NotFoundException('Oda bulunamadı');
    }

    return room;
  }

  async update(id: string, updateData: Partial<Room>): Promise<Room> {
    await this.roomsRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const room = await this.roomsRepository.findOne({
      where: { id },
      relations: ['contracts', 'warehouse'],
    });
    if (!room) throw new NotFoundException('Room not found');
    const hasActiveContract = (room.contracts ?? []).some((c: any) => c.is_active);
    if (hasActiveContract) {
      throw new BadRequestException(
        'Bu odada müşteri var. Odayı silebilmek için önce sözleşmeyi sonlandırmanız gerekiyor.',
      );
    }

    // Bildirim oluştur: Oda silindiğinde
    const usersToNotify: any[] = [];
    
    // Şirket kullanıcılarına bildirim gönder
    if (room.warehouse?.company_id) {
      const companyUsers = await this.usersService.findByCompanyId(room.warehouse.company_id);
      usersToNotify.push(...companyUsers);
    }
    
    // Super admin kullanıcılarına da bildirim gönder
    const superAdmins = await this.usersService.findAllSuperAdmins();
    usersToNotify.push(...superAdmins);
    
    // Tüm kullanıcılara bildirim gönder
    for (const user of usersToNotify) {
      await this.notificationsService.create({
        user_id: user.id,
        type: NotificationType.ROOM_DELETED,
        title: 'Oda Silindi',
        message: `${room.room_number} numaralı oda sistemden silindi.`,
        is_read: false,
        metadata: {
          room_id: room.id,
          room_number: room.room_number,
          warehouse_id: room.warehouse_id,
        },
      });
    }

    await this.roomsRepository.delete(id);
  }
}
