import {
  IsString,
  IsUUID,
  IsNumber,
  IsBoolean,
  IsOptional,
  IsArray,
  IsDateString,
  Min,
  ValidateNested,
} from 'class-validator';
import { Type } from 'class-transformer';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class ContractMonthlyPriceItemDto {
  @ApiProperty({ example: '2024-01' })
  @IsString()
  month: string;

  @ApiProperty({ example: 500 })
  @IsNumber()
  @Min(0)
  price: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  notes?: string;
}

export class CreateContractDto {
  @ApiProperty()
  @IsUUID()
  customer_id: string;

  @ApiProperty()
  @IsUUID()
  room_id: string;

  @ApiProperty()
  @IsDateString()
  start_date: string;

  @ApiProperty()
  @IsDateString()
  end_date: string;

  @ApiProperty({ example: 500 })
  @IsNumber()
  @Min(0)
  monthly_price: number;

  @ApiPropertyOptional({ example: 1 })
  @IsOptional()
  @IsNumber()
  @Min(1)
  payment_frequency_months?: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  terms?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  notes?: string;

  @ApiPropertyOptional({ default: true })
  @IsOptional()
  @IsBoolean()
  is_active?: boolean;

  @ApiPropertyOptional()
  @IsOptional()
  @IsNumber()
  @Min(0)
  transportation_fee?: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  pickup_location?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  sold_by_user_id?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsNumber()
  @Min(0)
  discount?: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  driver_name?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  driver_phone?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  vehicle_plate?: string;

  @ApiPropertyOptional({ type: [ContractMonthlyPriceItemDto] })
  @IsOptional()
  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => ContractMonthlyPriceItemDto)
  monthly_prices?: ContractMonthlyPriceItemDto[];

  @ApiPropertyOptional({ type: [String], description: 'User IDs for contract staff' })
  @IsOptional()
  @IsArray()
  @IsUUID('4', { each: true })
  staff_ids?: string[];
}
