import { Injectable, NotFoundException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Warehouse } from './entities/warehouse.entity';
import { Room } from '../rooms/entities/room.entity';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';

@Injectable()
export class WarehousesService {
  private readonly logger = new Logger(WarehousesService.name);

  constructor(
    @InjectRepository(Warehouse)
    private warehousesRepository: Repository<Warehouse>,
    @InjectRepository(Room)
    private roomsRepository: Repository<Room>,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
  ) {}

  async create(createWarehouseDto: Partial<Warehouse>): Promise<Warehouse> {
    const warehouse = this.warehousesRepository.create(createWarehouseDto);
    const savedWarehouse = await this.warehousesRepository.save(warehouse);

    try {
      const usersToNotify: Array<{ id: string }> = [];
      if (savedWarehouse.company_id) {
        usersToNotify.push(...(await this.usersService.findByCompanyId(savedWarehouse.company_id)));
      }
      usersToNotify.push(...(await this.usersService.findAllSuperAdmins()));
      for (const user of usersToNotify) {
        try {
          await this.notificationsService.create({
            user_id: user.id,
            type: NotificationType.WAREHOUSE_CREATED,
            title: 'Yeni Depo Eklendi',
            message: `${savedWarehouse.name} adlı yeni depo sisteme eklendi.`,
            is_read: false,
            metadata: {
              warehouse_id: savedWarehouse.id,
              warehouse_name: savedWarehouse.name,
            },
          });
        } catch (error: unknown) {
          this.logger.warn(`Notification failed for user ${user.id}: ${error instanceof Error ? error.message : String(error)}`);
        }
      }
    } catch (error: unknown) {
      this.logger.warn('Notification creation failed', error instanceof Error ? error.message : String(error));
    }

    return savedWarehouse;
  }

  async findAll(companyId?: string): Promise<Warehouse[]> {
    const where = companyId ? { company_id: companyId } : {};
    return this.warehousesRepository.find({
      where,
      relations: ['company', 'rooms', 'rooms.contracts', 'rooms.contracts.customer'],
    });
  }

  async findOne(id: string): Promise<Warehouse> {
    const warehouse = await this.warehousesRepository.findOne({
      where: { id },
      relations: ['company', 'rooms', 'rooms.contracts', 'rooms.contracts.customer'],
    });

    if (!warehouse) {
      throw new NotFoundException('Depo bulunamadı');
    }

    return warehouse;
  }

  async update(id: string, updateData: Partial<Warehouse>): Promise<Warehouse> {
    await this.warehousesRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const warehouse = await this.findOne(id);
    
    // Bildirim oluştur: Depo silindiğinde
    const usersToNotify: any[] = [];
    
    // Şirket kullanıcılarına bildirim gönder
    if (warehouse.company_id) {
      const companyUsers = await this.usersService.findByCompanyId(warehouse.company_id);
      usersToNotify.push(...companyUsers);
    }
    
    // Super admin kullanıcılarına da bildirim gönder
    const superAdmins = await this.usersService.findAllSuperAdmins();
    usersToNotify.push(...superAdmins);
    
    // Tüm kullanıcılara bildirim gönder
    for (const user of usersToNotify) {
      await this.notificationsService.create({
        user_id: user.id,
        type: NotificationType.WAREHOUSE_DELETED,
        title: 'Depo Silindi',
        message: `${warehouse.name} adlı depo sistemden silindi.`,
        is_read: false,
        metadata: {
          warehouse_id: warehouse.id,
          warehouse_name: warehouse.name,
        },
      });
    }

    await this.roomsRepository.delete({ warehouse_id: id });
    await this.warehousesRepository.delete(id);
  }
}
