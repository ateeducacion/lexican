/**
 * Messages between the page and the demo Web Worker. Only structured-cloneable data: Request, Response, AbortSignal
 * and streams cannot be posted, so requests and responses travel as plain fields plus a Blob body (a handle, not a
 * copy of the bytes, in current engines; never base64 or number arrays).
 */

/** Demo session context. A Worker cannot set cookies, so the opaque session id and the CAS login state travel here. */
export interface DemoContext {
  session: string | null;
  casState: { value: string; exp: number } | null;
}
/** Changes to apply after a response; `undefined` means unchanged. */
export interface DemoContextUpdate {
  session?: string | null;
  casState?: { value: string; exp: number } | null;
}

export type ToWorker =
  | {
      type: 'request';
      id: number;
      method: string;
      url: string;
      headers: [string, string][];
      body: Blob | null;
      ctx: DemoContext;
    }
  | { type: 'abort'; id: number }
  | { type: 'reset'; id: number };

export type InitErrorCode = 'locked' | 'unsupported' | 'storage' | 'failed';

export type FromWorker =
  | { type: 'ready' }
  | { type: 'init-error'; code: InitErrorCode; detail: string }
  | {
      type: 'response';
      id: number;
      status: number;
      headers: [string, string][];
      body: Blob | null;
      update: DemoContextUpdate;
    }
  | { type: 'error'; id: number; detail: string }
  | { type: 'reset-done'; id: number; ok: boolean };

/** One browser tab owns the demo database at a time (Web Locks). */
export const DEMO_LOCK = 'lexican-demo-database';
/** Bump the suffix when the demo schema or seed changes incompatibly (browsers start from scratch). */
export const DATA_DIR = 'lexican-demo-v2';
/** IndexedDB database of the demo's media Blobs (outside PGlite). */
export const MEDIA_DB = 'lexican-demo-media';
