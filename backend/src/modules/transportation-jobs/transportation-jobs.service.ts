import { Injectable, NotFoundException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { TransportationJob } from './entities/transportation-job.entity';
import { TransportationJobStaff } from './entities/transportation-job-staff.entity';
import { NotificationsService } from '../notifications/notifications.service';
import { UsersService } from '../users/users.service';
import { NotificationType } from '../../common/enums/notification-type.enum';
import {
  type PaginationParams,
  type PaginatedResult,
  parsePagination,
  toPaginatedResult,
} from '../../common/utils/pagination';

@Injectable()
export class TransportationJobsService {
  private readonly logger = new Logger(TransportationJobsService.name);

  constructor(
    @InjectRepository(TransportationJob)
    private transportationJobsRepository: Repository<TransportationJob>,
    @InjectRepository(TransportationJobStaff)
    private transportationJobStaffRepository: Repository<TransportationJobStaff>,
    private readonly notificationsService: NotificationsService,
    private readonly usersService: UsersService,
  ) {}

  async create(createTransportationJobDto: any): Promise<TransportationJob> {
    const { staff_ids, ...jobData } = createTransportationJobDto;
    
    const job = this.transportationJobsRepository.create(jobData);
    const savedJob = (await this.transportationJobsRepository.save(job)) as unknown as TransportationJob;

    // Personelleri ekle (çoklu)
    if (staff_ids && Array.isArray(staff_ids) && staff_ids.length > 0) {
      const staffEntities = staff_ids.map((userId: string) =>
        this.transportationJobStaffRepository.create({
          transportation_job_id: savedJob.id,
          user_id: userId,
        }),
      );
      await this.transportationJobStaffRepository.save(staffEntities);
    }

    const jobWithRelations = await this.findOne(savedJob.id);

    try {
      const usersToNotify: Array<{ id: string }> = [];
      if (jobWithRelations.company_id) {
        usersToNotify.push(...(await this.usersService.findByCompanyId(jobWithRelations.company_id)));
      }
      usersToNotify.push(...(await this.usersService.findAllSuperAdmins()));
      const paymentStatus = jobWithRelations.is_paid ? 'Ödeme alındı' : 'Ödeme alınmadı';
      const customerName = jobWithRelations.customer
        ? `${jobWithRelations.customer.first_name} ${jobWithRelations.customer.last_name}`
        : 'Bilinmeyen Müşteri';
      for (const user of usersToNotify) {
        try {
          await this.notificationsService.create({
            user_id: user.id,
            customer_id: jobWithRelations.customer?.id || null,
            type: NotificationType.TRANSPORTATION_JOB_CREATED,
            title: 'Yeni Nakliye İşi Oluşturuldu',
            message: `${customerName} adlı müşteri için yeni nakliye işi oluşturuldu. Ödeme durumu: ${paymentStatus}.`,
            is_read: false,
            metadata: {
              transportation_job_id: jobWithRelations.id,
              customer_id: jobWithRelations.customer?.id || null,
              customer_name: customerName,
              pickup_address: jobWithRelations.pickup_address,
              delivery_address: jobWithRelations.delivery_address,
              price: jobWithRelations.price,
              is_paid: jobWithRelations.is_paid,
            },
          });
        } catch (error: unknown) {
          this.logger.warn(`Notification failed for user ${user.id}: ${error instanceof Error ? error.message : String(error)}`);
        }
      }
    } catch (error: unknown) {
      this.logger.warn('Notification creation failed', error instanceof Error ? error.message : String(error));
    }

    return jobWithRelations;
  }

  async findAll(
    companyId?: string,
    pagination?: PaginationParams,
    year?: number,
    month?: number,
  ): Promise<PaginatedResult<TransportationJob>> {
    try {
      const params = pagination || parsePagination();
      const { skip, take } = params;
      
      const queryBuilder = this.transportationJobsRepository
        .createQueryBuilder('job')
        .leftJoinAndSelect('job.customer', 'customer')
        .leftJoinAndSelect('customer.user', 'user')
        .leftJoinAndSelect('job.staff', 'staff')
        .leftJoinAndSelect('staff.user', 'staffUser')
        .orderBy('job.created_at', 'DESC');

      // Filtreleri birleştir
      const conditions: string[] = [];
      const queryParams: Record<string, any> = {};

      if (companyId) {
        conditions.push('job.company_id = :companyId');
        queryParams.companyId = companyId;
      }

      if (year !== undefined) {
        conditions.push('job.job_date IS NOT NULL AND YEAR(job.job_date) = :year');
        queryParams.year = year;
      }

      if (month !== undefined) {
        conditions.push('job.job_date IS NOT NULL AND MONTH(job.job_date) = :month');
        queryParams.month = month;
      }

      if (conditions.length > 0) {
        queryBuilder.where(conditions.join(' AND '), queryParams);
      }

      const [data, total] = await queryBuilder.skip(skip).take(take).getManyAndCount();

      return toPaginatedResult(data, total, params);
    } catch (error: unknown) {
      this.logger.error('Error in findAll', error instanceof Error ? error.stack : String(error));
      throw error;
    }
  }

  async findOne(id: string): Promise<TransportationJob> {
    const job = await this.transportationJobsRepository.findOne({
      where: { id },
      relations: ['customer', 'customer.user', 'staff', 'staff.user'],
    });

    if (!job) {
      throw new NotFoundException('Nakliye işi bulunamadı');
    }

    return job;
  }

  async findByCustomerId(customerId: string): Promise<TransportationJob[]> {
    return this.transportationJobsRepository.find({
      where: { customer_id: customerId },
      relations: ['customer', 'customer.user', 'staff', 'staff.user'],
      order: { created_at: 'DESC' },
    });
  }

  async update(id: string, updateTransportationJobDto: any): Promise<TransportationJob> {
    const { staff_ids, ...jobData } = updateTransportationJobDto;
    const job = await this.findOne(id);
    
    Object.assign(job, jobData);
    const savedJob = await this.transportationJobsRepository.save(job);

    // Mevcut personelleri sil
    await this.transportationJobStaffRepository.delete({ transportation_job_id: id });

    // Yeni personelleri ekle
    if (staff_ids && Array.isArray(staff_ids) && staff_ids.length > 0) {
      const staffEntities = staff_ids.map((userId: string) =>
        this.transportationJobStaffRepository.create({
          transportation_job_id: id,
          user_id: userId,
        }),
      );
      await this.transportationJobStaffRepository.save(staffEntities);
    }

    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const job = await this.findOne(id);
    await this.transportationJobsRepository.softDelete(id);
  }
}
