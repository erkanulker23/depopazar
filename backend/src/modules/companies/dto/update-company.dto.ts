import { IsOptional, IsString, IsEmail, MaxLength } from 'class-validator';
import { ApiPropertyOptional } from '@nestjs/swagger';

export class UpdateCompanyDto {
  @ApiPropertyOptional({ description: 'Firma ünvanı' })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  name?: string;

  @ApiPropertyOptional({ description: 'E-posta adresi' })
  @IsOptional()
  @IsEmail()
  @MaxLength(255)
  email?: string;

  @ApiPropertyOptional({ description: 'Telefon numarası' })
  @IsOptional()
  @IsString()
  @MaxLength(20)
  phone?: string;

  @ApiPropertyOptional({ description: 'WhatsApp numarası' })
  @IsOptional()
  @IsString()
  @MaxLength(20)
  whatsapp_number?: string;

  @ApiPropertyOptional({ description: 'Adres' })
  @IsOptional()
  @IsString()
  address?: string;

  @ApiPropertyOptional({ description: 'Mersis numarası' })
  @IsOptional()
  @IsString()
  @MaxLength(50)
  mersis_number?: string;

  @ApiPropertyOptional({ description: 'Ticaret sicil numarası' })
  @IsOptional()
  @IsString()
  @MaxLength(50)
  trade_registry_number?: string;

  @ApiPropertyOptional({ description: 'Vergi dairesi' })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  tax_office?: string;

  @ApiPropertyOptional({ description: 'Proje adı (uygulama içinde görünen marka adı, örn. DepoPazar)' })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  project_name?: string;

  @ApiPropertyOptional({ description: 'Sözleşme PDF şablonu URL' })
  @IsOptional()
  @IsString()
  contract_template_url?: string;

  @ApiPropertyOptional({ description: 'Sigorta PDF şablonu URL' })
  @IsOptional()
  @IsString()
  insurance_template_url?: string;
}
