import { Injectable, ExecutionContext } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { Observable } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { of } from 'rxjs';

@Injectable()
export class OptionalJwtAuthGuard extends AuthGuard('jwt') {
  canActivate(context: ExecutionContext): boolean | Promise<boolean> | Observable<boolean> {
    // Try to authenticate, but don't throw error if token is missing or invalid
    const result = super.canActivate(context);
    
    if (result instanceof Promise) {
      return result.catch(() => true);
    }
    
    if (result instanceof Observable) {
      return result.pipe(
        map(() => true),
        catchError(() => of(true))
      );
    }
    
    return result || true;
  }

  handleRequest(err: any, user: any, info: any) {
    // Return user if authenticated, otherwise return null (don't throw error)
    if (err || !user) {
      return null;
    }
    return user;
  }
}
