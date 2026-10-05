/// <reference lib="webworker" />
import { PGlite } from '@electric-sql/pglite';
import { demoSeedVersion, seedDemo } from '@lexican/app';
import { type Db, type Journal, migrateBundled, schema } from '@lexican/db';
import { createApi, safeError } from '@lexican/http';
import { drizzle } from 'drizzle-orm/pglite';
import journal from '../../../../packages/db/migrations/meta/_journal.json';
import { deleteDatabase, openBlobStore } from './blob-store.ts';
import {
  DATA_DIR,
  DEMO_LOCK,
  type FromWorker,
  type InitErrorCode,
  MEDIA_DB,
  type ToWorker,
} from './protocol.ts';
import { demoCarrier as carrier, createMessageHandler, type Fetcher } from './worker-core.ts';

/**
 * The demo "server": the shared Hono API (`@lexican/http`) with the same services, on PGlite (IndexedDB) and media
 * Blobs in IndexedDB, off the main thread. The page talks to it only through `app.fetch` requests (transport.ts).
 */
declare const self: DedicatedWorkerGlobalScope;

const sqlFiles = import.meta.glob<string>('../../../../packages/db/migrations/*.sql', {
  query: '?raw',
  import: 'default',
  eager: true,
});
const sqlByTag = Object.fromEntries(
  Object.entries(sqlFiles).map(([path, sql]) => [
    path
      .split('/')
      .pop()!
      .replace(/\.sql$/, ''),
    sql,
  ]),
);

const post = (m: FromWorker, transfer: Transferable[] = []) => self.postMessage(m, transfer);
class InitError extends Error {
  constructor(
    readonly code: InitErrorCode,
    detail: string,
  ) {
    super(detail);
  }
}

let holdsLock = false;

/** Hold the demo lock for the life of this worker: a second tab never opens the same PGlite storage. */
function acquireLock(): Promise<void> {
  if (!('locks' in navigator))
    return Promise.reject(new InitError('unsupported', 'Web Locks API not available'));
  return new Promise((resolve, reject) => {
    // Wait briefly: on reload, the previous page's worker may still be releasing the lock.
    navigator.locks
      .request(DEMO_LOCK, { signal: AbortSignal.timeout(4000) }, () => {
        holdsLock = true;
        resolve();
        return new Promise<void>(() => undefined);
      })
      .catch((e: unknown) =>
        reject(
          (e as DOMException)?.name === 'AbortError' || (e as DOMException)?.name === 'TimeoutError'
            ? new InitError('locked', 'demo open in another tab')
            : e,
        ),
      );
  });
}

async function start() {
  await acquireLock();
  let pg: PGlite;
  let media: Awaited<ReturnType<typeof openBlobStore>>;
  try {
    media = await openBlobStore(MEDIA_DB);
    // Default durability: every statement is flushed to IndexedDB before it returns (relaxedDurability lost writes
    // on reload in our tests).
    pg = await PGlite.create(`idb://${DATA_DIR}`);
  } catch (e) {
    throw new InitError('storage', String((e as Error)?.message ?? e));
  }
  const db = drizzle({ client: pg, schema }) as unknown as Db;
  await moveLegacyBlobs(pg, media);
  await migrateBundled(db, journal as Journal, sqlByTag);
  const deps = { db, media, clock: () => new Date(), mediaUrl: (id: string) => `/media/${id}` };
  // Versioned and idempotent: seeded once per browser (passwords hashed once), never on later visits.
  if ((await demoSeedVersion(db)) === null) await seedDemo(deps, await demoImages());
  else await seedDemo(deps);
  const api = createApi({
    db,
    media,
    carrier,
    directory: null,
    clientIp: () => 'local',
    log: {
      info: () => undefined,
      warn: (o, m) => console.warn(m, o),
      error: (o, m) => console.error(m, o),
    },
    config: {
      // Only this page can post to the worker; Origin and Cookie are forbidden headers in a constructed Request.
      allowedOrigin: null,
      appUrl: '',
      sessionTtlMs: 30 * 24 * 3600_000,
      passwordLogin: true,
      loginRateLimit: 1000,
      maxUploadBytes: 10 * 1024 * 1024,
      mediaQuotaBytesPerDay: 100 * 1024 * 1024,
      cas: null,
    },
  });
  await api.services.media.sweepOrphans();
  return { pg, media, app: api.app };
}

/**
 * Demo databases created before media moved out of SQL kept the bytes in a `media_blobs` table that migration 0001
 * drops: copy them to the Blob store first, so existing browser data is migrated, not silently lost.
 */
async function moveLegacyBlobs(pg: PGlite, media: Awaited<ReturnType<typeof openBlobStore>>) {
  const exists = await pg.query<{ t: string | null }>(
    `select to_regclass('public.media_blobs')::text as t`,
  );
  if (!exists.rows[0]?.t) return;
  const rows = await pg.query<{ storage_key: string; data: Uint8Array }>(
    `select b.storage_key, b.data from media_blobs b join media_assets a on a.storage_key = b.storage_key`,
  );
  const mimes = await pg.query<{ storage_key: string; mime: string }>(
    `select storage_key, mime from media_assets`,
  );
  const mimeOf = new Map(mimes.rows.map((r) => [r.storage_key, r.mime]));
  for (const r of rows.rows)
    await media.put(r.storage_key, r.data, mimeOf.get(r.storage_key) ?? 'application/octet-stream');
}

async function demoImages() {
  return Promise.all(
    ['guagua.png', 'gofio.png'].map(async (name) => ({
      name,
      bytes: new Uint8Array(
        await (await fetch(`${import.meta.env.BASE_URL}demo/${name}`)).arrayBuffer(),
      ),
    })),
  ).catch(() => []);
}

const ready = start();
ready.then(
  () => post({ type: 'ready' }),
  (e: unknown) =>
    post({
      type: 'init-error',
      code: e instanceof InitError ? e.code : 'failed',
      detail: safeError(e).message.slice(0, 300),
    }),
);

const handler = createMessageHandler(
  ready.then((r) => r.app as unknown as Fetcher),
  post,
  self.location.origin,
);
self.onmessage = (ev: MessageEvent<ToWorker>) => {
  const m = ev.data;
  if (!handler.onMessage(m) && m.type === 'reset') void reset(m.id);
};

/** Stop taking requests, let running ones finish, close and delete only LexiCán's own databases. */
async function reset(id: number) {
  // Without the lock another tab owns the databases: never delete them from here.
  if (!holdsLock) return post({ type: 'reset-done', id, ok: false });
  try {
    await handler.stop();
    const r = await ready.catch(() => null);
    await r?.pg.close();
    r?.media.close();
    await deleteDatabase(`/pglite/${DATA_DIR}`);
    await deleteDatabase(MEDIA_DB);
    post({ type: 'reset-done', id, ok: true });
  } catch (e) {
    console.error('demo reset failed', safeError(e));
    post({ type: 'reset-done', id, ok: false });
  }
}
