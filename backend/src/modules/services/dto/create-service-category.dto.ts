import { IsNotEmpty, IsOptional, IsString } from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class CreateServiceCategoryDto {
  @ApiProperty({ description: 'Kategori adı' })
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiPropertyOptional({ description: 'Açıklama' })
  @IsOptional()
  @IsString()
  description?: string;
}
