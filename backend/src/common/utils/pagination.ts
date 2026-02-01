/**
 * Güvenli sayfalama parametreleri. Query'den gelen değerler parse edilir,
 * sınırlar uygulanır; SQL injection riski yok (TypeORM take/skip sayı kullanır).
 */
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const MAX_LIMIT = 100;

export interface PaginationParams {
  page: number;
  limit: number;
  skip: number;
  take: number;
}

export interface PaginatedResult<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export function parsePagination(
  pageStr?: string,
  limitStr?: string,
): PaginationParams {
  const page = Math.max(1, Number.parseInt(pageStr ?? '', 10) || DEFAULT_PAGE);
  const rawLimit = Number.parseInt(limitStr ?? '', 10) || DEFAULT_LIMIT;
  const limit = Math.min(MAX_LIMIT, Math.max(1, rawLimit));
  const skip = (page - 1) * limit;
  return { page, limit, skip, take: limit };
}

export function toPaginatedResult<T>(
  data: T[],
  total: number,
  params: PaginationParams,
): PaginatedResult<T> {
  const totalPages = Math.max(1, Math.ceil(total / params.limit));
  return {
    data,
    total,
    page: params.page,
    limit: params.limit,
    totalPages,
  };
}
