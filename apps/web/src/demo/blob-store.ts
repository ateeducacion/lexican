import type { MediaStorage } from '@lexican/app';

/**
 * Demo media bytes: Blobs in an IndexedDB database of LexiCán's own (`lexican-demo-media`), outside PGlite; SQL keeps
 * the metadata. Each write commits its own transaction (durable before the media row is inserted).
 */
const STORE = 'blobs';

const promised = <T>(req: IDBRequest<T>) =>
  new Promise<T>((resolve, reject) => {
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });

export async function openBlobStore(name: string): Promise<MediaStorage & { close(): void }> {
  const open = indexedDB.open(name, 1);
  open.onupgradeneeded = () => open.result.createObjectStore(STORE);
  const db = await promised(open);
  const tx = <T>(mode: IDBTransactionMode, fn: (s: IDBObjectStore) => IDBRequest<T>) =>
    new Promise<T>((resolve, reject) => {
      const t = db.transaction(STORE, mode, { durability: 'strict' });
      const req = fn(t.objectStore(STORE));
      t.oncomplete = () => resolve(req.result);
      t.onerror = () => reject(t.error ?? req.error);
      t.onabort = () => reject(t.error ?? new DOMException('Transaction aborted', 'AbortError'));
    });
  // WebKit refuses Blobs in IndexedDB in ephemeral sessions (Safari private browsing): keep the bytes as an
  // ArrayBuffer there; reads always hand out a Blob.
  let blobsRefused = false;
  type Stored = Blob | { type: string; bytes: ArrayBuffer };
  return {
    async put(key, bytes, mime) {
      if (!blobsRefused)
        try {
          await tx('readwrite', (s) => s.put(new Blob([bytes as BlobPart], { type: mime }), key));
          return;
        } catch (e) {
          if (
            (e as DOMException)?.name !== 'UnknownError' &&
            (e as DOMException)?.name !== 'DataCloneError'
          )
            throw e;
          blobsRefused = true;
        }
      await tx('readwrite', (s) =>
        s.put({ type: mime, bytes: bytes.slice().buffer } satisfies Stored, key),
      );
    },
    async open(key) {
      const v = (await tx('readonly', (s) => s.get(key))) as Stored | undefined;
      if (!v) return null;
      return v instanceof Blob ? v : new Blob([v.bytes], { type: v.type });
    },
    delete: async (key) => void (await tx('readwrite', (s) => s.delete(key))),
    keys: async () => (await tx('readonly', (s) => s.getAllKeys())).map(String),
    close: () => db.close(),
  };
}

export function deleteDatabase(name: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.deleteDatabase(name);
    req.onsuccess = () => resolve();
    req.onerror = () => reject(req.error);
    // Another connection still open (it will close with its tab): the deletion completes when it does.
    req.onblocked = () => resolve();
  });
}
