import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Item } from './entities/item.entity';

@Injectable()
export class ItemsService {
  constructor(
    @InjectRepository(Item)
    private itemsRepository: Repository<Item>,
  ) {}

  async create(createItemDto: Partial<Item>): Promise<Item> {
    const item = this.itemsRepository.create(createItemDto);
    return this.itemsRepository.save(item);
  }

  async findAll(contractId?: string): Promise<Item[]> {
    const where: any = {};
    if (contractId) {
      where.contract_id = contractId;
    }
    return this.itemsRepository.find({
      where,
      relations: ['room', 'contract', 'contract.customer'],
    });
  }

  async findByCustomerId(customerId: string): Promise<Item[]> {
    return this.itemsRepository
      .createQueryBuilder('item')
      .leftJoinAndSelect('item.room', 'room')
      .leftJoinAndSelect('item.contract', 'contract')
      .leftJoinAndSelect('contract.customer', 'customer')
      .where('contract.customer_id = :customerId', { customerId })
      .andWhere('item.removed_at IS NULL')
      .getMany();
  }

  async findOne(id: string): Promise<Item> {
    const item = await this.itemsRepository.findOne({
      where: { id },
      relations: ['room', 'contract', 'contract.customer'],
    });

    if (!item) {
      throw new NotFoundException('Eşya bulunamadı');
    }

    return item;
  }

  async update(id: string, updateData: Partial<Item>): Promise<Item> {
    await this.itemsRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.itemsRepository.delete(id);
  }

  async delete(id: string): Promise<void> {
    await this.itemsRepository.delete(id);
  }
}
