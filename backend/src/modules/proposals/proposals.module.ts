import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Proposal } from './entities/proposal.entity';
import { ProposalItem } from './entities/proposal-item.entity';
import { ProposalsService } from './proposals.service';
import { ProposalsController } from './proposals.controller';
import { CompaniesModule } from '../companies/companies.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Proposal, ProposalItem]),
    CompaniesModule,
  ],
  controllers: [ProposalsController],
  providers: [ProposalsService],
  exports: [ProposalsService],
})
export class ProposalsModule {}
