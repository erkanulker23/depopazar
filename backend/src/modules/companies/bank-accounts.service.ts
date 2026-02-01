import { Injectable, NotFoundException, ForbiddenException, BadRequestException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BankAccount } from './entities/bank-account.entity';
import { CompaniesService } from './companies.service';

@Injectable()
export class BankAccountsService {
  constructor(
    @InjectRepository(BankAccount)
    private bankAccountsRepository: Repository<BankAccount>,
    private readonly companiesService: CompaniesService,
  ) {}

  async create(createBankAccountDto: Partial<BankAccount>, companyId: string): Promise<BankAccount> {
    const bankAccount = this.bankAccountsRepository.create({
      ...createBankAccountDto,
      company_id: companyId,
    });
    return this.bankAccountsRepository.save(bankAccount);
  }

  async findAll(companyId: string): Promise<BankAccount[]> {
    return this.bankAccountsRepository.find({
      where: { company_id: companyId },
      order: { created_at: 'DESC' },
    });
  }

  async findActive(companyId: string): Promise<BankAccount[]> {
    return this.bankAccountsRepository.find({
      where: { company_id: companyId, is_active: true },
      order: { created_at: 'DESC' },
    });
  }

  async findOne(id: string, companyId: string): Promise<BankAccount> {
    const bankAccount = await this.bankAccountsRepository.findOne({
      where: { id, company_id: companyId },
    });

    if (!bankAccount) {
      throw new NotFoundException('Banka hesabı bulunamadı');
    }

    return bankAccount;
  }

  async update(id: string, updateBankAccountDto: Partial<BankAccount>, companyId: string): Promise<BankAccount> {
    await this.findOne(id, companyId); // Verify ownership
    await this.bankAccountsRepository.update(id, updateBankAccountDto);
    return this.findOne(id, companyId);
  }

  async remove(id: string, companyId: string): Promise<void> {
    await this.findOne(id, companyId); // Verify ownership
    await this.bankAccountsRepository.softDelete(id);
  }
}
