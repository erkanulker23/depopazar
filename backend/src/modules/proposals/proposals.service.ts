import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Proposal } from './entities/proposal.entity';
import { ProposalItem } from './entities/proposal-item.entity';
import { CreateProposalDto } from './dto/create-proposal.dto';
import { UpdateProposalDto } from './dto/update-proposal.dto';

@Injectable()
export class ProposalsService {
  constructor(
    @InjectRepository(Proposal)
    private proposalRepository: Repository<Proposal>,
    @InjectRepository(ProposalItem)
    private proposalItemRepository: Repository<ProposalItem>,
  ) {}

  async create(companyId: string, createDto: CreateProposalDto): Promise<Proposal> {
    const { items, ...proposalData } = createDto;
    
    // Calculate total amount
    let totalAmount = 0;
    const proposalItems = items.map(item => {
      const totalPrice = item.quantity * item.unit_price;
      totalAmount += totalPrice;
      return this.proposalItemRepository.create({
        ...item,
        total_price: totalPrice,
      });
    });

    const proposal = this.proposalRepository.create({
      ...proposalData,
      company_id: companyId,
      total_amount: totalAmount,
      items: proposalItems,
    });

    return this.proposalRepository.save(proposal);
  }

  async findAll(companyId: string): Promise<Proposal[]> {
    return this.proposalRepository.find({
      where: { company_id: companyId },
      relations: ['customer', 'items'],
      order: { created_at: 'DESC' },
    });
  }

  async findOne(companyId: string, id: string): Promise<Proposal> {
    const proposal = await this.proposalRepository.findOne({
      where: { id, company_id: companyId },
      relations: ['customer', 'items'],
    });

    if (!proposal) {
      throw new NotFoundException('Proposal not found');
    }

    return proposal;
  }

  async update(companyId: string, id: string, updateDto: UpdateProposalDto): Promise<Proposal> {
    const proposal = await this.findOne(companyId, id);
    
    // Note: This is a simplified update. For full update (items), we'd need more logic.
    // For now, allow updating status, notes, title, etc.
    // If items are provided, we should replace them (or handle complex diff).
    // Let's assume we re-calculate total if items are passed.

    if (updateDto.items) {
       // Delete old items
       await this.proposalItemRepository.delete({ proposal_id: id });
       
       let totalAmount = 0;
       const newItems = updateDto.items.map(item => {
         const totalPrice = item.quantity * item.unit_price;
         totalAmount += totalPrice;
         return this.proposalItemRepository.create({
           ...item,
           proposal_id: id,
           total_price: totalPrice,
         });
       });
       
       await this.proposalItemRepository.save(newItems);
       await this.proposalRepository.update({ id, company_id: companyId }, {
         ...updateDto,
         total_amount: totalAmount,
         items: undefined, // Don't pass items to update
       });
    } else {
       await this.proposalRepository.update({ id, company_id: companyId }, updateDto);
    }

    return this.findOne(companyId, id);
  }

  async remove(companyId: string, id: string): Promise<void> {
    const result = await this.proposalRepository.delete({ id, company_id: companyId });
    if (result.affected === 0) {
      throw new NotFoundException('Proposal not found');
    }
  }
}
