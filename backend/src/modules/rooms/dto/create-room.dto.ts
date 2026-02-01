import { IsString, IsNotEmpty, IsOptional, IsNumber, Min, IsEnum, IsUUID } from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { RoomStatus } from '../../../common/enums/room-status.enum';

export class CreateRoomDto {
  @ApiProperty({ example: 'A-101' })
  @IsString()
  @IsNotEmpty()
  room_number: string;

  @ApiProperty()
  @IsUUID()
  @IsNotEmpty()
  warehouse_id: string;

  @ApiProperty({ example: 25.5 })
  @IsNumber()
  @Min(0)
  area_m2: number;

  @ApiProperty({ example: 500.0 })
  @IsNumber()
  @Min(0)
  monthly_price: number;

  @ApiPropertyOptional({ enum: RoomStatus, default: RoomStatus.EMPTY })
  @IsOptional()
  @IsEnum(RoomStatus)
  status?: RoomStatus;

  @ApiPropertyOptional({ example: '1' })
  @IsOptional()
  @IsString()
  floor?: string;

  @ApiPropertyOptional({ example: 'A' })
  @IsOptional()
  @IsString()
  block?: string;

  @ApiPropertyOptional({ example: 'Kuzey' })
  @IsOptional()
  @IsString()
  corridor?: string;

  @ApiPropertyOptional({ example: 'Oda açıklaması' })
  @IsOptional()
  @IsString()
  description?: string;

  @ApiPropertyOptional({ example: 'Oda notları' })
  @IsOptional()
  @IsString()
  notes?: string;
}
