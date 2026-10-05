import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { memoryMediaStorage, seedDemo } from '@lexican/app';
import { openPglite, type TestDb } from '@lexican/db/testing';
import { createApi } from '@lexican/http';
import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApiClient } from '../api/client.ts';
import { ApiError } from '../api/types.ts';
import type { FromWorker, ToWorker } from './protocol.ts';
import { getDemoStatus } from './status.ts';
import { createWorkerTransport } from './transport.ts';
import { createMessageHandler, demoCarrier, type Fetcher } from './worker-core.ts';

/**
 * The demo transport end to end, in-process: page-side transport → structured clone (as postMessage does) → the
 * worker's message loop → the shared Hono API on PGlite. Only the Worker object is simulated.
 */
const ORIGIN = 'http://demo.test';
const tone = new Uint8Array(
  readFileSync(new URL('../../../../fixtures/media/tone.mp3', import.meta.url)),
);

class FakeWorker {
  onmessage: ((ev: { data: FromWorker }) => void) | null = null;
  onerror: ((ev: Event) => void) | null = null;
  onmessageerror: ((ev: Event) => void) | null = null;
  sent: ToWorker[] = [];
  constructor(private serve: (m: ToWorker, reply: (r: FromWorker) => void) => void) {}
  postMessage(m: ToWorker) {
    this.sent.push(m);
    const copy = structuredClone(m);
    setTimeout(() => this.serve(copy, (r) => this.emit(r)), 0);
  }
  emit(r: FromWorker) {
    this.onmessage?.({ data: structuredClone(r) });
  }
  crash() {
    this.onerror?.(new Event('error'));
  }
}

const storage = () => {
  const m = new Map<string, string>();
  return {
    getItem: (k: string) => m.get(k) ?? null,
    setItem: (k: string, v: string) => void m.set(k, v),
    removeItem: (k: string) => void m.delete(k),
  };
};

let t: TestDb;
let app: Fetcher;

beforeAll(async () => {
  t = await openPglite();
  const media = memoryMediaStorage();
  await seedDemo({ db: t.db, media, clock: () => new Date(), mediaUrl: (id) => `/media/${id}` });
  app = createApi({
    db: t.db,
    media,
    carrier: demoCarrier,
    directory: null,
    clientIp: () => 'local',
    log: { info: () => undefined, warn: () => undefined, error: () => undefined },
    config: {
      allowedOrigin: null,
      appUrl: '',
      sessionTtlMs: 3600_000,
      passwordLogin: true,
      loginRateLimit: 1000,
      maxUploadBytes: 1024 * 1024,
      mediaQuotaBytesPerDay: 10 * 1024 * 1024,
      cas: null,
    },
  }).app as unknown as Fetcher;
});
afterAll(() => t?.close());
beforeEach(() => {
  vi.stubGlobal('location', { origin: ORIGIN, assign: vi.fn(), reload: vi.fn() });
  vi.stubGlobal('localStorage', storage());
});
afterEach(() => vi.unstubAllGlobals());

/** A worker running the real message loop and API; `ready` is announced like the real one. */
function realWorker() {
  const w: FakeWorker = new FakeWorker((m) => handler.onMessage(m));
  const handler = createMessageHandler(Promise.resolve(app), (r) => w.emit(r), ORIGIN);
  setTimeout(() => w.emit({ type: 'ready' }), 0);
  return w;
}

const clientFor = (w: FakeWorker, opts = {}) => {
  const tr = createWorkerTransport(w as unknown as Worker, opts);
  const blobs: Blob[] = [];
  return {
    tr,
    blobs,
    api: createApiClient(tr.fetch, {
      media: 'blob',
      objectUrl: (id, b) => {
        blobs.push(b);
        return `blob:${id}`;
      },
    }),
  };
};

describe('worker transport', () => {
  it('carries JSON, errors, the demo session and concurrent requests through the real API', async () => {
    const { api } = clientFor(realWorker());
    expect(await api.me({})).toEqual({ user: null });
    await expect(api.myClassrooms({})).rejects.toMatchObject({ code: 'unauthenticated' });
    await expect(
      api.login({ email: 'alumno1@ejemplo.com', password: 'mal' }),
    ).rejects.toBeInstanceOf(ApiError);
    const user = await api.login({ email: 'alumno1@ejemplo.com', password: 'alumno1' });
    // The session id is kept by the page (the worker cannot set cookies), never the user id.
    const sid = localStorage.getItem('lexican-demo-sid');
    expect(sid).toMatch(/^[\w-]{43}$/);
    expect(sid).not.toContain(user.id);
    const results = await Promise.all(Array.from({ length: 20 }, () => api.me({})));
    expect(results.every((r) => r.user?.id === user.id)).toBe(true);
    await expect(
      api.getEntry({ entryId: '00000000-0000-4000-8000-000000000000' }),
    ).rejects.toMatchObject({
      code: 'not_found',
    });
    await api.logout({});
    expect(localStorage.getItem('lexican-demo-sid')).toBeNull();
    expect(await api.me({})).toEqual({ user: null });
  });

  it('disables CAS providers and login routes in the demo', async () => {
    const { tr } = clientFor(realWorker());
    const providers = await tr.fetch(new Request(`${ORIGIN}/api/auth/providers`));
    expect(await providers.json()).toEqual({ cas: null, password: true });
    for (const path of ['login', 'callback']) {
      const res = await tr.fetch(new Request(`${ORIGIN}/api/auth/cas/${path}`));
      expect(res.status).toBe(404);
      expect(res.headers.has('location')).toBe(false);
    }
  });

  it('uploads multipart binaries (name, type, boundary) and returns media as Blobs, not copies in JSON', async () => {
    const { api, blobs } = clientFor(realWorker());
    await api.login({ email: 'alumno1@ejemplo.com', password: 'alumno1' });
    const m = await api.uploadMedia(new Blob([tone]), 'tone.mp3', 'audio');
    expect(m).toMatchObject({ kind: 'audio', mime: 'audio/mpeg', originalName: 'tone.mp3' });
    expect(await api.mediaSrc(m)).toBe(`blob:${m.id}`);
    expect(blobs[0]!.type).toBe('audio/mpeg');
    expect(new Uint8Array(await blobs[0]!.arrayBuffer())).toEqual(tone);
    await expect(api.uploadMedia(new Blob(['<script>']), 'x.mp3', 'audio')).rejects.toMatchObject({
      code: 'validation',
    });
  });

  it('times out without retrying, drops the late answer, and cancels on abort', async () => {
    let held: ((r: FromWorker) => void)[] = [];
    const w = new FakeWorker((m, reply) => {
      if (m.type === 'request')
        held.push(() =>
          reply({
            type: 'response',
            id: m.id,
            status: 200,
            headers: [],
            body: null,
            update: { session: 'late' },
          }),
        );
    });
    setTimeout(() => w.emit({ type: 'ready' }), 0);
    const { tr } = clientFor(w, { timeoutMs: 30 });
    await expect(tr.fetch(new Request(`${ORIGIN}/api/me`))).rejects.toMatchObject({
      code: 'network',
    });
    expect(w.sent.filter((m) => m.type === 'request')).toHaveLength(1);
    expect(w.sent.at(-1)).toMatchObject({ type: 'abort' });
    for (const h of held) h({} as FromWorker);
    held = [];
    expect(localStorage.getItem('lexican-demo-sid')).toBeNull();

    const ctrl = new AbortController();
    const p = tr.fetch(new Request(`${ORIGIN}/api/me`, { signal: ctrl.signal }));
    await new Promise((r) => setTimeout(r, 5));
    ctrl.abort();
    await expect(p).rejects.toMatchObject({ name: 'AbortError' });
    expect(w.sent.at(-1)).toMatchObject({ type: 'abort' });
  });

  it('fails every pending and later request when the worker crashes, and reports start-up errors', async () => {
    const w = new FakeWorker(() => undefined);
    setTimeout(() => w.emit({ type: 'ready' }), 0);
    const { tr } = clientFor(w);
    await tr.ready;
    const pending = tr.fetch(new Request(`${ORIGIN}/api/me`));
    await new Promise((r) => setTimeout(r, 5));
    w.crash();
    await expect(pending).rejects.toMatchObject({ code: 'unavailable' });
    await expect(tr.fetch(new Request(`${ORIGIN}/api/me`))).rejects.toMatchObject({
      code: 'unavailable',
    });
    expect(getDemoStatus()).toMatchObject({ state: 'error', title: 'La demo se ha detenido' });

    const locked = new FakeWorker(() => undefined);
    setTimeout(() => locked.emit({ type: 'init-error', code: 'locked', detail: 'x' }), 0);
    vi.spyOn(console, 'error').mockImplementation(() => undefined);
    const second = clientFor(locked);
    await expect(second.tr.fetch(new Request(`${ORIGIN}/api/me`))).rejects.toMatchObject({
      code: 'unavailable',
    });
    expect(getDemoStatus()).toMatchObject({
      state: 'error',
      title: 'La demo ya está abierta en otra pestaña',
      canReset: false,
    });
    expect(locked.sent).toHaveLength(0);
  });

  it('bounds the start-up wait', async () => {
    const w = new FakeWorker(() => undefined);
    const { tr } = clientFor(w, { initTimeoutMs: 20 });
    await expect(tr.fetch(new Request(`${ORIGIN}/api/me`))).rejects.toMatchObject({
      code: 'unavailable',
    });
    expect(getDemoStatus()).toMatchObject({
      state: 'error',
      title: 'La demo tarda demasiado en arrancar',
    });
  });
});

describe('one path to the services', () => {
  it('the web app reaches application services only through the Hono API in the worker', () => {
    const root = new URL('../', import.meta.url).pathname;
    const files = (d: string): string[] =>
      readdirSync(d).flatMap((f) =>
        statSync(join(d, f)).isDirectory() ? files(join(d, f)) : [join(d, f)],
      );
    const offenders = files(root)
      .filter((f) => /\.tsx?$/.test(f) && !f.endsWith('.test.ts'))
      .filter((f) =>
        /createServices|^import (?!type )[^;]*from '@lexican\/app'/m.test(readFileSync(f, 'utf8')),
      )
      .map((f) => f.slice(root.length))
      .filter((f) => f !== 'demo/worker.ts');
    expect(offenders).toEqual([]);
    expect(readFileSync(join(root, 'demo/worker.ts'), 'utf8')).not.toContain('createServices');
  });
});
