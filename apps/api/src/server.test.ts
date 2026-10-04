import { existsSync, mkdtempSync, rmSync } from 'node:fs';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { emptyDatabase } from './testing/pg.ts';

/** server.ts is the production entry point: importing it starts the API exactly as `node dist/server.js` does. */
const serverUrl = process.env.TEST_DATABASE_URL;

const freePort = () =>
  new Promise<number>((resolve) => {
    const s = createServer().listen(0, '127.0.0.1', () => {
      const { port } = s.address() as { port: number };
      s.close(() => resolve(port));
    });
  });

describe.skipIf(!serverUrl)('server entry point', () => {
  let db: Awaited<ReturnType<typeof emptyDatabase>>;
  let dir: string;

  beforeAll(async () => {
    db = await emptyDatabase(serverUrl!);
    dir = mkdtempSync(join(tmpdir(), 'lexican-server-'));
  });
  afterAll(async () => {
    await db?.drop();
    rmSync(dir, { recursive: true, force: true });
  });
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.restoreAllMocks();
  });

  /** Start server.ts, capturing its signal handlers and process.exit instead of touching the test runner. */
  async function start(env: Record<string, string>) {
    const port = await freePort();
    const base = {
      DATABASE_URL: db.url,
      PUBLIC_URL: 'http://localhost:3999',
      HOST: '127.0.0.1',
      PORT: String(port),
      LOG_LEVEL: 'silent',
    };
    for (const [k, v] of Object.entries({ ...base, ...env })) vi.stubEnv(k, v);
    const handlers = new Map<string, () => Promise<void>>();
    const once = process.once.bind(process);
    vi.spyOn(process, 'once').mockImplementation(((event: string, fn: () => Promise<void>) => {
      if (event === 'SIGTERM' || event === 'SIGINT') handlers.set(event, fn);
      else once(event, fn);
      return process;
    }) as typeof process.once);
    const exit = vi
      .spyOn(process, 'exit')
      .mockImplementation((() => undefined) as typeof process.exit);
    vi.resetModules();
    await import('./server.ts');
    return { url: `http://127.0.0.1:${port}`, handlers, exit };
  }

  it('migrates on start when asked, creates the media dir, serves, and shuts down cleanly on SIGTERM', async () => {
    const mediaDir = join(dir, 'media', 'nested');
    const s = await start({ MEDIA_DIR: mediaDir, MIGRATE_ON_START: 'true' });
    expect(existsSync(mediaDir)).toBe(true);
    expect(
      (await db.query('select count(*)::int as n from drizzle.__drizzle_migrations')).rows[0].n,
    ).toBeGreaterThan(0);
    const health = await fetch(`${s.url}/api/health`);
    expect(health.status).toBe(200);
    expect(await health.json()).toEqual({ ok: true });
    expect(await (await fetch(`${s.url}/api/auth/providers`)).json()).toEqual({
      cas: false,
      password: false,
    });
    expect([...s.handlers.keys()].sort()).toEqual(['SIGINT', 'SIGTERM']);

    await s.handlers.get('SIGTERM')!();
    expect(s.exit).toHaveBeenCalledExactlyOnceWith(0);
    await expect(fetch(`${s.url}/api/health`)).rejects.toThrow();
    // A second signal while closing is ignored.
    await s.handlers.get('SIGINT')!();
    expect(s.exit).toHaveBeenCalledTimes(1);
  });

  it('starts without migrating and with the development login', async () => {
    const s = await start({ MEDIA_DIR: join(dir, 'm2'), AUTH_DEV_LOGIN: 'true' });
    expect(await (await fetch(`${s.url}/api/auth/providers`)).json()).toEqual({
      cas: false,
      password: true,
    });
    await s.handlers.get('SIGINT')!();
    expect(s.exit).toHaveBeenCalledWith(0);
  });
});
