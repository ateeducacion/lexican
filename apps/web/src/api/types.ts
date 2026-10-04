import type { ErrorCode, LexicanApi, MediaView } from '@lexican/core';

/** What the UI talks to: the shared operations plus a few adapter-specific helpers. */
export type WebApi = LexicanApi & {
  /** URL usable in <img>/<audio>/<video>: the API route in production, an object URL in the demo. */
  mediaSrc(media: Pick<MediaView, 'id' | 'url'>): Promise<string>;
  /** Demo only: wipe the browser database, media and session, then reload with fresh demo data. */
  resetDemo?: () => Promise<void>;
  /** Demo only: start a login with the public test CAS (navigates away). */
  startCasLogin?: () => Promise<void>;
  /** Demo only: end the local session and the test CAS SSO session (navigates away). */
  casLogout?: () => Promise<void>;
  /** Demo only: validate a CAS callback once; returns the in-app path to go to. */
  completeCasLogin?: (p: { ticket: string | null; state: string | null }) => Promise<string>;
};

export type ApiErrorCode = ErrorCode | 'network' | 'internal';

/** Uniform error for both adapters; the UI distinguishes codes, never shows stack traces (§64). */
export class ApiError extends Error {
  readonly code: ApiErrorCode;
  readonly details: Record<string, string[]>;

  constructor(code: ApiErrorCode, message: string, details: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.code = code;
    this.details = details;
  }
}

export const toApiError = (e: unknown): ApiError => {
  if (e instanceof ApiError) return e;
  const d = e as {
    name?: string;
    code?: ApiErrorCode;
    message?: string;
    details?: Record<string, string[]>;
  };
  if (d?.name === 'DomainError' && d.code)
    return new ApiError(d.code, d.message ?? '', d.details ?? {});
  console.error(e);
  return new ApiError('internal', 'Ha ocurrido un error inesperado. Inténtalo de nuevo.');
};
