import { createHash, randomBytes } from 'node:crypto';
import { sep } from 'node:path';
import cookie from '@fastify/cookie';
import helmet from '@fastify/helmet';
import multipart from '@fastify/multipart';
import rateLimit from '@fastify/rate-limit';
import fastifyStatic from '@fastify/static';
import { createServices, type Actor, type MediaStorage } from '@lexican/app';
import { DomainError, HTTP_STATUS, isDomainError, operations, type OperationName } from '@lexican/core';
import { sessions, type Db } from '@lexican/db';
import { and, eq, gt, lt, sql } from 'drizzle-orm';
import Fastify, { type FastifyError, type FastifyReply, type FastifyRequest, type FastifyServerOptions } from 'fastify';
import { parseLogoutRequest, validateTicket } from './cas.ts';
import { httpDirectory, type InstitutionalDirectory } from './cauce.ts';
import type { Config } from './config.ts';
import { fsMediaStorage } from './media-storage.ts';

declare module 'fastify' {
  interface FastifyRequest {
    actor: Actor | null;
    sessionHash: string | null;
  }
}

export interface AppOptions {
  config: Config;
  db: Db;
  directory?: InstitutionalDirectory;
  media?: MediaStorage;
  clock?: () => Date;
  logger?: FastifyServerOptions['logger'];
}

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
const SLIDE_EVERY_MS = 5 * 60_000;
const SLO_PATH = '/api/auth/cas/slo';
const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');
const redactUrl = (url: string) => url.replace(/([?&](?:ticket|logoutRequest)=)[^&]*/gi, '$1[REDACTED]');

/** `inline` disposition with an ASCII fallback name and the UTF-8 original (RFC 6266/5987). */
function disposition(name: string): string {
  const ascii = name.normalize('NFKD').replace(/[^\w.-]/g, '_').slice(0, 120) || 'archivo';
  return `inline; filename="${ascii}"; filename*=UTF-8''${encodeURIComponent(name)}`;
}

export async function buildApp(opts: AppOptions) {
  const { config, db } = opts;
  const clock = opts.clock ?? (() => new Date());
  const directory = opts.directory ?? (config.cauce ? httpDirectory(config.cauce) : null);
  const services = createServices({
    db,
    clock,
    media: opts.media ?? fsMediaStorage(config.mediaDir),
    mediaUrl: (id) => `/media/${id}`,
    schoolYear: config.schoolYear,
    maxUploadBytes: config.maxUploadBytes,
  });
  const cookieName = config.secureCookies ? '__Host-sid' : 'sid';

  const logger: FastifyServerOptions['logger'] = opts.logger ?? {
    level: config.logLevel,
    redact: ['req.headers.cookie', 'req.headers.authorization', 'res.headers["set-cookie"]'],
    serializers: {
      req: (req: { method?: string; url?: string; ip?: string }) => ({
        method: req.method,
        url: redactUrl(req.url ?? ''),
        remoteAddress: req.ip,
      }),
    },
  };
  const app = Fastify({ bodyLimit: 1024 * 1024, trustProxy: config.trustProxy, logger });
  app.decorateRequest('actor', null);
  app.decorateRequest('sessionHash', null);
  // JSON or multipart only: no text/plain bodies (CSRF surface, TECH_RESEARCH §5).
  app.removeContentTypeParser('text/plain');

  await app.register(cookie);
  await app.register(helmet, {
    contentSecurityPolicy: {
      useDefaults: false,
      directives: {
        'default-src': ["'self'"],
        'script-src': ["'self'"],
        'style-src': ["'self'"],
        'img-src': ["'self'", 'blob:', 'data:'],
        'media-src': ["'self'", 'blob:', 'data:'],
        'font-src': ["'self'", 'data:'],
        'connect-src': ["'self'"],
        'object-src': ["'none'"],
        'frame-ancestors': ["'none'"],
        'base-uri': ["'self'"],
        'form-action': ["'self'", ...(config.cas ? [new URL(config.cas.baseUrl).origin] : [])],
        ...(config.secureCookies ? { 'upgrade-insecure-requests': [] } : {}),
      },
    },
    referrerPolicy: { policy: 'strict-origin-when-cross-origin' },
    hsts: config.secureCookies,
  });
  await app.register(rateLimit, { global: false });
  await app.register(multipart, {
    limits: { fileSize: config.maxUploadBytes, files: 1, fields: 0, parts: 1, fieldNameSize: 50, headerPairs: 50 },
  });

  // CSRF: SameSite=Lax cookie + Origin allowlist on every unsafe method. CAS back-channel SLO is exempt.
  app.addHook('onRequest', async (req, reply) => {
    if (SAFE_METHODS.has(req.method) || req.url.split('?')[0] === SLO_PATH) return;
    if (req.headers.origin !== config.publicOrigin)
      return reply.code(403).send({ code: 'forbidden', message: 'Origen de la petición no permitido.' });
  });

  // Session → actor. The cookie holds 256 random bits; the DB keeps only its SHA-256.
  app.addHook('onRequest', async (req) => {
    const sid = req.cookies[cookieName];
    if (!sid || !(req.url.startsWith('/api/') || req.url.startsWith('/media/'))) return;
    const now = clock();
    const hash = sha256(sid);
    const [s] = await db
      .select()
      .from(sessions)
      .where(and(eq(sessions.idHash, hash), gt(sessions.expiresAt, now)));
    if (!s) return;
    req.sessionHash = hash;
    req.actor = await services.auth.actorFor(s.userId);
    if (now.getTime() - s.lastSeenAt.getTime() > SLIDE_EVERY_MS) {
      await db
        .update(sessions)
        .set({ lastSeenAt: now, expiresAt: new Date(now.getTime() + config.sessionTtlMs) })
        .where(eq(sessions.idHash, hash));
    }
  });

  const setSessionCookie = (reply: FastifyReply, sid: string) =>
    reply.setCookie(cookieName, sid, {
      path: '/',
      httpOnly: true,
      secure: config.secureCookies,
      sameSite: 'lax',
      maxAge: Math.floor(config.sessionTtlMs / 1000),
    });

  /** New row on every login (no fixation); expired rows are purged opportunistically. */
  async function startSession(req: FastifyRequest, reply: FastifyReply, userId: string, casTicket: string | null) {
    const now = clock();
    await db.delete(sessions).where(lt(sessions.expiresAt, now));
    if (req.sessionHash) await db.delete(sessions).where(eq(sessions.idHash, req.sessionHash));
    const sid = randomBytes(32).toString('base64url');
    await db.insert(sessions).values({
      idHash: sha256(sid),
      userId,
      casTicket,
      lastSeenAt: now,
      expiresAt: new Date(now.getTime() + config.sessionTtlMs),
    });
    setSessionCookie(reply, sid);
  }

  async function endSession(req: FastifyRequest, reply: FastifyReply) {
    if (req.sessionHash) await db.delete(sessions).where(eq(sessions.idHash, req.sessionHash));
    reply.clearCookie(cookieName, { path: '/', httpOnly: true, secure: config.secureCookies, sameSite: 'lax' });
  }

  app.setErrorHandler((err: FastifyError, req, reply) => {
    if (isDomainError(err))
      return reply.code(HTTP_STATUS[err.code]).send({ code: err.code, message: err.message, details: err.details });
    const status = err.statusCode ?? 500;
    if (err.validation) return reply.code(400).send({ code: 'validation', message: 'Datos no válidos.' });
    if (status === 413) {
      const what = req.url.startsWith('/api/media') ? 'El archivo es demasiado grande.' : 'La petición es demasiado grande.';
      return reply.code(413).send({ code: 'validation', message: what });
    }
    if (status === 429)
      return reply.code(429).send({ code: 'validation', message: 'Demasiados intentos. Espera un momento y vuelve a intentarlo.' });
    if (status >= 400 && status < 500)
      return reply.code(status).send({ code: 'validation', message: 'Petición no válida.' });
    req.log.error({ err }, 'unhandled error');
    return reply.code(500).send({ code: 'internal', message: 'Error interno' });
  });

  const notFound = (reply: FastifyReply) => reply.code(404).send({ code: 'not_found', message: 'No encontrado.' });

  // ---- Operation table → REST routes (auth → validate → service → response) ----
  const inputOf = (req: FastifyRequest): Record<string, unknown> => {
    const body = req.body;
    if (body !== undefined && body !== null && (typeof body !== 'object' || Array.isArray(body)))
      throw new DomainError('validation', 'Datos no válidos.');
    return { ...(req.query as object), ...(body as object | undefined), ...(req.params as object) };
  };

  for (const name of Object.keys(operations) as OperationName[]) {
    if (name === 'login' || name === 'logout') continue;
    const op = operations[name];
    app.route({ method: op.method, url: op.path, handler: (req) => services.call(name, req.actor, inputOf(req)) });
  }

  app.post(operations.login.path, { config: { rateLimit: { max: 10, timeWindow: '1 minute' } } }, async (req, reply) => {
    if (!config.devLogin) return notFound(reply);
    const user = await services.call('login', null, inputOf(req));
    await startSession(req, reply, user.id, null);
    return user;
  });

  app.post(operations.logout.path, async (req, reply) => {
    const result = await services.call('logout', req.actor, {});
    await endSession(req, reply);
    return result;
  });

  app.get('/api/auth/providers', async () => ({ cas: config.cas !== null, password: config.devLogin }));

  app.get('/api/health', async (_req, reply) => {
    try {
      await db.execute(sql`select 1`);
      return { ok: true };
    } catch {
      return reply.code(503).send({ ok: false });
    }
  });

  // ---- CAS ----
  const cas = config.cas;
  app.get('/api/auth/cas/login', async (_req, reply) => {
    if (!cas) return notFound(reply);
    return reply.redirect(`${cas.baseUrl}/login?service=${encodeURIComponent(cas.serviceUrl)}`);
  });

  app.get<{ Querystring: { ticket?: string } }>(
    '/api/auth/cas/callback',
    { config: { rateLimit: { max: 20, timeWindow: '1 minute' } } },
    async (req, reply) => {
      if (!cas) return notFound(reply);
      const fail = (error: string) => reply.redirect(`${config.publicUrl}/entrar?error=${error}`);
      const ticket = req.query.ticket;
      if (typeof ticket !== 'string' || ticket.length === 0 || ticket.length > 512) return fail('cas');
      let subject: string;
      try {
        const r = await validateTicket(cas.baseUrl, cas.serviceUrl, ticket);
        if (!r.ok) {
          req.log.warn({ casCode: r.code }, 'CAS ticket validation failed');
          return fail('cas');
        }
        subject = r.user;
      } catch (err) {
        req.log.warn({ err }, 'CAS unreachable');
        return fail('unavailable');
      }
      try {
        if (!directory) throw new DomainError('unavailable', 'Directorio institucional no configurado.');
        const profile = await directory.lookup(subject);
        const user = await services.auth.signInInstitutional(profile);
        await startSession(req, reply, user.id, ticket);
      } catch (err) {
        // Never log directory payloads (legacy logged NIF/CIAL): only the error code.
        const code = isDomainError(err) ? err.code : 'internal';
        if (code === 'internal') req.log.error({ err }, 'CAS sign-in failed');
        else req.log.warn({ code }, 'CAS sign-in refused');
        return fail(code === 'forbidden' ? 'forbidden' : 'unavailable');
      }
      return reply.redirect(`${config.publicUrl}/`);
    },
  );

  app.get('/api/auth/cas/logout', async (req, reply) => {
    await endSession(req, reply);
    if (!cas) return reply.redirect(`${config.publicUrl}/`);
    return reply.redirect(`${cas.baseUrl}/logout?service=${encodeURIComponent(`${config.publicUrl}/`)}`);
  });

  // Back-channel single logout: form-encoded only on this route.
  await app.register(async (slo) => {
    slo.addContentTypeParser('application/x-www-form-urlencoded', { parseAs: 'string', bodyLimit: 64 * 1024 }, (_req, body, done) =>
      done(null, Object.fromEntries(new URLSearchParams(body as string))),
    );
    slo.post<{ Body: { logoutRequest?: string } }>(SLO_PATH, async (req, reply) => {
      if (!cas) return notFound(reply);
      if (cas.sloHosts.length > 0 && !cas.sloHosts.includes(req.ip)) return reply.code(403).send({ code: 'forbidden', message: 'No permitido.' });
      const xml = req.body?.logoutRequest;
      let ticket: string | null;
      try {
        ticket = typeof xml === 'string' ? parseLogoutRequest(xml) : null;
      } catch {
        ticket = null;
      }
      if (!ticket) return reply.code(400).send({ code: 'validation', message: 'Petición no válida.' });
      await db.delete(sessions).where(eq(sessions.casTicket, ticket));
      return { ok: true };
    });
  });

  // ---- Media ----
  app.post('/api/media', { config: { rateLimit: { max: 30, timeWindow: '1 minute' } } }, async (req) => {
    const actor = services.auth.requireUser(req.actor);
    const file = await req.file();
    if (!file || file.fieldname !== 'file') throw new DomainError('validation', 'Falta el archivo.');
    const bytes = await file.toBuffer();
    return services.media.uploadMedia(actor, { bytes, name: file.filename });
  });

  app.get<{ Params: { id: string } }>('/media/:id', async (req, reply) => {
    if (!UUID.test(req.params.id)) return notFound(reply);
    const m = await services.media.readMedia(req.actor, req.params.id);
    return reply
      .header('content-type', m.mime)
      .header('x-content-type-options', 'nosniff')
      .header('content-disposition', disposition(m.name))
      .header('cache-control', 'private, max-age=3600')
      .header('content-security-policy', "default-src 'none'; sandbox")
      .send(Buffer.from(m.bytes.buffer, m.bytes.byteOffset, m.bytes.byteLength));
  });

  // ---- Built SPA ----
  const webDist = config.webDist;
  if (webDist) {
    await app.register(fastifyStatic, {
      root: webDist,
      wildcard: false,
      cacheControl: false,
      setHeaders: (res, filePath) =>
        res.header(
          'cache-control',
          filePath.includes(`${sep}assets${sep}`) ? 'public, max-age=31536000, immutable' : 'no-cache',
        ),
    });
  }
  app.setNotFoundHandler((req, reply) => {
    const path = req.url.split('?')[0]!;
    const spa = webDist && (req.method === 'GET' || req.method === 'HEAD') && !path.startsWith('/api/') && !path.startsWith('/media/');
    if (spa) return reply.header('cache-control', 'no-cache').sendFile('index.html');
    return notFound(reply);
  });

  return app;
}
