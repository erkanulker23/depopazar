import { IsOptional, IsString, IsBoolean, IsUrl } from 'class-validator';
import { ApiPropertyOptional } from '@nestjs/swagger';

export class UpdateSmsSettingsDto {
  @ApiPropertyOptional({ description: 'NetGSM API username' })
  @IsOptional()
  @IsString()
  username?: string;

  @ApiPropertyOptional({ description: 'NetGSM API password' })
  @IsOptional()
  @IsString()
  password?: string;

  @ApiPropertyOptional({ description: 'SMS sender ID (header)' })
  @IsOptional()
  @IsString()
  sender_id?: string;

  @ApiPropertyOptional({ description: 'NetGSM API URL', default: 'https://api.netgsm.com.tr' })
  @IsOptional()
  @IsString()
  @IsUrl({}, { message: 'API URL must be a valid URL' })
  api_url?: string;

  @ApiPropertyOptional({ description: 'Is SMS settings active' })
  @IsOptional()
  @IsBoolean()
  is_active?: boolean;

  @ApiPropertyOptional({ description: 'Test mode' })
  @IsOptional()
  @IsBoolean()
  test_mode?: boolean;
}
