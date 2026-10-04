import { describe, expect, it, vi } from 'vitest';
import {
  casEndpoint,
  casLoginUrl,
  casLogoutUrl,
  CasUnavailable,
  parseLogoutRequest,
  parseServiceResponse,
  validateTicket,
  type CasServer,
} from './cas.ts';

const OK =
  '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"><cas:authenticationSuccess><cas:user>alice</cas:user></cas:authenticationSuccess></cas:serviceResponse>';
const FAIL =
  '<cas:serviceResponse xmlns:cas=\'http://www.yale.edu/tp/cas\'>\n    <cas:authenticationFailure code="INVALID_TICKET">Ticket &#39;ST-x&#39; not recognized</cas:authenticationFailure>\n</cas:serviceResponse>';
const server: CasServer = {
  url: 'https://cas.example.test/cas',
  loginPath: '/login',
  validatePath: '/p3/serviceValidate',
  logoutPath: '/logout',
};
const SERVICE = 'https://app.example.test/lexican/?cas=callback&state=abc';

describe('CAS URLs', () => {
  it('keep the deployment prefix, use one slash and encode the service once', () => {
    expect(casEndpoint({ ...server, url: 'https://cas.example.test/cas/' }, '/login').href).toBe(
      'https://cas.example.test/cas/login',
    );
    expect(casLoginUrl(server, SERVICE)).toBe(
      `https://cas.example.test/cas/login?service=${encodeURIComponent(SERVICE)}`,
    );
    expect(new URL(casLoginUrl(server, SERVICE)).searchParams.get('service')).toBe(SERVICE);
    expect(casLogoutUrl(server, 'https://app.example.test/')).toBe(
      'https://cas.example.test/cas/logout?service=https%3A%2F%2Fapp.example.test%2F',
    );
  });

  it('refuse paths that leave the CAS server and odd base URLs', () => {
    for (const p of [
      '//evil.example/x',
      'https://evil.example/',
      'login',
      '/a/../../x',
      '/a?b',
      '/a\\b',
    ])
      expect(() => casEndpoint(server, p), p).toThrow();
    expect(() =>
      casEndpoint({ ...server, url: 'https://cas.example.test/?x=1' }, '/login'),
    ).toThrow();
    expect(() => casEndpoint({ ...server, url: 'javascript:alert(1)' }, '/login')).toThrow();
  });
});

describe('CAS responses', () => {
  it('accept only an unambiguous success', () => {
    expect(parseServiceResponse(OK)).toEqual({ ok: true, user: 'alice' });
    expect(parseServiceResponse(FAIL)).toEqual({ ok: false, code: 'INVALID_TICKET' });
    const both = OK.replace(
      '</cas:serviceResponse>',
      '<cas:authenticationFailure code="X"/></cas:serviceResponse>',
    );
    expect(parseServiceResponse(both).ok).toBe(false);
    expect(parseServiceResponse(OK.replace('alice', '  ')).ok).toBe(false);
    expect(parseServiceResponse('<html>oops</html>')).toEqual({
      ok: false,
      code: 'INVALID_RESPONSE',
    });
    expect(parseServiceResponse('<cas:serviceResponse><cas:authenticationSuccess>').ok).toBe(false);
  });

  it('refuse DTDs and entity declarations', () => {
    const xxe = `<?xml version="1.0"?><!DOCTYPE r [<!ENTITY x SYSTEM "file:///etc/passwd">]>${OK.replace('alice', '&x;')}`;
    expect(parseServiceResponse(xxe)).toEqual({ ok: false, code: 'INVALID_RESPONSE' });
    expect(() => parseLogoutRequest('<!ENTITY a "b"><LogoutRequest/>')).toThrow(/DTD/);
  });

  it('extract the SessionIndex of a back-channel logout request (service tickets only)', () => {
    const xml = `<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"><samlp:SessionIndex>ST-9-x</samlp:SessionIndex></samlp:LogoutRequest>`;
    expect(parseLogoutRequest(xml)).toBe('ST-9-x');
    expect(parseLogoutRequest('<samlp:LogoutRequest xmlns:samlp="x"/>')).toBeNull();
    expect(parseLogoutRequest(xml.replace('ST-9-x', 'TGT-1'))).toBeNull();
  });
});

describe('ticket validation', () => {
  it('calls the configured endpoint with the exact service, no credentials, refusing redirects', async () => {
    const f = vi.fn(async (_url: string | URL | Request, _init?: RequestInit) => new Response(OK));
    expect(
      await validateTicket(server, SERVICE, 'ST-1', { fetch: f as unknown as typeof fetch }),
    ).toEqual({
      ok: true,
      user: 'alice',
    });
    const [url, init] = f.mock.calls[0]!;
    const u = new URL(String(url));
    expect(u.origin + u.pathname).toBe('https://cas.example.test/cas/p3/serviceValidate');
    expect(u.searchParams.get('service')).toBe(SERVICE);
    expect(u.searchParams.get('ticket')).toBe('ST-1');
    expect(init).toMatchObject({ redirect: 'error', credentials: 'omit' });
    expect(init?.headers).toBeUndefined();
  });

  it('report network errors, timeouts, non-200 and oversized replies as unavailable', async () => {
    const run = (f: () => Promise<Response>, timeoutMs?: number) =>
      validateTicket(server, SERVICE, 'ST-1', { fetch: f as unknown as typeof fetch, timeoutMs });
    await expect(
      run(async () => Promise.reject(new TypeError('Failed to fetch'))),
    ).rejects.toBeInstanceOf(CasUnavailable);
    await expect(run(async () => new Response('x', { status: 500 }))).rejects.toThrow(/HTTP 500/);
    await expect(run(async () => new Response('x'.repeat(70 * 1024)))).rejects.toThrow(/too large/);
    const hang = (_u: unknown, init?: RequestInit) =>
      new Promise<Response>((_, reject) =>
        init?.signal?.addEventListener('abort', () => reject(init.signal!.reason)),
      );
    await expect(
      validateTicket(server, SERVICE, 'ST-1', { fetch: hang as typeof fetch, timeoutMs: 20 }),
    ).rejects.toBeInstanceOf(CasUnavailable);
  });
});
