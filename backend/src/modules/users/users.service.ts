import { Injectable, ConflictException, NotFoundException, ForbiddenException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { User } from './entities/user.entity';
import { RegisterDto } from '../auth/dto/register.dto';
import { UserRole } from '../../common/enums/user-role.enum';
import { NotificationsService } from '../notifications/notifications.service';
import { NotificationType } from '../../common/enums/notification-type.enum';
import { CompaniesService } from '../companies/companies.service';

@Injectable()
export class UsersService {
  private readonly logger = new Logger(UsersService.name);

  constructor(
    @InjectRepository(User)
    private usersRepository: Repository<User>,
    private readonly notificationsService: NotificationsService,
    private readonly companiesService: CompaniesService,
  ) {}

  async create(registerDto: RegisterDto): Promise<User> {
    const existingUser = await this.usersRepository.findOne({
      where: { email: registerDto.email },
    });

    if (existingUser) {
      throw new ConflictException('User with this email already exists');
    }

    const user = this.usersRepository.create({
      ...registerDto,
      role: registerDto.role || UserRole.CUSTOMER,
    });

    await user.hashPassword();
    const savedUser = await this.usersRepository.save(user);

    // Bildirim oluştur: Personel eklendiğinde (sadece COMPANY_STAFF için)
    try {
      const staffRoles = [UserRole.COMPANY_STAFF, UserRole.DATA_ENTRY, UserRole.ACCOUNTING];
      if (staffRoles.includes(savedUser.role) && savedUser.company_id) {
        const companyUsers = await this.findByCompanyId(savedUser.company_id);
        const usersToNotify = companyUsers.filter(u => u.id !== savedUser.id);
        const superAdmins = await this.findAllSuperAdmins();
        const allToNotify = [...usersToNotify, ...superAdmins];
        for (const user of allToNotify) {
          try {
            await this.notificationsService.create({
              user_id: user.id,
              type: NotificationType.STAFF_CREATED,
              title: 'Yeni Personel Eklendi',
              message: `${savedUser.first_name} ${savedUser.last_name} adlı yeni personel sisteme eklendi.`,
              is_read: false,
              metadata: {
                staff_id: savedUser.id,
                staff_name: `${savedUser.first_name} ${savedUser.last_name}`,
                staff_email: savedUser.email,
              },
            });
          } catch (notifError: unknown) {
            this.logger.warn(`Notification failed for user ${user.id}: ${notifError instanceof Error ? notifError.message : String(notifError)}`);
          }
        }
      }
    } catch (error: unknown) {
      this.logger.warn('Notification creation failed', error instanceof Error ? error.message : String(error));
    }

    return savedUser;
  }

  async findAll(companyId?: string): Promise<User[]> {
    if (companyId) {
      return this.usersRepository.find({
        where: { company_id: companyId },
        relations: ['company'],
      });
    }
    return this.usersRepository.find({
      relations: ['company'],
    });
  }

  async findOne(id: string): Promise<User> {
    const user = await this.usersRepository.findOne({
      where: { id },
      relations: ['company'],
    });

    if (!user) {
      throw new NotFoundException('Kullanıcı bulunamadı');
    }

    return user;
  }

  async findByEmail(email: string): Promise<User | null> {
    return this.usersRepository.findOne({
      where: { email },
      relations: ['company'],
    });
  }

  async findByCompanyId(companyId: string): Promise<User[]> {
    return this.usersRepository.find({
      where: { company_id: companyId, is_active: true },
      relations: ['company'],
    });
  }

  async findAllSuperAdmins(): Promise<User[]> {
    return this.usersRepository.find({
      where: { role: UserRole.SUPER_ADMIN, is_active: true },
      relations: ['company'],
    });
  }

  async updateLastLogin(id: string): Promise<void> {
    await this.usersRepository.update(id, {
      last_login_at: new Date(),
    });
  }

  async update(id: string, updateData: Partial<User>, requestingUser?: { role: string; company_id: string | null }): Promise<User> {
    const user = await this.findOne(id);

    // Role değişikliği için authorization check
    if (updateData.role && updateData.role !== user.role) {
      if (!requestingUser) {
        throw new ForbiddenException('Role değiştirmek için yetkiniz yok');
      }
      if (requestingUser.role !== UserRole.SUPER_ADMIN && requestingUser.role !== UserRole.COMPANY_OWNER) {
        throw new ForbiddenException('Sadece SUPER_ADMIN ve COMPANY_OWNER role değiştirebilir');
      }
      // COMPANY_OWNER sadece kendi şirketindeki kullanıcıların rolünü değiştirebilir
      if (requestingUser.role === UserRole.COMPANY_OWNER && user.company_id !== requestingUser.company_id) {
        throw new ForbiddenException('Sadece kendi şirketinizdeki kullanıcıların rolünü değiştirebilirsiniz');
      }
    }

    // Check if email is being changed and if it conflicts with existing user
    if (updateData.email && updateData.email !== user.email) {
      const existingUser = await this.usersRepository.findOne({
        where: { email: updateData.email },
      });

      if (existingUser) {
        throw new ConflictException('User with this email already exists');
      }
    }

    // Handle password hashing if password is being updated
    if (updateData.password) {
      const tempUser = this.usersRepository.create({ password: updateData.password });
      await tempUser.hashPassword();
      updateData.password = tempUser.password;
    }

    await this.usersRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const user = await this.findOne(id);
    
    // Bildirim oluştur: Personel silindiğinde
    const staffRoles = [UserRole.COMPANY_STAFF, UserRole.DATA_ENTRY, UserRole.ACCOUNTING];
    if (staffRoles.includes(user.role) && user.company_id) {
      const usersToNotify: any[] = [];
      
      // Şirket kullanıcılarına bildirim gönder
      const companyUsers = await this.findByCompanyId(user.company_id);
      usersToNotify.push(...companyUsers.filter(u => u.id !== user.id));
      
      // Super admin kullanıcılarına da bildirim gönder
      const superAdmins = await this.findAllSuperAdmins();
      usersToNotify.push(...superAdmins);
      
      // Tüm kullanıcılara bildirim gönder
      for (const notifyUser of usersToNotify) {
        await this.notificationsService.create({
          user_id: notifyUser.id,
          type: NotificationType.STAFF_DELETED,
          title: 'Personel Silindi',
          message: `${user.first_name} ${user.last_name} adlı personel sistemden silindi.`,
          is_read: false,
          metadata: {
            staff_id: user.id,
            staff_name: `${user.first_name} ${user.last_name}`,
            staff_email: user.email,
          },
        });
      }
    }

    await this.usersRepository.softDelete(id);
  }
}
