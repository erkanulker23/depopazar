import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Service } from './entities/service.entity';
import { CreateServiceDto } from './dto/create-service.dto';
import { UpdateServiceDto } from './dto/update-service.dto';

@Injectable()
export class ServicesService {
  constructor(
    @InjectRepository(Service)
    private serviceRepository: Repository<Service>,
  ) {}

  async create(companyId: string, createDto: CreateServiceDto): Promise<Service> {
    const service = this.serviceRepository.create({
      ...createDto,
      company_id: companyId,
    });
    return this.serviceRepository.save(service);
  }

  async findAll(companyId: string): Promise<Service[]> {
    return this.serviceRepository.find({
      where: { company_id: companyId },
      relations: ['category'],
      order: { created_at: 'ASC' },
    });
  }

  async findOne(companyId: string, id: string): Promise<Service> {
    const service = await this.serviceRepository.findOne({
      where: { id, company_id: companyId },
      relations: ['category'],
    });
    if (!service) throw new NotFoundException('Hizmet bulunamadı. Belirtilen hizmet silinmiş veya yetkiniz dışında olabilir.');
    return service;
  }

  async update(companyId: string, id: string, updateDto: UpdateServiceDto): Promise<Service> {
    await this.serviceRepository.update({ id, company_id: companyId }, updateDto);
    return this.findOne(companyId, id);
  }

  async remove(companyId: string, id: string): Promise<void> {
    const result = await this.serviceRepository.delete({ id, company_id: companyId });
    if (result.affected === 0) throw new NotFoundException('Hizmet bulunamadı. Belirtilen hizmet silinmiş veya yetkiniz dışında olabilir.');
  }
}
