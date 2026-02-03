import { Injectable, UnauthorizedException } from '@nestjs/common';
import { PassportStrategy } from '@nestjs/passport';
import { ExtractJwt, Strategy } from 'passport-jwt';
import { ConfigService } from '@nestjs/config';
import { UsersService } from '../../users/users.service';

@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
  constructor(
    private configService: ConfigService,
    private usersService: UsersService,
  ) {
    const secret = configService.get('JWT_SECRET');
    if (!secret) {
      throw new Error(
        'JWT_SECRET .env içinde tanımlanmalıdır (proje kökü .env veya backend/.env).',
      );
    }
    super({
      jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
      ignoreExpiration: false,
      secretOrKey: secret,
    });
  }

  async validate(payload: any) {
    let user;
    try {
      user = await this.usersService.findOne(payload.sub);
    } catch {
      throw new UnauthorizedException('Kullanıcı bulunamadı veya token geçersiz');
    }
    if (!user || !user.is_active) {
      throw new UnauthorizedException('Kullanıcı aktif değil veya yetkisiz erişim');
    }
    return {
      id: user.id,
      email: user.email,
      role: user.role,
      company_id: user.company_id,
    };
  }
}
