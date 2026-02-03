import { SetMetadata } from '@nestjs/common';

export const IS_PUBLIC_KEY = 'isPublic';

/** Route'u JWT doğrulamasından muaf tutar (örn. login, public brand). */
export const Public = () => SetMetadata(IS_PUBLIC_KEY, true);
