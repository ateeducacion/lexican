import { XMLParser } from 'fast-xml-parser';

/**
 * Minimal CAS 3.0 client on Web APIs (fetch, URL, streams): the same code validates tickets in the Bun server and
 * in the demo Web Worker. No CAS npm package. Protocol: https://apereo.github.io/cas/development/protocol/CAS-Protocol-Specification.html
 */

export interface CasServer {
  /** Absolute http(s) URL, possibly with a path prefix (`https://host/cas`); no query or fragment. */
  url: string;
  loginPath: string;
  validatePath: string;
  logoutPath: string;
}

/** A CAS path: absolute (`/…`), not protocol-relative, no `..`, query, fragment or backslash. */
export const isCasPath = (p: string): boolean =>
  /^\/(?!\/)[A-Za-z0-9._~!$&'()*+,;=:@%/-]*$/.test(p) && !p.split('/').includes('..');

/** `https://host/cas` + `/login` → `https://host/cas/login` (prefix kept, one slash), refusing anything that escapes the server. */
export function casEndpoint(server: CasServer, path: string): URL {
  if (!isCasPath(path)) throw new Error('Invalid CAS path');
  const base = new URL(server.url);
  if (base.search || base.hash || (base.protocol !== 'https:' && base.protocol !== 'http:'))
    throw new Error('Invalid CAS URL');
  const url = new URL(base.href.replace(/\/+$/, '') + path);
  if (url.origin !== base.origin) throw new Error('Invalid CAS path');
  return url;
}

const withParams = (url: URL, params: Record<string, string>): string => {
  url.search = new URLSearchParams(params).toString();
  return url.href;
};

export const casLoginUrl = (server: CasServer, service: string): string =>
  withParams(casEndpoint(server, server.loginPath), { service });

export const casLogoutUrl = (server: CasServer, service: string): string =>
  withParams(casEndpoint(server, server.logoutPath), { service });

const parser = new XMLParser({
  removeNSPrefix: true,
  ignoreAttributes: false,
  attributeNamePrefix: '@_',
  trimValues: true,
  parseTagValue: false,
  isArray: (name) => name === 'InfoCentro',
});

type Xml = Record<string, unknown>;

/** fast-xml-parser never resolves external entities; any DTD is refused as well, so entity expansion cannot apply. */
export function parseXmlSafe(xml: string): Xml {
  if (/<!DOCTYPE|<!ENTITY/i.test(xml)) throw new Error('XML with DTD is not allowed');
  return parser.parse(xml) as Xml;
}

const obj = (v: unknown): Xml | undefined => (v && typeof v === 'object' ? (v as Xml) : undefined);

export type CasResult = { ok: true; user: string } | { ok: false; code: string };

/** Only an unambiguous success counts: a non-empty user and no failure element alongside it. */
export function parseServiceResponse(xml: string): CasResult {
  let r: Xml | undefined;
  try {
    r = obj(parseXmlSafe(xml).serviceResponse);
  } catch {
    return { ok: false, code: 'INVALID_RESPONSE' };
  }
  const failure =
    obj(r?.authenticationFailure) ?? (r && 'authenticationFailure' in r ? {} : undefined);
  const user = obj(r?.authenticationSuccess)?.user;
  if (!failure && typeof user === 'string' && user.trim() && user.length <= 256)
    return { ok: true, user: user.trim() };
  const code = failure?.['@_code'];
  return { ok: false, code: typeof code === 'string' ? code.slice(0, 64) : 'INVALID_RESPONSE' };
}

/** The CAS server could not be read (network, timeout, CORS in a browser, redirect, oversized or non-200 reply). */
export class CasUnavailable extends Error {
  override readonly name = 'CasUnavailable';
}

const MAX_RESPONSE_BYTES = 64 * 1024;

async function readLimited(res: Response, max: number): Promise<string> {
  const reader = res.body?.getReader();
  if (!reader) return '';
  const chunks: Uint8Array[] = [];
  let size = 0;
  for (;;) {
    const { done, value } = await reader.read();
    if (done) break;
    size += value.byteLength;
    if (size > max) {
      await reader.cancel();
      throw new CasUnavailable('CAS response too large');
    }
    chunks.push(value);
  }
  const all = new Uint8Array(size);
  let at = 0;
  for (const c of chunks) {
    all.set(c, at);
    at += c.byteLength;
  }
  return new TextDecoder().decode(all);
}

/**
 * Validate a service ticket once. `service` must be byte-identical to the one sent to the login page. Redirects are
 * refused and no credentials or custom headers are sent (a simple CORS request where a browser runs this). A thrown
 * CasUnavailable means the ticket state is unknown: callers must not retry it, only start a new login.
 */
export async function validateTicket(
  server: CasServer,
  service: string,
  ticket: string,
  opts: { fetch?: typeof fetch; timeoutMs?: number } = {},
): Promise<CasResult> {
  const url = withParams(casEndpoint(server, server.validatePath), { service, ticket });
  let res: Response;
  try {
    res = await (opts.fetch ?? fetch)(url, {
      signal: AbortSignal.timeout(opts.timeoutMs ?? 5000),
      redirect: 'error',
      credentials: 'omit',
    });
  } catch (e) {
    throw new CasUnavailable(e instanceof Error ? e.name : 'fetch failed');
  }
  if (!res.ok) {
    await res.body?.cancel();
    throw new CasUnavailable(`HTTP ${res.status}`);
  }
  try {
    return parseServiceResponse(await readLimited(res, MAX_RESPONSE_BYTES));
  } catch (e) {
    if (e instanceof CasUnavailable) throw e;
    throw new CasUnavailable('CAS response unreadable');
  }
}

/** SessionIndex (the service ticket) of a back-channel `samlp:LogoutRequest`, or null. */
export function parseLogoutRequest(xml: string): string | null {
  const idx = obj(parseXmlSafe(xml).LogoutRequest)?.SessionIndex;
  return typeof idx === 'string' && idx.startsWith('ST-') && idx.length <= 512 ? idx : null;
}
