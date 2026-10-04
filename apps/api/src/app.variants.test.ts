import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createPasswordUser } from '@lexican/app';
import { authIdentities } from '@lexican/db';
import { openPglite, type TestDb } from '@lexican/db/testing';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { buildApp } from './app.ts';
import { testApp } from './testing/inject.ts';
import { fakeDirectory, type InstitutionalDirectory } from './cauce.ts';
import { loadConfig, type Config } from './config.ts';

/** The HTTP adapter under configurations other than the main app.test.ts one. */
const HTTPS = 'https://lexican.example.test';
const HTTP = 'http://localhost:3999';
const CAS = 'https://cas.example.test/cas';
const CAS_OK =
  '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"><cas:authenticationSuccess><cas:user>cficticia</cas:user></cas:authenticationSuccess></cas:serviceResponse>';

let t: TestDb;
let mediaDir: string;
const apps: { close(): Promise<unknown> }[] = [];

beforeAll(async () => {
  t = await openPglite();
  await createPasswordUser(t.db, {
    email: 'profe@ejemplo.com',
    password: 'clave',
    firstName: 'Profe',
    lastName: 'Prueba',
    globalRole: 'teacher',
  });
  mediaDir = mkdtempSync(join(tmpdir(), 'lexican-variants-'));
});
afterAll(async () => {
  for (const a of apps) await a.close();
  await t.close();
  rmSync(mediaDir, { recursive: true, force: true });
});
afterEach(() => vi.unstubAllGlobals());

const config = (env: Record<string, string> = {}) =>
  loadConfig({
    APP_ENV: 'test',
    DATABASE_URL: 'postgres://unused',
    PUBLIC_URL: HTTP,
    MEDIA_DIR: mediaDir,
    AUTH_DEV_LOGIN: 'true',
    ...env,
  });

async function app(
  cfg: Config,
  extra: { clock?: () => Date; directory?: InstitutionalDirectory; db?: TestDb['db'] } = {},
) {
  const a = testApp({ config: cfg, db: extra.db ?? t.db, ...extra }, buildApp);
  apps.push(a);
  return a;
}

const login = (a: Awaited<ReturnType<typeof app>>, origin: string, cookie?: string) =>
  a.inject({
    method: 'POST',
    url: '/api/auth/login',
    headers: { origin, ...(cookie ? { cookie } : {}) },
    payload: { email: 'profe@ejemplo.com', password: 'clave' },
  });

describe('HTTPS deployment', () => {
  it('uses __Host- cookies, Secure, HSTS and upgrade-insecure-requests', async () => {
    const a = await app(config({ PUBLIC_URL: HTTPS }));
    const r = await login(a, HTTPS);
    expect(r.statusCode).toBe(200);
    expect(r.cookies.find((c) => c.name === '__Host-sid')).toMatchObject({
      secure: true,
      httpOnly: true,
      path: '/',
    });
    expect(r.headers['strict-transport-security']).toMatch(/max-age=/);
    expect(r.headers['content-security-policy']).toContain('upgrade-insecure-requests');
    const cookie = `__Host-sid=${r.cookies.find((c) => c.name === '__Host-sid')!.value}`;
    expect(
      (await a.inject({ method: 'GET', url: '/api/me', headers: { cookie } })).json().user.email,
    ).toBe('profe@ejemplo.com');
    // The plain cookie name is ignored.
    expect(
      (
        await a.inject({
          method: 'GET',
          url: '/api/me',
          headers: { cookie: cookie.replace('__Host-', '') },
        })
      ).json(),
    ).toEqual({ user: null });
  });
});

describe('sessions', () => {
  it('slide while in use, expire when idle, and a new login replaces the old session', async () => {
    let now = new Date('2025-10-15T10:00:00Z');
    const a = await app(config({ SESSION_TTL_HOURS: '1' }), { clock: () => now });
    const sid = (r: Awaited<ReturnType<typeof login>>) =>
      `sid=${r.cookies.find((c) => c.name === 'sid')!.value}`;
    const me = async (cookie: string) =>
      (await a.inject({ method: 'GET', url: '/api/me', headers: { cookie } })).json().user;
    const cookie = sid(await login(a, HTTP));

    now = new Date('2025-10-15T10:50:00Z'); // used after 50 min: expiry moves to 11:50
    expect(await me(cookie)).not.toBeNull();
    now = new Date('2025-10-15T11:10:00Z'); // past the original 11:00 expiry
    expect(await me(cookie)).not.toBeNull();
    now = new Date('2025-10-15T11:12:00Z'); // within 5 min: no write, expiry stays 12:10
    expect(await me(cookie)).not.toBeNull();
    now = new Date('2025-10-15T12:11:00Z');
    expect(await me(cookie)).toBeNull();

    const first = sid(await login(a, HTTP));
    const second = sid(await login(a, HTTP, first));
    expect(await me(first)).toBeNull();
    expect(await me(second)).not.toBeNull();
    // Cookies are only looked up for API and media routes.
    expect(
      (await a.inject({ method: 'GET', url: '/', headers: { cookie: second } })).statusCode,
    ).toBe(404);
  });
});

describe('error mapping', () => {
  let a: Awaited<ReturnType<typeof app>>;
  let cookie: string;
  beforeAll(async () => {
    a = await app(config());
    cookie = `sid=${(await login(a, HTTP)).cookies.find((c) => c.name === 'sid')!.value}`;
  });
  const post = (payload: string, type = 'application/json') =>
    a.inject({
      method: 'POST',
      url: '/api/classrooms',
      headers: { origin: HTTP, cookie, 'content-type': type },
      payload,
    });

  it('non-object JSON bodies are validation errors', async () => {
    for (const body of ['[1,2]', '"texto"', '7'])
      expect((await post(body)).json()).toEqual({
        code: 'validation',
        message: 'Datos no válidos.',
      });
  });

  it('malformed JSON and unsupported content types are 4xx without internals', async () => {
    const bad = await post('{"title":');
    expect(bad.statusCode).toBe(400);
    expect(bad.json()).toEqual({ code: 'validation', message: 'Petición no válida.' });
    const text = await post('title=x', 'text/plain');
    expect(text.statusCode).toBe(415);
    expect(text.json()).toEqual({ code: 'validation', message: 'Petición no válida.' });
  });

  it('bodies over 1 MB are 413', async () => {
    const r = await post(JSON.stringify({ title: 'x'.repeat(1024 * 1024) }));
    expect(r.statusCode).toBe(413);
    expect(r.json()).toEqual({ code: 'validation', message: 'La petición es demasiado grande.' });
  });

  it('uploads need a "file" part and media ids must be UUIDs', async () => {
    const b = 'B';
    const payload = Buffer.from(
      `--${b}\r\nContent-Disposition: form-data; name="otro"; filename="a.png"\r\nContent-Type: image/png\r\n\r\nxx\r\n--${b}--\r\n`,
    );
    const r = await a.inject({
      method: 'POST',
      url: '/api/media',
      headers: { origin: HTTP, cookie, 'content-type': `multipart/form-data; boundary=${b}` },
      payload,
    });
    expect(r.statusCode).toBe(400);
    expect(r.json().message).toBe('Falta el archivo.');
    expect(
      (await a.inject({ method: 'GET', url: '/media/..%2F..%2Fetc%2Fpasswd', headers: { cookie } }))
        .statusCode,
    ).toBe(404);
  });

  it('database failures are a generic 500 and an unhealthy 503', async () => {
    const broken = await openPglite();
    const b = await app(config(), { db: broken.db });
    await broken.close();
    expect((await b.inject({ method: 'GET', url: '/api/health' })).statusCode).toBe(503);
    const r = await b.inject({ method: 'GET', url: '/api/vocabularies' });
    expect(r.statusCode).toBe(500);
    expect(r.json()).toEqual({ code: 'internal', message: 'Error interno' });
  });
});

describe('CAS variants', () => {
  it('without CAS every CAS route is 404 and logout goes home', async () => {
    const a = await app(config());
    for (const url of ['/api/auth/cas/login', '/api/auth/cas/callback?ticket=ST-1&state=x'])
      expect((await a.inject({ method: 'GET', url })).statusCode).toBe(404);
    const slo = await a.inject({
      method: 'POST',
      url: '/api/auth/cas/slo',
      headers: { 'content-type': 'application/x-www-form-urlencoded' },
      payload: 'logoutRequest=x',
    });
    expect(slo.statusCode).toBe(404);
    expect((await a.inject({ method: 'GET', url: '/api/auth/cas/logout' })).headers.location).toBe(
      `${HTTP}/`,
    );
  });

  /** CAS enabled; `directory` replaces CAUCE. */
  async function casApp(
    directory: InstitutionalDirectory | undefined,
    env: Record<string, string> = {},
  ) {
    const cfg = config({
      CAS_URL: CAS,
      CAS_PROFILES: 'cauce',
      CAUCE_URL: 'https://cauce.example.test/',
      CAUCE_TOKEN: 'fake-token',
      ...env,
    });
    const a = await app(directory ? cfg : { ...cfg, cauce: null }, directory ? { directory } : {});
    const callback = async (ticket: string | null) => {
      const login = await a.inject({ method: 'GET', url: '/api/auth/cas/login' });
      const state = login.cookies.find((c) => c.name === 'cas_state')!.value;
      const q = new URLSearchParams({ state, ...(ticket === null ? {} : { ticket }) });
      return a.inject({
        method: 'GET',
        url: `/api/auth/cas/callback?${q}`,
        headers: { cookie: `cas_state=${state}` },
      });
    };
    return { a, callback };
  }

  it('a missing or oversized ticket is refused before calling CAS', async () => {
    const fetch = vi.fn();
    vi.stubGlobal('fetch', fetch);
    const { callback } = await casApp(fakeDirectory({}));
    expect((await callback(null)).headers.location).toBe(`${HTTP}/entrar?error=cas`);
    expect((await callback(`ST-${'x'.repeat(512)}`)).headers.location).toBe(
      `${HTTP}/entrar?error=cas`,
    );
    expect(fetch).not.toHaveBeenCalled();
  });

  it('fails closed when no directory is configured or the directory breaks', async () => {
    vi.stubGlobal('fetch', async () => new Response(CAS_OK));
    const none = await casApp(undefined);
    expect((await none.callback('ST-1')).headers.location).toBe(`${HTTP}/entrar?error=unavailable`);
    const broken = await casApp({
      lookup: async () => {
        throw new Error('socket hang up');
      },
    });
    const r = await broken.callback('ST-2');
    expect(r.headers.location).toBe(`${HTTP}/entrar?error=unavailable`);
    expect(r.cookies.find((c) => c.name === 'sid')).toBeUndefined();
    const ok = await casApp(
      fakeDirectory({
        cficticia: { firstName: 'Carmen', lastName: 'Ficticia', email: null, schools: [] },
      }),
    );
    expect((await ok.callback('ST-3')).headers.location).toBe(`${HTTP}/`);
  });

  it('the test CAS profile signs fixture subjects in under their own issuer, without CAUCE', async () => {
    const db = await openPglite();
    try {
      const a = await app(config({ CAS_URL: CAS, CAS_PROFILES: 'test' }), {
        db: db.db,
        directory: undefined,
      });
      let user = 'alice';
      vi.stubGlobal('fetch', async (u: string) => {
        expect(u.startsWith(`${CAS}/p3/serviceValidate?`)).toBe(true);
        return new Response(CAS_OK.replace('cficticia', user));
      });
      expect((await a.inject({ method: 'GET', url: '/api/auth/providers' })).json()).toEqual({
        cas: 'test',
        password: true,
      });
      const go = async (ticket: string) => {
        const login = await a.inject({ method: 'GET', url: '/api/auth/cas/login' });
        const state = login.cookies.find((c) => c.name === 'cas_state')!.value;
        return a.inject({
          method: 'GET',
          url: `/api/auth/cas/callback?ticket=${ticket}&state=${state}`,
          headers: { cookie: `cas_state=${state}` },
        });
      };
      const r = await go('ST-a');
      expect(r.headers.location).toBe(`${HTTP}/`);
      const sid = r.cookies.find((c) => c.name === 'sid')!.value;
      const me = (
        await a.inject({ method: 'GET', url: '/api/me', headers: { cookie: `sid=${sid}` } })
      ).json().user;
      expect(me).toMatchObject({ globalRole: 'teacher', lastName: 'Tutoriales' });
      user = 'mallory';
      expect((await go('ST-m')).headers.location).toBe(`${HTTP}/entrar?error=forbidden`);
      const rows = await db.db.select().from(authIdentities);
      expect(rows.map((i) => `${i.provider}:${i.subject}`)).toEqual(['cas_test:alice']);
    } finally {
      await db.close();
    }
  });

  it('back-channel logout only from allowed proxies-resolved IPs, with a logoutRequest', async () => {
    const { a } = await casApp(fakeDirectory({}), {
      CAS_ALLOWED_SLO_HOSTS: '10.0.0.5',
      TRUST_PROXY: '1',
    });
    const slo = (ip: string, payload: string) =>
      a.inject({
        method: 'POST',
        url: '/api/auth/cas/slo',
        remoteAddress: '127.0.0.1',
        headers: { 'content-type': 'application/x-www-form-urlencoded', 'x-forwarded-for': ip },
        payload,
      });
    expect((await slo('10.0.0.9', 'logoutRequest=x')).statusCode).toBe(403);
    expect((await slo('10.0.0.5', 'otro=x')).statusCode).toBe(400);
    expect((await slo('10.0.0.5', 'logoutRequest=<a')).statusCode).toBe(400);
    const xml = encodeURIComponent(
      '<samlp:LogoutRequest xmlns:samlp="x"><samlp:SessionIndex>ST-1</samlp:SessionIndex></samlp:LogoutRequest>',
    );
    expect((await slo('10.0.0.5', `logoutRequest=${xml}`)).json()).toEqual({ ok: true });
  });
});

describe('SPA static files', () => {
  it('fingerprinted assets are immutable, other files revalidate, non-GET client routes are 404', async () => {
    const dist = mkdtempSync(join(tmpdir(), 'lexican-dist-'));
    mkdirSync(join(dist, 'assets'));
    writeFileSync(join(dist, 'index.html'), '<!doctype html><title>LexiCán</title>');
    writeFileSync(join(dist, 'assets', 'app-1a2b.js'), 'console.log(1)');
    writeFileSync(join(dist, 'favicon.svg'), '<svg/>');
    try {
      const a = await app(config({ WEB_DIST: dist }));
      expect(
        (await a.inject({ method: 'GET', url: '/assets/app-1a2b.js' })).headers['cache-control'],
      ).toBe('public, max-age=31536000, immutable');
      expect(
        (await a.inject({ method: 'GET', url: '/favicon.svg' })).headers['cache-control'],
      ).toBe('no-cache');
      const head = await a.inject({ method: 'HEAD', url: '/aulas' });
      expect(head.statusCode).toBe(200);
      expect(head.headers['cache-control']).toBe('no-cache');
      expect(
        (await a.inject({ method: 'DELETE', url: '/aulas', headers: { origin: HTTP } })).statusCode,
      ).toBe(404);
      expect((await a.inject({ method: 'GET', url: '/media/missing' })).json().code).toBe(
        'not_found',
      );
    } finally {
      rmSync(dist, { recursive: true, force: true });
    }
  });
});
