import { createServices, seedDemo, type Actor, type MediaStorage } from '@lexican/app';
import { mediaBlobs, type Db } from '@lexican/db';
import type { MediaView, OperationName } from '@lexican/core';
import { eq } from 'drizzle-orm';
import { ApiError, toApiError, type WebApi } from '../api/types.ts';
import { deleteDemoDb, openDemoDb } from './db.ts';
import { readDemoSession as readSession, writeDemoSession as writeSession } from './session.ts';

/** Demo uploads live in the browser database (bytea), capped to keep IndexedDB small. */
const blobStorage = (db: Db): MediaStorage => ({
  async put(storageKey, data) {
    await db.insert(mediaBlobs).values({ storageKey, data });
  },
  async get(storageKey) {
    const [row] = await db.select().from(mediaBlobs).where(eq(mediaBlobs.storageKey, storageKey));
    return row ? new Uint8Array(row.data) : null;
  },
});

/**
 * In-browser adapter: the very same application services as the API, on PGlite.
 * The demo login is not a security boundary (§16); it only selects which fictitious user acts.
 */
export async function createDemoClient(): Promise<WebApi> {
  const { client, db } = await openDemoDb();
  const deps = {
    db,
    clock: () => new Date(),
    media: blobStorage(db),
    mediaUrl: (id: string) => `demo-media:${id}`,
    maxUploadBytes: 2 * 1024 * 1024,
  };
  const base = import.meta.env.BASE_URL;
  const images = await Promise.all(
    ['guagua.png', 'gofio.png'].map(async (name) => ({
      name,
      bytes: new Uint8Array(await (await fetch(`${base}demo/${name}`)).arrayBuffer()),
    })),
  ).catch(() => []);
  await seedDemo(deps, images);
  const svc = createServices(deps);

  let sessionUser = readSession();
  // Once a reset starts the database is closing: park every call until the page reloads
  // instead of failing (a pending loader would otherwise hit a closed PGlite).
  let resetting = false;
  const parked = <T>(): Promise<T> => new Promise<T>(() => undefined);
  const inflight = new Set<Promise<unknown>>();
  /** Run a data call; calls started during a reset are parked, failures caused by the reset are swallowed. */
  const track = <T>(fn: () => Promise<T>): Promise<T> => {
    if (resetting) return parked();
    const p = fn().catch((e: unknown) => (resetting ? parked<T>() : Promise.reject(e)));
    inflight.add(p);
    void p.finally(() => inflight.delete(p)).catch(() => undefined);
    return p;
  };
  const actor = async (): Promise<Actor | null> =>
    sessionUser ? svc.auth.actorFor(sessionUser) : null;
  const objectUrls = new Map<string, string>();

  const api = {} as Record<string, unknown>;
  for (const name of Object.keys(svc.handlers) as OperationName[]) {
    api[name] = (input: unknown = {}) =>
      track(async () => {
        try {
          const result = await svc.call(name, await actor(), input);
          if (name === 'login') {
            sessionUser = (result as { id: string }).id;
            writeSession(sessionUser);
          }
          if (name === 'logout') {
            sessionUser = null;
            writeSession(null);
          }
          return structuredClone(result);
        } catch (e) {
          throw toApiError(e);
        }
      });
  }

  return {
    ...(api as unknown as WebApi),
    uploadMedia(file: Blob, name: string): Promise<MediaView> {
      return track(() => upload(file, name));
    },
    mediaSrc(m) {
      return track(() => resolveMedia(m));
    },
    async resetDemo() {
      resetting = true;
      writeSession(null);
      // Let calls already running finish before the database is closed.
      await Promise.allSettled([...inflight]);
      await deleteDemoDb(client);
      location.reload();
    },
  };

  async function upload(file: Blob, name: string): Promise<MediaView> {
    try {
      return await svc.media.uploadMedia(await actor(), {
        bytes: new Uint8Array(await file.arrayBuffer()),
        name,
      });
    } catch (e) {
      throw toApiError(e);
    }
  }

  async function resolveMedia(m: Pick<MediaView, 'id' | 'url'>): Promise<string> {
    const cached = objectUrls.get(m.id);
    if (cached) return cached;
    try {
      const { bytes, mime } = await svc.media.readMedia(await actor(), m.id);
      const url = URL.createObjectURL(new Blob([bytes as BlobPart], { type: mime }));
      objectUrls.set(m.id, url);
      return url;
    } catch (e) {
      throw e instanceof ApiError ? e : toApiError(e);
    }
  }
}
