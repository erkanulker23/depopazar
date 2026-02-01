import { Injectable, NestMiddleware, UnauthorizedException } from '@nestjs/common';
import { Request, Response, NextFunction } from 'express';
import { UserRole } from '../enums/user-role.enum';

@Injectable()
export class TenantMiddleware implements NestMiddleware {
  use(req: Request, res: Response, next: NextFunction) {
    const user = (req as any).user;

    // Super admin can access all companies
    if (user && user.role === UserRole.SUPER_ADMIN) {
      return next();
    }

    // For other users, ensure they can only access their company's data
    // This will be enforced at the service level
    next();
  }
}
