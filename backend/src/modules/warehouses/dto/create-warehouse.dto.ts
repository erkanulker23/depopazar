import { IsString, IsNotEmpty, IsOptional, IsInt, Min, IsBoolean, IsUUID } from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class CreateWarehouseDto {
  @ApiProperty({ example: 'Ana Depo' })
  @IsString()
  @IsNotEmpty()
  name: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  company_id?: string;

  @ApiProperty({ example: 'İstanbul, Şişli, Depo Sokak No:1' })
  @IsString()
  @IsNotEmpty()
  address: string;

  @ApiProperty({ example: 'İstanbul' })
  @IsString()
  @IsNotEmpty()
  city: string;

  @ApiProperty({ example: 'Şişli' })
  @IsString()
  @IsNotEmpty()
  district: string;

  @ApiProperty({ example: 3, required: false })
  @IsOptional()
  @IsInt()
  @Min(1)
  total_floors?: number;

  @ApiPropertyOptional({ example: 'Depo açıklaması' })
  @IsOptional()
  @IsString()
  description?: string;

  @ApiPropertyOptional({ example: true, default: true })
  @IsOptional()
  @IsBoolean()
  is_active?: boolean;
}
