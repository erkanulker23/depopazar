import { IsOptional, IsString, IsBoolean } from 'class-validator';
import { ApiPropertyOptional } from '@nestjs/swagger';

export class UpdatePaytrSettingsDto {
  @ApiPropertyOptional({ description: 'PayTR Merchant ID' })
  @IsOptional()
  @IsString()
  merchant_id?: string;

  @ApiPropertyOptional({ description: 'PayTR Merchant Key' })
  @IsOptional()
  @IsString()
  merchant_key?: string;

  @ApiPropertyOptional({ description: 'PayTR Merchant Salt' })
  @IsOptional()
  @IsString()
  merchant_salt?: string;

  @ApiPropertyOptional({ description: 'Is PayTR settings active' })
  @IsOptional()
  @IsBoolean()
  is_active?: boolean;

  @ApiPropertyOptional({ description: 'Test mode' })
  @IsOptional()
  @IsBoolean()
  test_mode?: boolean;
}
