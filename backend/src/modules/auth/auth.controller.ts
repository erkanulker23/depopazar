import { Controller, Post, Body, HttpCode, HttpStatus, UseGuards, BadRequestException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { AuthService } from './auth.service';
import { LoginDto } from './dto/login.dto';
import { RegisterDto } from './dto/register.dto';
import { AuthResponseDto } from './dto/auth-response.dto';
import { OptionalJwtAuthGuard } from './guards/optional-jwt-auth.guard';
import { CurrentUser } from './decorators/current-user.decorator';
import { UserRole } from '../../common/enums/user-role.enum';

@ApiTags('Authentication')
@Controller('auth')
export class AuthController {
  constructor(private readonly authService: AuthService) {}

  @Post('login')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'User login' })
  @ApiResponse({ status: 200, description: 'Login successful', type: AuthResponseDto })
  @ApiResponse({ status: 401, description: 'Invalid credentials' })
  async login(@Body() loginDto: LoginDto) {
    return this.authService.login(loginDto);
  }

  @Post('register')
  @ApiOperation({ summary: 'User registration' })
  @ApiResponse({ status: 201, description: 'User registered successfully' })
  @ApiBearerAuth()
  @UseGuards(OptionalJwtAuthGuard)
  async register(@Body() registerDto: RegisterDto, @CurrentUser() user?: any) {
    // Güvenlik: Client'tan gelen company_id'yi ignore et, sadece authenticated user'dan al
    const dto: any = { ...registerDto };
    const isStaffOrOwner =
      dto.role === UserRole.COMPANY_STAFF || dto.role === UserRole.COMPANY_OWNER;

    // Personel/Depo sahibi eklerken: giriş yapılmış olmalı ve şirket bilgisi olmalı
    if (isStaffOrOwner) {
      if (!user) {
        throw new BadRequestException(
          'Personel eklemek için oturum açmanız gerekiyor. Lütfen sayfayı yenileyip tekrar deneyin.',
        );
      }
      if (user.role === UserRole.SUPER_ADMIN) {
        // SUPER_ADMIN şirket kullanıcısı eklerken body'den company_id alabilir
        if (!registerDto.company_id) {
          throw new BadRequestException(
            'Personel veya şirket yöneticisi eklerken şirket seçmelisiniz. Lütfen şirket ID\'sini belirtin.',
          );
        }
        dto.company_id = registerDto.company_id;
      } else if (user.company_id) {
        dto.company_id = user.company_id;
      } else {
        throw new BadRequestException(
          'Şirket bilgisi bulunamadı. Bu işlem için şirket hesabıyla giriş yapmalısınız.',
        );
      }
    } else if (user?.company_id) {
      dto.company_id = user.company_id;
    }

    return this.authService.register(dto as RegisterDto);
  }

  @Post('refresh')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Refresh access token' })
  @ApiResponse({ status: 200, description: 'Token refreshed successfully' })
  async refresh(@Body('refresh_token') refreshToken: string) {
    return this.authService.refreshToken(refreshToken);
  }
}
