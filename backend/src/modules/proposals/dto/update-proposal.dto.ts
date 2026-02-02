import { PartialType } from '@nestjs/swagger';
import { CreateProposalDto } from './create-proposal.dto';
import { IsOptional, IsString } from 'class-validator';
import { ApiPropertyOptional } from '@nestjs/swagger';

export class UpdateProposalDto extends PartialType(CreateProposalDto) {
  @ApiPropertyOptional({ description: 'Durum' })
  @IsOptional()
  @IsString()
  status?: string;
}
