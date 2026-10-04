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
  const actor = async (): Promise<Actor | null> => (sessionUser ? svc.auth.actorFor(sessionUser) : null);
  const objectUrls = new Map<string, string>();

  const api = {} as Record<string, unknown>;
  for (const name of Object.keys(svc.handlers) as OperationName[]) {
    api[name] = async (input: unknown = {}) => {
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
    };
  }

  return {
    ...(api as unknown as WebApi),
    async uploadMedia(file: Blob, name: string): Promise<MediaView> {
      try {
        return await svc.media.uploadMedia(await actor(), { bytes: new Uint8Array(await file.arrayBuffer()), name });
      } catch (e) {
        throw toApiError(e);
      }
    },
    async mediaSrc(m) {
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
    },
    async resetDemo() {
      writeSession(null);
      await deleteDemoDb(client);
      location.reload();
    },
  };
}
