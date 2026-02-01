import { IsEmail, IsString, IsOptional, IsEnum, IsUUID } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';
import { UserRole } from '../../../common/enums/user-role.enum';
import { IsStrongPassword } from '../../../common/validators/password.validator';

export class RegisterDto {
  @ApiProperty({ example: 'john@example.com' })
  @IsEmail()
  email: string;

  @ApiProperty({ example: 'Password123', description: 'En az 8 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir' })
  @IsString()
  @IsStrongPassword()
  password: string;

  @ApiProperty({ example: 'John' })
  @IsString()
  first_name: string;

  @ApiProperty({ example: 'Doe' })
  @IsString()
  last_name: string;

  @ApiProperty({ example: '+905551234567', required: false })
  @IsOptional()
  @IsString()
  phone?: string;

  @ApiProperty({ enum: UserRole, required: false })
  @IsOptional()
  @IsEnum(UserRole)
  role?: UserRole;

  /** Sadece SUPER_ADMIN şirket kullanıcısı (personel/owner) eklerken gönderir; diğer rollerde sunucu user.company_id kullanır */
  @ApiProperty({ required: false })
  @IsOptional()
  @IsUUID()
  company_id?: string;
}
