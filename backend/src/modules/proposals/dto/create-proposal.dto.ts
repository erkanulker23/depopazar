import { IsNotEmpty, IsOptional, IsString, IsUUID, IsNumber, IsDateString, ValidateNested, IsArray, IsEnum } from 'class-validator';
import { Type } from 'class-transformer';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class CreateProposalItemDto {
  @ApiPropertyOptional({ description: 'Hizmet ID (varsa)' })
  @IsOptional()
  @IsUUID()
  service_id?: string;

  @ApiProperty({ description: 'Hizmet/Ürün adı' })
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiPropertyOptional({ description: 'Açıklama' })
  @IsOptional()
  @IsString()
  description?: string;

  @ApiProperty({ description: 'Miktar' })
  @IsNotEmpty()
  @IsNumber()
  quantity: number;

  @ApiProperty({ description: 'Birim Fiyat' })
  @IsNotEmpty()
  @IsNumber()
  unit_price: number;
}

export class CreateProposalDto {
  @ApiPropertyOptional({ description: 'Müşteri ID' })
  @IsOptional()
  @IsUUID()
  customer_id?: string;

  @ApiProperty({ description: 'Teklif Başlığı' })
  @IsNotEmpty()
  @IsString()
  title: string;

  @ApiPropertyOptional({ description: 'Geçerlilik Tarihi' })
  @IsOptional()
  @IsDateString()
  valid_until?: string;

  @ApiPropertyOptional({ description: 'Notlar' })
  @IsOptional()
  @IsString()
  notes?: string;

  @ApiPropertyOptional({ description: 'Taşıma şartları' })
  @IsOptional()
  @IsString()
  transport_terms?: string;

  @ApiProperty({ description: 'Para Birimi' })
  @IsOptional()
  @IsString()
  currency?: string;

  @ApiProperty({ type: [CreateProposalItemDto] })
  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => CreateProposalItemDto)
  items: CreateProposalItemDto[];
}
