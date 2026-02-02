import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ServiceCategory } from './entities/service-category.entity';
import { CreateServiceCategoryDto } from './dto/create-service-category.dto';
import { UpdateServiceCategoryDto } from './dto/update-service-category.dto';

@Injectable()
export class ServiceCategoriesService {
  constructor(
    @InjectRepository(ServiceCategory)
    private categoryRepository: Repository<ServiceCategory>,
  ) {}

  async create(companyId: string, createDto: CreateServiceCategoryDto): Promise<ServiceCategory> {
    const category = this.categoryRepository.create({
      ...createDto,
      company_id: companyId,
    });
    return this.categoryRepository.save(category);
  }

  async findAll(companyId: string): Promise<ServiceCategory[]> {
    return this.categoryRepository.find({
      where: { company_id: companyId },
      relations: ['services'],
      order: { created_at: 'ASC' },
    });
  }

  async findOne(companyId: string, id: string): Promise<ServiceCategory> {
    const category = await this.categoryRepository.findOne({
      where: { id, company_id: companyId },
      relations: ['services'],
    });
    if (!category) throw new NotFoundException('Category not found');
    return category;
  }

  async update(companyId: string, id: string, updateDto: UpdateServiceCategoryDto): Promise<ServiceCategory> {
    await this.categoryRepository.update({ id, company_id: companyId }, updateDto);
    return this.findOne(companyId, id);
  }

  async remove(companyId: string, id: string): Promise<void> {
    const result = await this.categoryRepository.delete({ id, company_id: companyId });
    if (result.affected === 0) throw new NotFoundException('Category not found');
  }
}
