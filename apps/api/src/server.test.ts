import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync, rmSync } from 'node:fs';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { emptyDatabase } from './testing/pg.ts';

/** server.ts is the production entry point: started here with Bun, exactly as the image runs it. */
const serverUrl = process.env.TEST_DATABASE_URL;
const entry = new URL('./server.ts', import.meta.url).pathname;

const freePort = () =>
  new Promise<number>((resolve) => {
    const s = createServer().listen(0, '127.0.0.1', () => {
      const { port } = s.address() as { port: number };
      s.close(() => resolve(port));
    });
  });

describe.skipIf(!serverUrl)('server entry point (Bun)', () => {
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

  async function start(env: Record<string, string>) {
    const port = await freePort();
    const child = spawn('bun', ['--no-env-file', entry], {
      env: {
        PATH: process.env.PATH,
        APP_ENV: 'test',
        DATABASE_URL: db.url,
        PUBLIC_URL: 'http://localhost:3999',
        HOST: '127.0.0.1',
        PORT: String(port),
        LOG_LEVEL: 'silent',
        ...env,
      },
      stdio: ['ignore', 'ignore', 'pipe'],
    });
    let stderr = '';
    child.stderr.on('data', (d: Buffer) => (stderr += d.toString()));
    const exited = new Promise<number | null>((resolve) =>
      child.once('exit', (code) => resolve(code)),
    );
    const url = `http://127.0.0.1:${port}`;
    for (let i = 0; i < 100; i++) {
      if (child.exitCode !== null) throw new Error(`server exited: ${stderr}`);
      if (
        await fetch(`${url}/api/health`).then(
          (r) => r.ok,
          () => false,
        )
      )
        break;
      await new Promise((r) => setTimeout(r, 100));
    }
    return { url, child, exited, stderr: () => stderr };
  }

  it('migrates on start when asked, creates the media dir, serves, and shuts down cleanly on SIGTERM', async () => {
    const mediaDir = join(dir, 'media', 'nested');
    const s = await start({ MEDIA_DIR: mediaDir, MIGRATE_ON_START: 'true' });
    expect(existsSync(mediaDir)).toBe(true);
    expect(
      (await db.query('select count(*)::int as n from drizzle.__drizzle_migrations')).rows[0]!.n,
    ).toBeGreaterThan(0);
    const health = await fetch(`${s.url}/api/health`);
    expect(health.status).toBe(200);
    expect(await health.json()).toEqual({ ok: true });
    expect(health.headers.get('content-security-policy')).toContain("frame-ancestors 'none'");
    expect(await (await fetch(`${s.url}/api/auth/providers`)).json()).toEqual({
      cas: null,
      password: false,
    });
    s.child.kill('SIGTERM');
    expect(await s.exited).toBe(0);
    await expect(fetch(`${s.url}/api/health`)).rejects.toThrow();
  });

  it('starts without migrating and with the development login', async () => {
    const s = await start({ MEDIA_DIR: join(dir, 'm2'), AUTH_DEV_LOGIN: 'true' });
    expect(await (await fetch(`${s.url}/api/auth/providers`)).json()).toEqual({
      cas: null,
      password: true,
    });
    s.child.kill('SIGINT');
    expect(await s.exited).toBe(0);
  });

  it('refuses to start with the default (production) profile and no institutional configuration', async () => {
    const child = spawn('bun', ['--no-env-file', entry], {
      env: {
        PATH: process.env.PATH,
        DATABASE_URL: db.url,
        PUBLIC_URL: 'https://lexican.example.test',
        MEDIA_DIR: join(dir, 'm3'),
      },
      stdio: ['ignore', 'ignore', 'pipe'],
    });
    let stderr = '';
    child.stderr.on('data', (d: Buffer) => (stderr += d.toString()));
    const code = await new Promise<number | null>((resolve) => child.once('exit', resolve));
    expect(code).not.toBe(0);
    expect(stderr).toContain('APP_ENV=production requires CAS_URL');
  });
});
