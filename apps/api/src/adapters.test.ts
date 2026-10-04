import { mkdtempSync, readdirSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { roleFromDirectory } from '@lexican/app';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { httpDirectory, parseCauceResponse } from './cauce.ts';
import { loadConfig } from './config.ts';
import { fsMediaStorage } from './media-storage.ts';

const fixture = (n: string) =>
  readFileSync(new URL(`./__fixtures__/${n}`, import.meta.url), 'utf8');

afterEach(() => vi.unstubAllGlobals());

describe('CAUCE directory', () => {
  it('maps a teacher and drops national ids', async () => {
    const p = parseCauceResponse('cficticia', fixture('cauce-teacher.xml'));
    expect(p).toEqual({
      subject: 'cficticia',
      firstName: 'Carmen Ficticia',
      lastName: 'Pérez de Prueba',
      email: null,
      schools: [
        { code: '35999999', name: 'IES Inventado de Ejemplo', role: '1' },
        { code: '00000000', name: 'Sin centro educativo', role: '5' },
      ],
    });
    expect(JSON.stringify(p)).not.toMatch(/00000000T|Z0000000Z|Nif|CIAL/i);
    expect(roleFromDirectory(p)).toBe('teacher');
  });

  it('decodes HTML entities and maps a student', async () => {
    const p = parseCauceResponse('lalumna', fixture('cauce-student.xml'));
    expect(p.firstName).toBe('Lucía');
    expect(JSON.stringify(p)).not.toMatch(/XX0000000|Y1111111Y/);
    expect(roleFromDirectory(p)).toBe('student');
  });

  it('refuses users with MensajeError', async () => {
    expect(() => parseCauceResponse('x', '')).toThrow();
    const denied = fixture('cauce-denied.xml');
    expect(() => parseCauceResponse('x', denied)).toThrow(/no está autorizado/);
  });

  it('HTTP adapter sends the bearer token, encodes the subject and fails closed', async () => {
    const seen: string[] = [];
    const xml = fixture('cauce-teacher.xml');
    vi.stubGlobal('fetch', async (url: string, init: RequestInit) => {
      seen.push(`${url} ${new Headers(init.headers).get('authorization')}`);
      return new Response(xml);
    });
    const dir = httpDirectory({ url: 'https://cauce.example.test/u/', token: 'tok' });
    await dir.lookup('a/b?c');
    expect(seen).toEqual(['https://cauce.example.test/u/a%2Fb%3Fc Bearer tok']);

    vi.stubGlobal('fetch', async () => new Response('err', { status: 500 }));
    await expect(dir.lookup('x')).rejects.toMatchObject({ code: 'unavailable' });
    vi.stubGlobal('fetch', async () => new Response('<!DOCTYPE x><x/>'));
    await expect(dir.lookup('x')).rejects.toMatchObject({ code: 'forbidden' });
  });
});

describe('filesystem media storage', () => {
  it('round-trips bytes in sharded dirs as lazy Blobs, lists, deletes and refuses unsafe keys', async () => {
    const root = mkdtempSync(join(tmpdir(), 'lexican-fs-'));
    try {
      const s = fsMediaStorage(root);
      expect(await s.keys()).toEqual([]);
      const key = '0a1b2c3d-0000-4000-8000-000000000000.png';
      await s.put(key, new Uint8Array([1, 2, 3, 4]), 'image/png');
      const blob = (await s.open(key))!;
      expect(blob.size).toBe(4);
      expect(new Uint8Array(await blob.slice(1, 3).arrayBuffer())).toEqual(new Uint8Array([2, 3]));
      expect(readdirSync(join(root, '0a'))).toEqual([key]);
      expect(await s.keys()).toEqual([key]);
      expect(await s.open('ffffffff-0000-4000-8000-000000000000.png')).toBeNull();
      await expect(s.put('../../etc/passwd', new Uint8Array([1]), 'x')).rejects.toThrow(/Invalid/);
      await expect(s.open('../0a/x.png')).rejects.toThrow(/Invalid/);
      // Exclusive temp file: a failed write leaves nothing behind.
      await expect(s.put(key, undefined as unknown as Uint8Array, 'image/png')).rejects.toThrow();
      expect(readdirSync(join(root, '0a'))).toEqual([key]);
      await s.delete(key);
      await s.delete(key);
      expect(await s.keys()).toEqual([]);
    } finally {
      rmSync(root, { recursive: true, force: true });
    }
  });
});

describe('config', () => {
  const base = {
    DATABASE_URL: 'postgres://x',
    PUBLIC_URL: 'https://lexican.example.org/',
    MEDIA_DIR: '/data/media',
  };
  const institutional = {
    CAS_URL: 'https://cas.example.org/cas',
    CAUCE_URL: 'https://c.example.org/',
    CAUCE_TOKEN: 't',
    CAS_ALLOWED_SLO_HOSTS: '10.0.0.5',
  };

  it('defaults to production, which needs the institutional CAS + CAUCE and https', () => {
    expect(() => loadConfig(base)).toThrow(/requires CAS_URL/);
    const c = loadConfig({ ...base, ...institutional });
    expect(c).toMatchObject({
      appEnv: 'production',
      production: true,
      secureCookies: true,
      devLogin: false,
    });
    expect(c.cas).toEqual({
      url: 'https://cas.example.org/cas',
      loginPath: '/login',
      validatePath: '/p3/serviceValidate',
      logoutPath: '/logout',
      profiles: 'cauce',
      sloHosts: ['10.0.0.5'],
    });
    expect(() =>
      loadConfig({ ...base, ...institutional, PUBLIC_URL: 'http://lexican.example.org' }),
    ).toThrow(/https/);
    expect(() =>
      loadConfig({ ...base, ...institutional, CAS_URL: 'http://cas.example.org' }),
    ).toThrow(/https/);
  });

  it('production refuses the test CAS, fixture profiles, dev login and a missing directory', () => {
    const bad: Record<string, string>[] = [
      { CAS_URL: 'https://www.casserverpac4j.dev' },
      { CAS_PROFILES: 'test' },
      { AUTH_DEV_LOGIN: 'true' },
      { CAUCE_TOKEN: '' },
      { CAS_ALLOWED_SLO_HOSTS: '' },
    ];
    for (const b of bad)
      expect(() => loadConfig({ ...base, ...institutional, ...b }), JSON.stringify(b)).toThrow();
  });

  it('local uses the public test CAS with fixture profiles and no CAUCE by default', () => {
    const c = loadConfig({
      ...base,
      APP_ENV: 'local',
      PUBLIC_URL: 'http://localhost:3000',
      AUTH_DEV_LOGIN: 'true',
    });
    expect(c.cas).toMatchObject({
      url: 'https://www.casserverpac4j.dev',
      profiles: 'test',
      validatePath: '/p3/serviceValidate',
    });
    expect(c.secureCookies).toBe(false);
    expect(c.cauce).toBeNull();
    // Tests start with nothing enabled.
    expect(loadConfig({ ...base, APP_ENV: 'test' }).cas).toBeNull();
  });

  it('validates CAS paths and refuses the old CAS_BASE_URL', () => {
    const local = { ...base, APP_ENV: 'local' };
    expect(
      loadConfig({
        ...local,
        CAS_URL: 'https://idp.example.org/cas/',
        CAS_VALIDATE_PATH: '/serviceValidate',
      }).cas,
    ).toMatchObject({
      url: 'https://idp.example.org/cas',
      validatePath: '/serviceValidate',
    });
    for (const p of [
      'https://evil.example/x',
      '//evil.example/x',
      'login',
      '/a/../b',
      '/a?x=1',
      '/a#b',
    ])
      expect(() => loadConfig({ ...local, CAS_LOGIN_PATH: p }), p).toThrow(/CAS_LOGIN_PATH/);
    expect(() => loadConfig({ ...local, CAS_URL: 'https://cas.example.org/?x=1' })).toThrow(
      /query/,
    );
    expect(() => loadConfig({ ...local, CAS_BASE_URL: 'https://cas.example.org' })).toThrow(
      /renamed to CAS_URL/,
    );
    expect(() => loadConfig({ ...local, CAS_PROFILES: 'cauce' })).toThrow(/CAUCE_URL/);
  });

  it('fails closed without echoing secrets', () => {
    expect(() => loadConfig({ ...base, DATABASE_URL: '', CAUCE_TOKEN: 'secret-value' })).toThrow(
      /^(?!.*secret-value)/,
    );
  });

  it('accepts only a hop count or IP/CIDR list as TRUST_PROXY (SEC-007)', () => {
    const t = { ...base, APP_ENV: 'test' };
    expect(loadConfig(t).trustProxy).toBe(false);
    expect(loadConfig({ ...t, TRUST_PROXY: '1' }).trustProxy).toBe(1);
    expect(loadConfig({ ...t, TRUST_PROXY: '10.0.0.1, 172.16.0.0/12,::1' }).trustProxy).toEqual([
      '10.0.0.1',
      '172.16.0.0/12',
      '::1',
    ]);
    for (const bad of ['true', 'TRUE', 'loopback', '10.0.0.0/33', '999.1.1.1', '*'])
      expect(() => loadConfig({ ...t, TRUST_PROXY: bad }), bad).toThrow(/TRUST_PROXY/);
  });
});
