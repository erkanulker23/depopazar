import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Company } from './entities/company.entity';
import { UserRole } from '../../common/enums/user-role.enum';

@Injectable()
export class CompaniesService {
  constructor(
    @InjectRepository(Company)
    private companiesRepository: Repository<Company>,
  ) {}

  /**
   * Güvenli company ID: sadece JWT user bilgisinden. Client'tan alınmaz.
   * Super_admin company_id yoksa ilk şirketi döner; diğer roller kendi company_id.
   */
  async getCompanyIdForUser(user: { role?: string; company_id?: string | null }): Promise<string | null> {
    if (user?.company_id) return user.company_id;
    const role = user?.role;
    if (role === UserRole.SUPER_ADMIN || role === 'super_admin') {
      const companies = await this.companiesRepository.find({ take: 1 });
      return companies.length > 0 ? companies[0].id : null;
    }
    return null;
  }

  async create(createCompanyDto: Partial<Company>): Promise<Company> {
    const company = this.companiesRepository.create(createCompanyDto);
    return this.companiesRepository.save(company);
  }

  async findAll(): Promise<Company[]> {
    return this.companiesRepository.find({
      relations: ['users', 'warehouses'],
    });
  }

  async findOne(id: string): Promise<Company> {
    const company = await this.companiesRepository.findOne({
      where: { id },
      relations: ['users', 'warehouses'],
    });

    if (!company) {
      throw new NotFoundException('Şirket bulunamadı');
    }

    return company;
  }

  async findBySlug(slug: string): Promise<Company> {
    const company = await this.companiesRepository.findOne({
      where: { slug },
    });

    if (!company) {
      throw new NotFoundException('Şirket bulunamadı');
    }

    return company;
  }

  async update(id: string, updateData: Partial<Company>): Promise<Company> {
    await this.companiesRepository.update(id, updateData);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.companiesRepository.delete(id);
  }

  async updateLogo(companyId: string, relativePath: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { logo_url: relativePath });
    return this.findOne(companyId);
  }

  async clearLogo(companyId: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { logo_url: null });
    return this.findOne(companyId);
  }

  async updateContractTemplate(companyId: string, relativePath: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { contract_template_url: relativePath });
    return this.findOne(companyId);
  }

  async clearContractTemplate(companyId: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { contract_template_url: null });
    return this.findOne(companyId);
  }

  async updateInsuranceTemplate(companyId: string, relativePath: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { insurance_template_url: relativePath });
    return this.findOne(companyId);
  }

  async clearInsuranceTemplate(companyId: string): Promise<Company> {
    await this.companiesRepository.update(companyId, { insurance_template_url: null });
    return this.findOne(companyId);
  }
}
