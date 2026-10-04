import { openAsBlob } from 'node:fs';
import { stat } from 'node:fs/promises';
import { extname, resolve, sep } from 'node:path';
import { testDirectory, type InstitutionalDirectory, type MediaStorage } from '@lexican/app';
import type { Db } from '@lexican/db';
import { createApi, type Logger, type SessionCarrier } from '@lexican/http';
import { Hono, type Context } from 'hono';
import { deleteCookie, getCookie, setCookie } from 'hono/cookie';
import proxyaddr from 'proxy-addr';
import { httpDirectory } from './cauce.ts';
import type { Config } from './config.ts';
import { createLogger } from './log.ts';
import { fsMediaStorage } from './media-storage.ts';

/** What the Bun server adapter passes to `app.fetch` (tests pass it too). Never derived from request headers. */
export interface ServerEnv {
  remoteAddress?: string;
}

export interface ServerOptions {
  config: Config;
  db: Db;
  directory?: InstitutionalDirectory | null;
  media?: MediaStorage;
  clock?: () => Date;
  log?: Logger;
  fetch?: typeof fetch;
}

const redactUrl = (url: string) =>
  url.replace(/([?&](?:ticket|logoutRequest|state)=)[^&]*/gi, '$1[REDACTED]');

/** HttpOnly cookies: `__Host-` + Secure behind https, plain names only for local http. */
function cookieCarrier(secure: boolean): SessionCarrier {
  const sid = secure ? '__Host-sid' : 'sid';
  const state = secure ? '__Host-cas_state' : 'cas_state';
  const opts = { path: '/', httpOnly: true, secure, sameSite: 'Lax' } as const;
  return {
    getSession: (c) => getCookie(c, sid),
    setSession: (c, id, maxAge) => setCookie(c, sid, id, { ...opts, maxAge }),
    clearSession: (c) => void deleteCookie(c, sid, opts),
    getCasState: (c) => getCookie(c, state),
    setCasState: (c, v, maxAge) => setCookie(c, state, v, { ...opts, maxAge }),
    clearCasState: (c) => void deleteCookie(c, state, opts),
  };
}

/**
 * Client IP: the socket address from the server adapter, and X-Forwarded-For only through the configured trusted
 * proxies (hop count or CIDR list), never blindly (SEC-007).
 */
function clientIpResolver(trust: Config['trustProxy']) {
  const fn =
    trust === false
      ? () => false
      : typeof trust === 'number'
        ? (_addr: string, i: number) => i < trust
        : proxyaddr.compile(trust);
  return (c: Context<{ Bindings: ServerEnv }>) => {
    const remoteAddress = c.env?.remoteAddress ?? '';
    const xff = c.req.header('x-forwarded-for');
    const req = {
      socket: { remoteAddress },
      connection: { remoteAddress },
      headers: xff ? { 'x-forwarded-for': xff } : {},
    };
    return proxyaddr(req as never, fn);
  };
}

const MIME: Record<string, string> = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json',
  '.webmanifest': 'application/manifest+json',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.txt': 'text/plain; charset=utf-8',
  '.map': 'application/json',
};

/** Built SPA: fingerprinted assets are immutable, everything else revalidates; unknown GET paths get index.html. */
function spa(root: string) {
  const base = resolve(root);
  const file = async (path: string, cache: string) => {
    const full = resolve(base, `.${path}`);
    if (full !== base && !full.startsWith(base + sep)) return null;
    const s = await stat(full).catch(() => null);
    if (!s?.isFile()) return null;
    return new Response(await openAsBlob(full), {
      headers: {
        'content-type': MIME[extname(full)] ?? 'application/octet-stream',
        'content-length': String(s.size),
        'cache-control': cache,
      },
    });
  };
  return async (c: Context) => {
    let path: string;
    try {
      path = decodeURIComponent(c.req.path);
    } catch {
      return null;
    }
    if (path.includes('\0')) return null;
    const cache = path.startsWith('/assets/') ? 'public, max-age=31536000, immutable' : 'no-cache';
    return (path !== '/' && (await file(path, cache))) || file('/index.html', 'no-cache');
  };
}

/**
 * Production HTTP app: security headers, request log, trusted client IP and the built SPA around the shared
 * LexiCán API (`@lexican/http`), which owns every /api and /media route.
 */
export function buildApp(opts: ServerOptions) {
  const { config } = opts;
  const log = opts.log ?? createLogger(config.logLevel);
  const cas = config.cas;
  const directory =
    opts.directory !== undefined
      ? opts.directory
      : cas?.profiles === 'test'
        ? testDirectory()
        : config.cauce
          ? httpDirectory(config.cauce)
          : null;
  const clientIp = clientIpResolver(config.trustProxy);
  const api = createApi({
    db: opts.db,
    media: opts.media ?? fsMediaStorage(config.mediaDir),
    clock: opts.clock,
    log,
    fetch: opts.fetch,
    directory,
    clientIp,
    carrier: cookieCarrier(config.secureCookies),
    config: {
      allowedOrigin: config.publicOrigin,
      appUrl: config.publicUrl,
      sessionTtlMs: config.sessionTtlMs,
      passwordLogin: config.devLogin,
      loginRateLimit: config.loginRateLimit,
      maxUploadBytes: config.maxUploadBytes,
      mediaQuotaBytesPerDay: config.mediaQuotaBytesPerDay,
      schoolYear: config.schoolYear,
      cas: cas
        ? {
            url: cas.url,
            loginPath: cas.loginPath,
            validatePath: cas.validatePath,
            logoutPath: cas.logoutPath,
            serviceUrl: `${config.publicUrl}/api/auth/cas/callback`,
            afterLogoutUrl: `${config.publicUrl}/`,
            provider: cas.profiles === 'test' ? 'cas_test' : 'cas',
            sloHosts: cas.sloHosts,
          }
        : null,
    },
  });

  const csp = [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self'",
    "img-src 'self' blob: data:",
    "media-src 'self' blob: data:",
    "font-src 'self' data:",
    "connect-src 'self'",
    "object-src 'none'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    `form-action 'self'${cas ? ` ${new URL(cas.url).origin}` : ''}`,
    ...(config.secureCookies ? ['upgrade-insecure-requests'] : []),
  ].join('; ');
  const securityHeaders: Record<string, string> = {
    'content-security-policy': csp,
    'referrer-policy': 'strict-origin-when-cross-origin',
    'x-content-type-options': 'nosniff',
    'x-frame-options': 'DENY',
    'cross-origin-opener-policy': 'same-origin',
    'cross-origin-resource-policy': 'same-origin',
    ...(config.secureCookies
      ? { 'strict-transport-security': 'max-age=15552000; includeSubDomains' }
      : {}),
  };

  const app = new Hono<{ Bindings: ServerEnv }>();
  app.use('*', async (c, next) => {
    const start = performance.now();
    await next();
    // Route-specific headers win (e.g. the sandbox CSP of /media).
    for (const [k, v] of Object.entries(securityHeaders))
      if (!c.res.headers.has(k)) c.res.headers.set(k, v);
    log.info(
      {
        method: c.req.method,
        url: redactUrl(new URL(c.req.url).pathname + new URL(c.req.url).search),
        status: c.res.status,
        ms: Math.round(performance.now() - start),
        remoteAddress: clientIp(c),
      },
      'request',
    );
  });
  const toApi = (c: Context<{ Bindings: ServerEnv }>) => api.app.fetch(c.req.raw, c.env);
  app.all('/api/*', toApi);
  app.all('/media/*', toApi);
  const serve = config.webDist ? spa(config.webDist) : null;
  app.on(
    ['GET', 'HEAD'],
    '*',
    async (c) =>
      (serve && (await serve(c))) || c.json({ code: 'not_found', message: 'No encontrado.' }, 404),
  );
  app.all('*', (c) => c.json({ code: 'not_found', message: 'No encontrado.' }, 404));

  return { app, services: api.services, log };
}
