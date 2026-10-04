import { DomainError, type GlobalRole, type SchoolYearConfig } from '@lexican/core';
import { auditEvents, type Db } from '@lexican/db';

/** Who is calling. `null` means anonymous. */
export interface Actor {
  userId: string;
  globalRole: GlobalRole;
}

/** Byte storage for uploaded media: filesystem in production, `media_blobs` in the demo. */
export interface MediaStorage {
  put(key: string, bytes: Uint8Array, mime: string): Promise<void>;
  get(key: string): Promise<Uint8Array | null>;
}

export interface Deps {
  db: Db;
  clock: () => Date;
  media: MediaStorage;
  /** Public URL of a media asset for the current adapter (API route or blob URL resolver). */
  mediaUrl: (mediaId: string) => string;
  schoolYear?: SchoolYearConfig;
  /** Upload size limit per media kind, bytes. */
  maxUploadBytes?: number;
  /** Per-user upload quota over a rolling 24 h window, bytes (default 100 MB). */
  maxUploadBytesPerDay?: number;
}

export function requireUser(actor: Actor | null): Actor {
  if (!actor) throw new DomainError('unauthenticated', 'Tu sesión ha caducado. Vuelve a entrar.');
  return actor;
}

export function requireRole(actor: Actor | null, ...roles: GlobalRole[]): Actor {
  const a = requireUser(actor);
  if (!roles.includes(a.globalRole)) throw new DomainError('forbidden', 'No tienes permiso para esta acción.');
  return a;
}

export const notFound = (what = 'El elemento'): DomainError =>
  new DomainError('not_found', `${what} no existe o no tienes acceso.`);

export const iso = (d: Date | null | undefined): string | null => (d ? d.toISOString() : null);

/** Minimal audit trail (§53): no content, no secrets. */
export async function audit(
  db: Db,
  actor: Actor | null,
  action: string,
  entityType: string,
  entityId: string | null,
  metadata: Record<string, string | number | boolean | null> = {},
): Promise<void> {
  await db.insert(auditEvents).values({ actorId: actor?.userId ?? null, action, entityType, entityId, metadata });
}

/** Postgres unique-violation detection on both drivers. */
export function isUniqueViolation(e: unknown): boolean {
  const err = e as { code?: string; cause?: { code?: string } };
  return err?.code === '23505' || err?.cause?.code === '23505';
}
