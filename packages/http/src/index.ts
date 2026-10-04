import {
  createServices,
  type Actor,
  type CasProvider,
  type InstitutionalDirectory,
  type MediaStorage,
} from '@lexican/app';
import {
  DomainError,
  HTTP_STATUS,
  isDomainError,
  operations,
  type OperationName,
  type SchoolYearConfig,
} from '@lexican/core';
import { sessions, type Db } from '@lexican/db';
import { and, eq, gt, lt, sql } from 'drizzle-orm';
import { Hono, type Context } from 'hono';
import { bodyLimit } from 'hono/body-limit';
import {
  casLoginUrl,
  casLogoutUrl,
  CasUnavailable,
  parseLogoutRequest,
  validateTicket,
  type CasServer,
} from './cas.ts';
import { blobResponse } from './range.ts';

export * from './cas.ts';
export { blobResponse, parseRange } from './range.ts';

/**
 * The LexiCán HTTP API as one Hono app, built from the operation table. Browser-safe: no filesystem, database
 * driver, server object or runtime-specific API. The Bun server and the demo Web Worker both run it through
 * `app.fetch`, injecting only what really differs (database, media storage, how the session travels, directory).
 */

export interface Logger {
  info(obj: object, msg: string): void;
  warn(obj: object, msg: string): void;
  error(obj: object, msg: string): void;
}

/**
 * How the opaque session id and the CAS login state travel. Server: HttpOnly cookies. Demo worker: fields of the
 * transport envelope (a Web Worker cannot set cookies). Session rows and their rules are shared.
 */
export interface SessionCarrier {
  getSession(c: Context): string | undefined;
  setSession(c: Context, id: string, maxAgeS: number): void;
  clearSession(c: Context): void;
  getCasState(c: Context): string | undefined;
  setCasState(c: Context, state: string, maxAgeS: number): void;
  clearCasState(c: Context): void;
}

export interface CasConfig extends CasServer {
  /** Base service URL; the login state is appended as `state=`. Must be identical at login and validation. */
  serviceUrl: string;
  /** Where the CAS server sends the browser back after its logout (the app's home page). */
  afterLogoutUrl: string;
  /** Issuer of the identities: institutional CAS or the public test CAS (never linked to each other). */
  provider: CasProvider;
  /** Back-channel single logout source IPs (empty: any); null disables the route (the demo cannot receive it). */
  sloHosts: string[] | null;
}

export interface ApiConfig {
  /** Origin every unsafe request must send (CSRF); null where no other origin can reach the app (demo worker). */
  allowedOrigin: string | null;
  /** Prefix of the redirects after CAS login/logout: the public URL on the server, '' in the demo. */
  appUrl: string;
  sessionTtlMs: number;
  passwordLogin: boolean;
  loginRateLimit: number;
  maxUploadBytes: number;
  mediaQuotaBytesPerDay: number;
  schoolYear?: SchoolYearConfig;
  cas: CasConfig | null;
}

export interface ApiDeps {
  db: Db;
  media: MediaStorage;
  config: ApiConfig;
  carrier: SessionCarrier;
  /** Profiles for validated CAS subjects (CAUCE, or test fixtures); null fails CAS sign-in closed. */
  directory: InstitutionalDirectory | null;
  /** Client address from the server adapter (never from arbitrary headers), for rate limits and SLO. */
  clientIp: (c: Context) => string;
  log: Logger;
  clock?: () => Date;
  /** Network for CAS validation (tests inject a fake CAS). */
  fetch?: typeof fetch;
}

type Env = { Variables: { actor: Actor | null; sessionHash: string | null } };

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
const SLIDE_EVERY_MS = 5 * 60_000;
const STATE_TTL_S = 5 * 60;
const JSON_LIMIT = 1024 * 1024;
const SLO_PATH = '/api/auth/cas/slo';
const CALLBACK_PATH = '/api/auth/cas/callback';

const b64url = (bytes: Uint8Array) =>
  btoa(String.fromCharCode(...bytes))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '');
const randomToken = (n: number) => b64url(crypto.getRandomValues(new Uint8Array(n)));
async function sha256(s: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(s));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}
/** Constant-time comparison of two short ASCII strings. */
function sameString(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  let d = 0;
  for (let i = 0; i < a.length; i++) d |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return d === 0;
}

/** `inline` disposition with an ASCII fallback name and the UTF-8 original (RFC 6266/5987). */
function disposition(name: string): string {
  const ascii =
    name
      .normalize('NFKD')
      .replace(/[^\w.-]/g, '_')
      .slice(0, 120) || 'archivo';
  return `inline; filename="${ascii}"; filename*=UTF-8''${encodeURIComponent(name)}`;
}

/**
 * Log-safe view of an error: database errors carry SQL text, parameters and row values in `message`, `detail`,
 * `stack` and `cause`, so only their class and SQLSTATE are kept (SEC-005).
 */
export function safeError(err: unknown): {
  type: string;
  message: string;
  stack: string;
  code?: unknown;
} {
  const e = (err instanceof Error ? err : new Error(String(err))) as Error & {
    code?: unknown;
    cause?: unknown;
  };
  const cause = (typeof e.cause === 'object' && e.cause ? e.cause : {}) as { code?: unknown };
  const isDb = e.name === 'DrizzleQueryError' || 'severity' in e || 'severity' in cause;
  if (isDb)
    return {
      type: e.name,
      message: 'database error',
      stack: '',
      code: typeof e.code === 'string' ? e.code : cause.code,
    };
  return { type: e.name, message: e.message, stack: e.stack ?? '', code: e.code };
}

/**
 * Fixed-window, in-memory rate limiter.
 * ponytail: per process; several API replicas would each count separately (add a shared store only with an ADR).
 */
function rateLimiter(clock: () => Date) {
  const hits = new Map<string, { n: number; reset: number }>();
  return (key: string, max: number, windowMs = 60_000): boolean => {
    const now = clock().getTime();
    if (hits.size > 10_000) for (const [k, v] of hits) if (v.reset <= now) hits.delete(k);
    const h = hits.get(key);
    if (!h || h.reset <= now) {
      hits.set(key, { n: 1, reset: now + windowMs });
      return true;
    }
    return ++h.n <= max;
  };
}

const json = (c: Context, status: number, code: string, message: string, details?: unknown) =>
  c.json(details === undefined ? { code, message } : { code, message, details }, status as 200);

export function createApi(deps: ApiDeps) {
  const { db, config, carrier, log } = deps;
  const clock = deps.clock ?? (() => new Date());
  const services = createServices({
    db,
    clock,
    media: deps.media,
    mediaUrl: (id) => `/media/${id}`,
    schoolYear: config.schoolYear,
    maxUploadBytes: config.maxUploadBytes,
    maxUploadBytesPerDay: config.mediaQuotaBytesPerDay,
  });
  const allow = rateLimiter(clock);
  const app = new Hono<Env>();

  const tooMany = (c: Context) =>
    json(c, 429, 'validation', 'Demasiados intentos. Espera un momento y vuelve a intentarlo.');
  const notFound = (c: Context) => json(c, 404, 'not_found', 'No encontrado.');
  const userOrIp = (c: Context<Env>) => c.get('actor')?.userId ?? `ip:${deps.clientIp(c)}`;

  app.onError((err, c) => {
    if (isDomainError(err))
      return json(c, HTTP_STATUS[err.code], err.code, err.message, err.details);
    log.error({ err: safeError(err) }, 'unhandled error');
    return json(c, 500, 'internal', 'Error interno');
  });
  app.notFound(notFound);

  // CSRF: SameSite=Lax cookie + Origin allowlist on every unsafe method. CAS back-channel SLO (its own path or the
  // service URL) is exempt: it is server-to-server, IP-allowlisted and only ends sessions.
  app.use('*', async (c, next) => {
    if (
      config.allowedOrigin === null ||
      SAFE_METHODS.has(c.req.method) ||
      c.req.path === SLO_PATH ||
      c.req.path === CALLBACK_PATH
    )
      return next();
    if (c.req.header('origin') !== config.allowedOrigin)
      return json(c, 403, 'forbidden', 'Origen de la petición no permitido.');
    return next();
  });

  // Session → actor. The carrier holds 256 random bits; the database keeps only their SHA-256.
  app.use('*', async (c, next) => {
    c.set('actor', null);
    c.set('sessionHash', null);
    const sid = carrier.getSession(c);
    if (sid && sid.length <= 64) {
      const now = clock();
      const hash = await sha256(sid);
      const [s] = await db
        .select()
        .from(sessions)
        .where(and(eq(sessions.idHash, hash), gt(sessions.expiresAt, now)));
      if (s) {
        c.set('sessionHash', hash);
        c.set('actor', await services.auth.actorFor(s.userId));
        if (now.getTime() - s.lastSeenAt.getTime() > SLIDE_EVERY_MS)
          await db
            .update(sessions)
            .set({ lastSeenAt: now, expiresAt: new Date(now.getTime() + config.sessionTtlMs) })
            .where(eq(sessions.idHash, hash));
      }
    }
    return next();
  });

  /** New row on every login (no fixation); expired rows are purged opportunistically. */
  async function startSession(c: Context<Env>, userId: string, casTicket: string | null) {
    const now = clock();
    await db.delete(sessions).where(lt(sessions.expiresAt, now));
    const old = c.get('sessionHash');
    if (old) await db.delete(sessions).where(eq(sessions.idHash, old));
    const sid = randomToken(32);
    await db.insert(sessions).values({
      idHash: await sha256(sid),
      userId,
      casTicket,
      lastSeenAt: now,
      expiresAt: new Date(now.getTime() + config.sessionTtlMs),
    });
    carrier.setSession(c, sid, Math.floor(config.sessionTtlMs / 1000));
  }

  async function endSession(c: Context<Env>) {
    const h = c.get('sessionHash');
    if (h) await db.delete(sessions).where(eq(sessions.idHash, h));
    carrier.clearSession(c);
  }

  /** JSON object body (≤ 1 MB, application/json only: no text/plain or form posts, a CSRF surface) + query + params. */
  async function inputOf(c: Context<Env>): Promise<Record<string, unknown>> {
    let body: unknown;
    if (!SAFE_METHODS.has(c.req.method) && c.req.method !== 'DELETE') {
      const text = await c.req.text();
      if (text !== '') {
        const type = c.req.header('content-type') ?? '';
        if (!/^application\/json\s*(;|$)/i.test(type)) throw new BadRequest(415);
        try {
          body = JSON.parse(text);
        } catch {
          throw new BadRequest(400);
        }
        if (body === null || typeof body !== 'object' || Array.isArray(body))
          throw new DomainError('validation', 'Datos no válidos.');
      }
    }
    return { ...c.req.query(), ...(body as object | undefined), ...c.req.param() };
  }

  const jsonLimit = bodyLimit({
    maxSize: JSON_LIMIT,
    onError: (c) => json(c, 413, 'validation', 'La petición es demasiado grande.'),
  });

  // ---- Operation table → REST routes (identity → validation → service → response) ----
  for (const name of Object.keys(operations) as OperationName[]) {
    if (name === 'login' || name === 'logout') continue;
    const op = operations[name];
    app.on(op.method, op.path, jsonLimit, async (c) => {
      // Join codes are short: throttle guessing (SEC-002).
      if (name === 'joinClassroom' && !allow(`join:${userOrIp(c)}`, 10)) return tooMany(c);
      try {
        return c.json(await services.call(name, c.get('actor'), await inputOf(c)));
      } catch (e) {
        if (e instanceof BadRequest) return json(c, e.status, 'validation', 'Petición no válida.');
        throw e;
      }
    });
  }

  app.post(operations.login.path, jsonLimit, async (c) => {
    if (!config.passwordLogin) return notFound(c);
    if (!allow(`login:${deps.clientIp(c)}`, config.loginRateLimit)) return tooMany(c);
    let input: Record<string, unknown>;
    try {
      input = await inputOf(c);
    } catch (e) {
      if (e instanceof BadRequest) return json(c, e.status, 'validation', 'Petición no válida.');
      throw e;
    }
    const user = await services.call('login', null, input);
    await startSession(c, user.id, null);
    return c.json(user);
  });

  app.post(operations.logout.path, async (c) => {
    const result = await services.call('logout', c.get('actor'), {});
    await endSession(c);
    return c.json(result);
  });

  app.get('/api/auth/providers', (c) =>
    c.json({
      cas: config.cas ? (config.cas.provider === 'cas_test' ? 'test' : 'institutional') : null,
      password: config.passwordLogin,
    }),
  );

  app.get('/api/health', async (c) => {
    try {
      await db.execute(sql`select 1`);
      return c.json({ ok: true });
    } catch {
      return c.json({ ok: false }, 503);
    }
  });

  // ---- CAS ----
  const cas = config.cas;
  // Login CSRF (SEC-006): a random state is kept by the carrier AND bound into the service URL, so a ticket is only
  // accepted by the browser that started that login (CAS validates the exact service URL).
  const serviceFor = (state: string) =>
    `${cas!.serviceUrl}${cas!.serviceUrl.includes('?') ? '&' : '?'}state=${state}`;
  const loginFailed = (c: Context, error: string) =>
    c.redirect(`${config.appUrl}/entrar?error=${error}`);

  app.get('/api/auth/cas/login', (c) => {
    if (!cas) return notFound(c);
    const state = randomToken(24);
    carrier.setCasState(c, state, STATE_TTL_S);
    return c.redirect(casLoginUrl(cas, serviceFor(state)));
  });

  app.get(CALLBACK_PATH, async (c) => {
    if (!cas) return notFound(c);
    if (!allow(`cas:${deps.clientIp(c)}`, 20)) return tooMany(c);
    const ticket = c.req.query('ticket');
    const state = c.req.query('state');
    // The state is single-use: cleared before anything else, so a replayed or double callback fails.
    const expected = carrier.getCasState(c);
    carrier.clearCasState(c);
    if (!ticket || ticket.length > 512) return loginFailed(c, 'cas');
    if (!state || !expected || !sameString(state, expected)) {
      log.warn({}, 'CAS callback without a matching login state');
      return loginFailed(c, 'cas');
    }
    // A ticket that already opened a session is never validated again (CAS also refuses reuse).
    const [used] = await db
      .select({ h: sessions.idHash })
      .from(sessions)
      .where(eq(sessions.casTicket, ticket))
      .limit(1);
    if (used) return loginFailed(c, 'cas');
    let subject: string;
    try {
      const r = await validateTicket(cas, serviceFor(state), ticket, { fetch: deps.fetch });
      if (!r.ok) {
        log.warn({ casCode: r.code }, 'CAS ticket validation failed');
        return loginFailed(c, 'cas');
      }
      subject = r.user;
    } catch (err) {
      // Never retried: the ticket may already be consumed. The person starts a new login.
      log.warn({ err: safeError(err) }, 'CAS unreachable');
      return loginFailed(c, err instanceof CasUnavailable ? 'unavailable' : 'cas');
    }
    try {
      if (!deps.directory)
        throw new DomainError('unavailable', 'Directorio institucional no configurado.');
      const profile = await deps.directory.lookup(subject);
      const user = await services.auth.signInInstitutional(profile, cas.provider);
      await startSession(c, user.id, ticket);
    } catch (err) {
      // Never log directory payloads (legacy logged NIF/CIAL): only the error code.
      const code = isDomainError(err) ? err.code : 'internal';
      if (code === 'internal') log.error({ err: safeError(err) }, 'CAS sign-in failed');
      else log.warn({ code }, 'CAS sign-in refused');
      return loginFailed(c, code === 'forbidden' ? 'forbidden' : 'unavailable');
    }
    return c.redirect(`${config.appUrl}/`);
  });

  /** Ends the LexiCán session and, with CAS, the single sign-on session on the CAS server too. */
  app.get('/api/auth/cas/logout', async (c) => {
    await endSession(c);
    if (!cas) return c.redirect(`${config.appUrl}/`);
    return c.redirect(casLogoutUrl(cas, cas.afterLogoutUrl));
  });

  // Back-channel single logout: form-encoded only, 64 KB. CAS posts it to the service URL unless a logout URL is
  // registered, so both paths are accepted.
  const slo = async (c: Context<Env>) => {
    if (!cas || cas.sloHosts === null) return notFound(c);
    if (cas.sloHosts.length > 0 && !cas.sloHosts.includes(deps.clientIp(c)))
      return json(c, 403, 'forbidden', 'No permitido.');
    let ticket: string | null = null;
    if (/^application\/x-www-form-urlencoded\s*(;|$)/i.test(c.req.header('content-type') ?? '')) {
      try {
        const xml = new URLSearchParams(await c.req.text()).get('logoutRequest');
        ticket = xml ? parseLogoutRequest(xml) : null;
      } catch {
        ticket = null;
      }
    }
    if (!ticket) return json(c, 400, 'validation', 'Petición no válida.');
    await db.delete(sessions).where(eq(sessions.casTicket, ticket));
    return c.json({ ok: true });
  };
  const sloLimit = bodyLimit({
    maxSize: 64 * 1024,
    onError: (c) => json(c, 413, 'validation', 'Petición no válida.'),
  });
  app.post(SLO_PATH, sloLimit, slo);
  app.post(CALLBACK_PATH, sloLimit, slo);

  // ---- Media ----
  // The limit is enforced while the body streams in (with or without Content-Length), before anything is parsed.
  const uploadLimit = bodyLimit({
    maxSize: config.maxUploadBytes + 16 * 1024,
    onError: (c) => json(c, 413, 'validation', 'El archivo es demasiado grande.'),
  });
  app.post(
    '/api/media',
    async (c, next) => {
      const actor = services.auth.requireUser(c.get('actor'));
      if (!allow(`media:${actor.userId}`, 30)) return tooMany(c);
      return next();
    },
    uploadLimit,
    async (c) => {
      const actor = c.get('actor');
      if (!/^multipart\/form-data\s*;/i.test(c.req.header('content-type') ?? ''))
        return json(c, 415, 'validation', 'Petición no válida.');
      let form: FormData;
      try {
        form = await c.req.formData();
      } catch {
        return json(c, 400, 'validation', 'Petición no válida.');
      }
      const parts = [...form.entries()];
      const file = form.get('file');
      if (!(file instanceof Blob)) throw new DomainError('validation', 'Falta el archivo.');
      if (parts.length !== 1) return json(c, 400, 'validation', 'Petición no válida.');
      if (file.size > config.maxUploadBytes)
        return json(c, 413, 'validation', 'El archivo es demasiado grande.');
      const name = 'name' in file && typeof file.name === 'string' ? file.name : 'archivo';
      // The slot being filled (only decides audio vs video for WebM/MP4 containers).
      const slot = c.req.query('kind');
      const kind = slot === 'image' || slot === 'audio' || slot === 'video' ? slot : undefined;
      return c.json(
        await services.media.uploadMedia(actor, {
          bytes: new Uint8Array(await file.arrayBuffer()),
          name,
          kind,
        }),
      );
    },
  );

  // GET and HEAD (Hono serves HEAD from GET routes without a body). Authorization runs before the storage is opened.
  app.get('/media/:id', async (c) => {
    const id = c.req.param('id');
    if (!UUID.test(id)) return notFound(c);
    const m = await services.media.readMedia(c.get('actor'), id);
    return blobResponse(
      m.blob,
      c.req.header('range'),
      {
        'content-type': m.mime,
        'x-content-type-options': 'nosniff',
        'content-disposition': disposition(m.name),
        'cache-control': 'private, max-age=3600',
        'content-security-policy': "default-src 'none'; sandbox",
      },
      c.req.header('if-range') !== undefined,
    );
  });

  return { app, services };
}

class BadRequest extends Error {
  constructor(readonly status: 400 | 415) {
    super('bad request');
  }
}

export type Api = ReturnType<typeof createApi>;
