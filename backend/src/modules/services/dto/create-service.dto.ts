import { IsNotEmpty, IsNumber, IsOptional, IsString, IsUUID, Min } from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class CreateServiceDto {
  @ApiProperty({ description: 'Kategori ID' })
  @IsNotEmpty()
  @IsUUID()
  category_id: string;

  @ApiProperty({ description: 'Hizmet adı' })
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiPropertyOptional({ description: 'Açıklama' })
  @IsOptional()
  @IsString()
  description?: string;

  @ApiProperty({ description: 'Birim fiyat' })
  @IsNotEmpty()
  @IsNumber()
  @Min(0)
  unit_price: number;

  @ApiPropertyOptional({ description: 'Birim (adet, saat, vb.)' })
  @IsOptional()
  @IsString()
  unit?: string;
}
