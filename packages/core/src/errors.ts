export type ErrorCode =
  | 'validation'
  | 'unauthenticated'
  | 'forbidden'
  | 'not_found'
  | 'conflict'
  | 'unavailable';

/** Error raised by application services; adapters map `code` to HTTP status or UI message. */
export class DomainError extends Error {
  readonly code: ErrorCode;
  readonly details?: Record<string, string[]>;

  constructor(code: ErrorCode, message: string, details?: Record<string, string[]>) {
    super(message);
    this.name = 'DomainError';
    this.code = code;
    if (details) this.details = details;
  }
}

export const HTTP_STATUS: Record<ErrorCode, number> = {
  validation: 400,
  unauthenticated: 401,
  forbidden: 403,
  not_found: 404,
  conflict: 409,
  unavailable: 503,
};

export const isDomainError = (e: unknown): e is DomainError =>
  e instanceof Error && e.name === 'DomainError' && 'code' in e;
