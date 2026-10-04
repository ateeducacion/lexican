import { mkdtempSync, readFileSync, readdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { roleFromDirectory } from '@lexican/app';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { parseLogoutRequest, parseServiceResponse } from './cas.ts';
import { httpDirectory, parseCauceResponse } from './cauce.ts';
import { loadConfig } from './config.ts';
import { fsMediaStorage } from './media-storage.ts';

const fixture = (n: string) => readFileSync(new URL(`./__fixtures__/${n}`, import.meta.url), 'utf8');

afterEach(() => vi.unstubAllGlobals());

describe('CAS XML', () => {
  it('parses success and failure responses', async () => {
    expect(parseServiceResponse(fixture('cas-success.xml'))).toEqual({ ok: true, user: 'cficticia' });
    expect(parseServiceResponse(fixture('cas-failure.xml'))).toEqual({ ok: false, code: 'INVALID_TICKET' });
    expect(parseServiceResponse('<html>oops</html>')).toEqual({ ok: false, code: 'INVALID_RESPONSE' });
  });

  it('rejects DTDs and entity declarations', () => {
    const xxe = `<?xml version="1.0"?><!DOCTYPE r [<!ENTITY x SYSTEM "file:///etc/passwd">]><cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"><cas:authenticationSuccess><cas:user>&x;</cas:user></cas:authenticationSuccess></cas:serviceResponse>`;
    expect(() => parseServiceResponse(xxe)).toThrow(/DTD/);
    expect(() => parseLogoutRequest('<!ENTITY a "b"><LogoutRequest/>')).toThrow(/DTD/);
  });

  it('extracts the SessionIndex of a logout request', () => {
    const xml = `<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"><samlp:SessionIndex>ST-9-x</samlp:SessionIndex></samlp:LogoutRequest>`;
    expect(parseLogoutRequest(xml)).toBe('ST-9-x');
    expect(parseLogoutRequest('<samlp:LogoutRequest xmlns:samlp="x"/>')).toBeNull();
  });
});

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
  it('round-trips bytes in sharded dirs and refuses unsafe keys', async () => {
    const root = mkdtempSync(join(tmpdir(), 'lexican-fs-'));
    try {
      const s = fsMediaStorage(root);
      const key = '0a1b2c3d-0000-4000-8000-000000000000.png';
      await s.put(key, new Uint8Array([1, 2, 3]), 'image/png');
      expect(await s.get(key)).toEqual(new Uint8Array([1, 2, 3]));
      expect(readdirSync(join(root, '0a'))).toEqual([key]);
      expect(await s.get('ffffffff-0000-4000-8000-000000000000.png')).toBeNull();
      await expect(s.put('../../etc/passwd', new Uint8Array([1]), 'x')).rejects.toThrow(/Invalid/);
      await expect(s.get('../0a/x.png')).rejects.toThrow(/Invalid/);
    } finally {
      rmSync(root, { recursive: true, force: true });
    }
  });
});

describe('config', () => {
  const base = { DATABASE_URL: 'postgres://x', PUBLIC_URL: 'https://lexican.example.org/', MEDIA_DIR: '/data/media' };

  it('derives secure cookies and CAS service URL from PUBLIC_URL', () => {
    const c = loadConfig({ ...base, CAS_BASE_URL: 'https://cas.example.org/cas', CAUCE_URL: 'https://c.example.org/', CAUCE_TOKEN: 't' });
    expect(c.secureCookies).toBe(true);
    expect(c.cas?.serviceUrl).toBe('https://lexican.example.org/api/auth/cas/callback');
    expect(loadConfig({ ...base, PUBLIC_URL: 'http://localhost:3000' }).secureCookies).toBe(false);
  });

  it('fails closed on unsafe combinations without echoing secrets', () => {
    expect(() => loadConfig({ ...base, NODE_ENV: 'production', AUTH_DEV_LOGIN: 'true' })).toThrow(/AUTH_DEV_LOGIN/);
    expect(() => loadConfig({ ...base, CAS_BASE_URL: 'https://cas.example.org' })).toThrow(/CAUCE/);
    expect(() => loadConfig({ ...base, DATABASE_URL: '', CAUCE_TOKEN: 'secret-value' })).toThrow(/^(?!.*secret-value)/);
  });
});
