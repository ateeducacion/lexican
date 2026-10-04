import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { seedDemo } from '@lexican/app';
import { openPglite, openPostgres, type TestDb } from '@lexican/db/testing';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { buildApp, errSerializer } from './app.ts';
import { loadConfig } from './config.ts';
import { fsMediaStorage } from './media-storage.ts';

const ORIGIN = 'http://localhost:3999';
const CAS = 'https://cas.example.test/cas';
const CAUCE = 'https://cauce.example.test/check/';
const fixture = (n: string) =>
  readFileSync(new URL(`./__fixtures__/${n}`, import.meta.url), 'utf8');
const demoPng = new Uint8Array(
  readFileSync(new URL('../../web/public/demo/gofio.png', import.meta.url)),
);

let t: TestDb;
let mediaDir: string;
let app: Awaited<ReturnType<typeof buildApp>>;

beforeAll(async () => {
  const url = process.env.TEST_DATABASE_URL;
  t = url ? await openPostgres(url) : await openPglite();
  mediaDir = mkdtempSync(join(tmpdir(), 'lexican-media-'));
  const media = fsMediaStorage(mediaDir);
  await seedDemo({ db: t.db, clock: () => new Date(), media, mediaUrl: (id) => `/media/${id}` }, [
    { name: 'gofio.png', bytes: demoPng },
  ]);
  const config = loadConfig({
    NODE_ENV: 'test',
    DATABASE_URL: 'postgres://unused',
    PUBLIC_URL: ORIGIN,
    MEDIA_DIR: mediaDir,
    MAX_UPLOAD_MB: '0.05',
    AUTH_DEV_LOGIN: 'true',
    CAS_BASE_URL: CAS,
    CAUCE_URL: CAUCE,
    CAUCE_TOKEN: 'test-token',
    LOG_LEVEL: 'silent',
  });
  app = await buildApp({ config, db: t.db, media, logger: false });
});

afterAll(async () => {
  await app?.close();
  await t?.close();
  rmSync(mediaDir, { recursive: true, force: true });
});

afterEach(() => vi.unstubAllGlobals());

/** One session per account for the whole file (the login route is rate-limited per IP). */
const sessionsByEmail = new Map<string, string>();
async function login(email: string, password: string): Promise<string> {
  const known = sessionsByEmail.get(email);
  if (known) return known;
  const r = await app.inject({
    method: 'POST',
    url: '/api/auth/login',
    headers: { origin: ORIGIN },
    payload: { email, password },
  });
  expect(r.statusCode).toBe(200);
  const c = r.cookies.find((x) => x.name === 'sid');
  sessionsByEmail.set(email, `sid=${c!.value}`);
  return `sid=${c!.value}`;
}

const get = (url: string, cookie?: string) =>
  app.inject({ method: 'GET', url, headers: cookie ? { cookie } : {} });
const send = (
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE',
  url: string,
  cookie: string,
  payload: object,
) => app.inject({ method, url, headers: { cookie, origin: ORIGIN }, payload });

function multipart(bytes: Uint8Array, filename: string, type: string) {
  const b = 'lexicanTestBoundary';
  const head = Buffer.from(
    `--${b}\r\nContent-Disposition: form-data; name="file"; filename="${filename}"\r\nContent-Type: ${type}\r\n\r\n`,
  );
  return {
    payload: Buffer.concat([head, Buffer.from(bytes), Buffer.from(`\r\n--${b}--\r\n`)]),
    headers: { 'content-type': `multipart/form-data; boundary=${b}` },
  };
}

describe('platform', () => {
  it('health pings the database and sends security headers', async () => {
    const r = await get('/api/health');
    expect(r.statusCode).toBe(200);
    expect(r.json()).toEqual({ ok: true });
    expect(r.headers['content-security-policy']).toContain("frame-ancestors 'none'");
    expect(r.headers['content-security-policy']).toContain(
      `form-action 'self' https://cas.example.test`,
    );
    expect(r.headers['referrer-policy']).toBe('strict-origin-when-cross-origin');
    expect(r.headers['x-content-type-options']).toBe('nosniff');
  });

  it('unknown API routes are JSON 404', async () => {
    const r = await get('/api/nope');
    expect(r.statusCode).toBe(404);
    expect(r.json().code).toBe('not_found');
  });

  it('providers reflect configuration', async () => {
    expect((await get('/api/auth/providers')).json()).toEqual({ cas: true, password: true });
  });
});

describe('auth and sessions', () => {
  it('requires a session (401)', async () => {
    const r = await get('/api/classrooms');
    expect(r.statusCode).toBe(401);
    expect(r.json().code).toBe('unauthenticated');
  });

  it('rejects unsafe methods without an allowed Origin (403)', async () => {
    const payload = { email: 'profesor@ejemplo.com', password: 'profesor' };
    expect((await app.inject({ method: 'POST', url: '/api/auth/login', payload })).statusCode).toBe(
      403,
    );
    const evil = await app.inject({
      method: 'POST',
      url: '/api/auth/login',
      headers: { origin: 'https://evil.example' },
      payload,
    });
    expect(evil.statusCode).toBe(403);
  });

  it('dev login sets an HttpOnly cookie; /api/me works; logout ends the session', async () => {
    const r = await app.inject({
      method: 'POST',
      url: '/api/auth/login',
      headers: { origin: ORIGIN },
      payload: { email: 'profesor@ejemplo.com', password: 'profesor' },
    });
    const c = r.cookies.find((x) => x.name === 'sid')!;
    expect(c).toMatchObject({ httpOnly: true, sameSite: 'Lax', path: '/' });
    const cookie = `sid=${c.value}`;
    expect((await get('/api/me', cookie)).json().user.email).toBe('profesor@ejemplo.com');
    expect((await get('/api/classrooms', cookie)).statusCode).toBe(200);
    const out = await send('POST', '/api/auth/logout', cookie, {});
    expect(out.json()).toEqual({ ok: true });
    expect((await get('/api/me', cookie)).json()).toEqual({ user: null });
  });

  it('wrong password is 401', async () => {
    const r = await app.inject({
      method: 'POST',
      url: '/api/auth/login',
      headers: { origin: ORIGIN },
      payload: { email: 'profesor@ejemplo.com', password: 'nope' },
    });
    expect(r.statusCode).toBe(401);
  });
});

describe('operations', () => {
  it('validation errors are 400 with details', async () => {
    const cookie = await login('alumno1@ejemplo.com', 'alumno1');
    const dict = (await get('/api/me/dictionary', cookie)).json();
    const r = await send('POST', `/api/dictionaries/${dict.id}/entries`, cookie, {
      entry: { headword: '', senses: [] },
    });
    expect(r.statusCode).toBe(400);
    const body = r.json();
    expect(body.code).toBe('validation');
    expect(Object.keys(body.details)).toEqual(
      expect.arrayContaining(['entry.headword', 'entry.senses']),
    );
  });

  it('a student cannot publish submissions (403/404)', async () => {
    const teacher = await login('profesor@ejemplo.com', 'profesor');
    const [classroom] = (await get('/api/classrooms', teacher)).json();
    const subs = (
      await get(`/api/classrooms/${classroom.id}/submissions?status=pending`, teacher)
    ).json();
    expect(subs.length).toBeGreaterThan(0);
    const student = await login('alumno2@ejemplo.com', 'alumno2');
    const r = await send('POST', '/api/submissions/publish', student, {
      submissionIds: [subs[0].id],
    });
    expect([403, 404]).toContain(r.statusCode);
  });

  it('stale versions are 409 conflicts', async () => {
    const cookie = await login('alumno2@ejemplo.com', 'alumno2');
    const dict = (await get('/api/me/dictionary', cookie)).json();
    const entry = {
      headword: 'jairo',
      senses: [{ definition: 'Nombre de prueba para el conflicto.' }],
    };
    const res = await send('POST', `/api/dictionaries/${dict.id}/entries`, cookie, { entry });
    expect(res.statusCode, res.body).toBe(200);
    const created = res.json();
    const ok = await send('PUT', `/api/entries/${created.id}`, cookie, {
      version: created.version,
      entry,
    });
    expect(ok.statusCode, ok.body).toBe(200);
    const stale = await send('PUT', `/api/entries/${created.id}`, cookie, {
      version: created.version,
      entry,
    });
    expect(stale.statusCode).toBe(409);
    expect(stale.json().code).toBe('conflict');
  });
});

describe('media', () => {
  it('accepts a PNG and serves it only to authorized users', async () => {
    const owner = await login('alumno1@ejemplo.com', 'alumno1');
    const up = await app.inject({
      method: 'POST',
      url: '/api/media',
      ...withAuth(multipart(demoPng, 'evil.php', 'application/x-php'), owner),
    });
    expect(up.statusCode).toBe(200);
    const m = up.json();
    expect(m).toMatchObject({ kind: 'image', mime: 'image/png', url: `/media/${m.id}` });

    const dl = await get(m.url, owner);
    expect(dl.statusCode).toBe(200);
    expect(dl.headers['content-type']).toBe('image/png');
    expect(dl.headers['x-content-type-options']).toBe('nosniff');
    expect(dl.headers['content-disposition']).toMatch(/^inline; filename="/);
    expect(dl.headers['cache-control']).toContain('private');
    expect(dl.rawPayload.equals(Buffer.from(demoPng))).toBe(true);

    expect((await get(m.url)).statusCode).toBe(401);
    const other = await login('alumno2@ejemplo.com', 'alumno2');
    expect((await get(m.url, other)).statusCode).toBe(404);
    expect((await get('/media/../../etc/passwd', owner)).statusCode).toBe(404);
  });

  it('rejects script bytes disguised as an image', async () => {
    const cookie = await login('alumno1@ejemplo.com', 'alumno1');
    const bytes = new TextEncoder().encode('<script>alert(1)</script>');
    const r = await app.inject({
      method: 'POST',
      url: '/api/media',
      ...withAuth(multipart(bytes, 'x.png', 'image/png'), cookie),
    });
    expect([400, 415]).toContain(r.statusCode);
    expect(r.json().code).toBe('validation');
  });

  it('rejects files over the limit with 413', async () => {
    const cookie = await login('alumno1@ejemplo.com', 'alumno1');
    const big = new Uint8Array(80 * 1024);
    big.set(demoPng.slice(0, 64));
    const r = await app.inject({
      method: 'POST',
      url: '/api/media',
      ...withAuth(multipart(big, 'big.png', 'image/png'), cookie),
    });
    expect(r.statusCode).toBe(413);
    expect(r.json()).toMatchObject({
      code: 'validation',
      message: 'El archivo es demasiado grande.',
    });
  });

  it('requires a session to upload', async () => {
    const r = await app.inject({
      method: 'POST',
      url: '/api/media',
      ...withAuth(multipart(demoPng, 'a.png', 'image/png'), ''),
    });
    expect(r.statusCode).toBe(401);
  });
});

function withAuth(m: { payload: Buffer; headers: Record<string, string> }, cookie: string) {
  return {
    payload: m.payload,
    headers: { ...m.headers, origin: ORIGIN, ...(cookie ? { cookie } : {}) },
  };
}

/** Fake CAS + CAUCE over fetch; records requested URLs and auth headers. */
function stubNetwork(cas: string, cauce: string) {
  const calls: { url: string; auth: string | null }[] = [];
  vi.stubGlobal('fetch', async (input: string | URL, init?: RequestInit) => {
    const url = String(input);
    calls.push({ url, auth: new Headers(init?.headers).get('authorization') });
    if (url.startsWith(`${CAS}/p3/serviceValidate`)) return new Response(cas, { status: 200 });
    if (url.startsWith(CAUCE)) return new Response(cauce, { status: 200 });
    return new Response('nope', { status: 404 });
  });
  return calls;
}

const upload = async (cookie: string) => {
  const r = await app.inject({
    method: 'POST',
    url: '/api/media',
    ...withAuth(multipart(demoPng, 'foto.png', 'image/png'), cookie),
  });
  expect(r.statusCode, r.body).toBe(200);
  return r.json() as { id: string; url: string };
};
let posId = '';
const sense = (definition: string, mediaIds: string[] = [], hidden = false) => ({
  definition,
  mediaIds,
  hidden,
  partOfSpeechId: posId,
});

/** alumno1 writes entries in the personal dictionary and submits them to the demo classroom. */
async function submitAsStudent(
  entries: { headword: string; senses: ReturnType<typeof sense>[] }[],
) {
  const teacher = await login('profesor@ejemplo.com', 'profesor');
  const student = await login('alumno1@ejemplo.com', 'alumno1');
  if (!posId)
    posId = (await get('/api/vocabularies', student))
      .json()
      .find((v: { vocabulary: string }) => v.vocabulary === 'part_of_speech').id;
  const [classroom] = (await get('/api/classrooms', teacher)).json();
  const dict = (await get('/api/me/dictionary', student)).json();
  const entryIds: string[] = [];
  for (const entry of entries) {
    const r = await send('POST', `/api/dictionaries/${dict.id}/entries`, student, {
      entry: { ...entry, senses: entry.senses.map((x) => ({ ...x, partOfSpeechId: posId })) },
    });
    expect(r.statusCode, r.body).toBe(200);
    entryIds.push(r.json().id);
  }
  const sub = await send('POST', '/api/submissions', student, {
    entryIds,
    classroomIds: [classroom.id],
  });
  const created = sub.json().created as { id: string }[];
  expect(created, sub.body).toHaveLength(entries.length);
  return { teacher, student, classroom, submissionIds: created.map((c) => c.id) };
}

describe('media authorization regressions', () => {
  it('a media id written in a submitted text does not grant access; a real reference does (SEC-001)', async () => {
    const secret = await upload(await login('alumno2@ejemplo.com', 'alumno2'));
    const mine = await upload(await login('alumno1@ejemplo.com', 'alumno1'));
    const { teacher } = await submitAsStudent([
      { headword: 'zzleak', senses: [sense(`Mira ${secret.id}`)] },
      { headword: 'zzreal', senses: [sense('Con imagen', [mine.id])] },
    ]);
    expect((await get(secret.url, teacher)).statusCode).toBe(404);
    expect((await get(mine.url, teacher)).statusCode).toBe(200);
  });

  it('media of hidden entries or senses are only served to editors (SEC-009)', async () => {
    const student = await login('alumno1@ejemplo.com', 'alumno1');
    const [entryMedia, senseMedia] = [await upload(student), await upload(student)];
    const { teacher, classroom, submissionIds } = await submitAsStudent([
      {
        headword: 'zzoculta',
        senses: [sense('Uno', [entryMedia.id]), sense('Dos', [senseMedia.id])],
      },
    ]);
    const pub = await send('POST', '/api/submissions/publish', teacher, { submissionIds });
    expect(pub.json().published, pub.body).toHaveLength(1);
    const list = (
      await get(`/api/dictionaries/${classroom.id}/entries?q=zzoculta`, teacher)
    ).json();
    const entry = (await get(`/api/entries/${list.items[0].id}`, teacher)).json();
    const published = { entry: entry.senses[0].media[0].url, sense: entry.senses[1].media[0].url };

    const viewer = await login('alumno2@ejemplo.com', 'alumno2');
    expect((await get(published.entry, viewer)).statusCode).toBe(200);
    const hide = (url: string, body: object) => send('PATCH', url, teacher, body);
    expect(
      (await hide(`/api/senses/${entry.senses[1].id}/visibility`, { hidden: true })).statusCode,
    ).toBe(200);
    expect((await get(published.sense, viewer)).statusCode).toBe(404);
    expect((await get(published.entry, viewer)).statusCode).toBe(200);
    expect((await hide(`/api/entries/${entry.id}/visibility`, { hidden: true })).statusCode).toBe(
      200,
    );
    expect((await get(published.entry, viewer)).statusCode).toBe(404);
    expect((await get(published.entry, teacher)).statusCode).toBe(200);
  });

  it('enforces a per-user daily upload quota (SEC-004)', async () => {
    const config = loadConfig({
      DATABASE_URL: 'x',
      PUBLIC_URL: ORIGIN,
      MEDIA_DIR: mediaDir,
      AUTH_DEV_LOGIN: 'true',
      MEDIA_QUOTA_MB_PER_DAY: '0.01',
    });
    const quotaApp = await buildApp({ config, db: t.db, logger: false });
    try {
      const r = await quotaApp.inject({
        method: 'POST',
        url: '/api/auth/login',
        headers: { origin: ORIGIN },
        payload: { email: 'admin@ejemplo.com', password: 'admin' },
      });
      const cookie = `sid=${r.cookies.find((c) => c.name === 'sid')!.value}`;
      const up = () =>
        quotaApp.inject({
          method: 'POST',
          url: '/api/media',
          ...withAuth(multipart(demoPng, 'a.png', 'image/png'), cookie),
        });
      expect((await up()).statusCode).toBe(200);
      const second = await up();
      expect(second.statusCode).toBe(400);
      expect(second.json().message).toMatch(/límite de subida/);
    } finally {
      await quotaApp.close();
    }
  });
});

describe('join codes (SEC-002)', () => {
  it('accepts only 6 characters of the join alphabet, case-insensitively', async () => {
    const student = await login('alumno2@ejemplo.com', 'alumno2');
    for (const code of ['ABC12', 'ABCDEFG', 'ABCDE1', 'ABCDEO'])
      expect((await send('POST', '/api/classrooms/join', student, { code })).statusCode).toBe(400);
    const lower = await send('POST', '/api/classrooms/join', student, { code: 'zzzzzz' });
    expect(lower.statusCode).toBe(404);
  });

  it('throttles join attempts per user', async () => {
    const student = await login('alumno2@ejemplo.com', 'alumno2');
    const statuses: number[] = [];
    for (let i = 0; i < 12; i++)
      statuses.push(
        (await send('POST', '/api/classrooms/join', student, { code: 'ZZZZZZ' })).statusCode,
      );
    expect(statuses).toContain(429);
    const other = await login('alumno1@ejemplo.com', 'alumno1');
    expect((await send('POST', '/api/classrooms/join', other, { code: 'ZZZZZZ' })).statusCode).toBe(
      404,
    );
  });
});

/** Start a CAS login like a browser: returns the state bound into the service URL and its cookie. */
async function casLogin() {
  const r = await get('/api/auth/cas/login');
  const c = r.cookies.find((x) => x.name === 'cas_state')!;
  return { state: c.value, cookie: `cas_state=${c.value}`, response: r };
}
const callback = (ticket: string, s: { state: string; cookie: string }) =>
  app.inject({
    method: 'GET',
    url: `/api/auth/cas/callback?ticket=${ticket}&state=${s.state}`,
    headers: { cookie: s.cookie },
  });
const serviceFor = (state: string) => `${ORIGIN}/api/auth/cas/callback?state=${state}`;
const slo = (url: string, index: string) =>
  app.inject({
    method: 'POST',
    url,
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    payload: new URLSearchParams({
      logoutRequest: `<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="x" Version="2.0" IssueInstant="2026-01-01T00:00:00Z"><saml:NameID xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">u</saml:NameID><samlp:SessionIndex>${index}</samlp:SessionIndex></samlp:LogoutRequest>`,
    }).toString(),
  });

describe('CAS', () => {
  it('login sets a short-lived state cookie and binds it into the service URL', async () => {
    const { state, response: r } = await casLogin();
    expect(r.statusCode).toBe(302);
    expect(r.headers.location).toBe(
      `${CAS}/login?service=${encodeURIComponent(serviceFor(state))}`,
    );
    expect(r.cookies.find((c) => c.name === 'cas_state')).toMatchObject({
      httpOnly: true,
      sameSite: 'Lax',
      maxAge: 300,
      path: '/',
    });
    expect(state.length).toBeGreaterThanOrEqual(32);
  });

  it('callback validates the ticket, queries CAUCE and opens a session; SLO closes it', async () => {
    const calls = stubNetwork(fixture('cas-success.xml'), fixture('cauce-teacher.xml'));
    const s = await casLogin();
    const r = await callback('ST-1-abc', s);
    expect(r.statusCode).toBe(302);
    expect(r.headers.location).toBe(`${ORIGIN}/`);
    expect(calls[0]!.url).toContain('ticket=ST-1-abc');
    expect(calls[0]!.url).toContain(`service=${encodeURIComponent(serviceFor(s.state))}`);
    expect(calls[1]).toEqual({ url: `${CAUCE}cficticia`, auth: 'Bearer test-token' });
    expect(r.cookies.find((c) => c.name === 'cas_state')?.value).toBe('');

    const cookie = `sid=${r.cookies.find((c) => c.name === 'sid')!.value}`;
    const me = (await get('/api/me', cookie)).json().user;
    expect(me).toMatchObject({ firstName: 'Carmen Ficticia', globalRole: 'teacher' });
    expect(JSON.stringify(me)).not.toMatch(/00000000T|Z0000000Z/);

    expect((await slo('/api/auth/cas/slo', 'ST-1-abc')).statusCode).toBe(200);
    expect((await get('/api/me', cookie)).json()).toEqual({ user: null });
  });

  it('accepts back-channel logout on the callback URL and requires an ST- SessionIndex', async () => {
    stubNetwork(fixture('cas-success.xml'), fixture('cauce-teacher.xml'));
    const r = await callback('ST-7-slo', await casLogin());
    const cookie = `sid=${r.cookies.find((c) => c.name === 'sid')!.value}`;
    expect((await slo('/api/auth/cas/slo', 'XX-7-slo')).statusCode).toBe(400);
    expect((await get('/api/me', cookie)).json().user).not.toBeNull();
    expect((await slo('/api/auth/cas/callback', 'ST-7-slo')).statusCode).toBe(200);
    expect((await get('/api/me', cookie)).json()).toEqual({ user: null });
  });

  it('rejects callbacks without the login state (login CSRF)', async () => {
    const calls = stubNetwork(fixture('cas-success.xml'), fixture('cauce-teacher.xml'));
    const noCookie = await get('/api/auth/cas/callback?ticket=ST-4&state=whatever');
    expect(noCookie.headers.location).toBe(`${ORIGIN}/entrar?error=cas`);
    const mine = await casLogin();
    const attacker = await casLogin();
    const mixed = await callback('ST-5', { state: attacker.state, cookie: mine.cookie });
    expect(mixed.headers.location).toBe(`${ORIGIN}/entrar?error=cas`);
    expect(mixed.cookies.find((c) => c.name === 'sid')).toBeUndefined();
    expect(calls).toEqual([]);
  });

  it('invalid tickets redirect to the login page with an error', async () => {
    stubNetwork(fixture('cas-failure.xml'), fixture('cauce-teacher.xml'));
    const r = await callback('ST-bad', await casLogin());
    expect(r.statusCode).toBe(302);
    expect(r.headers.location).toBe(`${ORIGIN}/entrar?error=cas`);
    expect(r.cookies.find((c) => c.name === 'sid')).toBeUndefined();
  });

  it('users refused by CAUCE get error=forbidden', async () => {
    stubNetwork(fixture('cas-success.xml'), fixture('cauce-denied.xml'));
    const r = await callback('ST-2', await casLogin());
    expect(r.headers.location).toBe(`${ORIGIN}/entrar?error=forbidden`);
  });

  it('a directory email already used by another account does not block sign-in', async () => {
    const withEmail = fixture('cauce-teacher.xml').replace(
      '</Apellidos>',
      '</Apellidos>\n      <Email>PROFESOR@ejemplo.com</Email>',
    );
    stubNetwork(fixture('cas-success.xml').replace('cficticia', 'colision'), withEmail);
    const r = await callback('ST-6', await casLogin());
    expect(r.headers.location).toBe(`${ORIGIN}/`);
    const cookie = `sid=${r.cookies.find((c) => c.name === 'sid')!.value}`;
    expect((await get('/api/me', cookie)).json().user).toMatchObject({
      firstName: 'Carmen Ficticia',
      email: null,
    });
  });

  it('CAS unreachable gives error=unavailable', async () => {
    vi.stubGlobal('fetch', async () => {
      throw new TypeError('fetch failed');
    });
    const r = await callback('ST-3', await casLogin());
    expect(r.headers.location).toBe(`${ORIGIN}/entrar?error=unavailable`);
  });

  it('cas logout redirects to the CAS logout endpoint', async () => {
    const r = await get('/api/auth/cas/logout');
    expect(r.headers.location).toBe(`${CAS}/logout?service=${encodeURIComponent(`${ORIGIN}/`)}`);
  });
});

describe('logging', () => {
  it('database errors are logged without SQL, parameters or row values', () => {
    const pgError = Object.assign(new Error('duplicate key value violates unique constraint'), {
      code: '23505',
      severity: 'ERROR',
      detail: 'Key (lower(email))=(secreto@ejemplo.com) already exists.',
    });
    const drizzle = Object.assign(
      new Error('Failed query: insert into users values ($1)\nparams: secreto@ejemplo.com'),
      { name: 'DrizzleQueryError', cause: pgError },
    );
    for (const e of [drizzle, pgError]) {
      const out = errSerializer(e);
      expect(JSON.stringify(out)).not.toMatch(/secreto|insert|duplicate/);
      expect(out.code).toBe('23505');
    }
    expect(errSerializer(new TypeError('boom'))).toMatchObject({
      type: 'TypeError',
      message: 'boom',
    });
  });
});

describe('SPA serving', () => {
  it('serves index.html for client routes, never for /api', async () => {
    const dist = mkdtempSync(join(tmpdir(), 'lexican-web-'));
    writeFileSync(join(dist, 'index.html'), '<!doctype html><title>LexiCán</title>');
    const config = loadConfig({
      DATABASE_URL: 'x',
      PUBLIC_URL: ORIGIN,
      MEDIA_DIR: mediaDir,
      WEB_DIST: dist,
    });
    const spa = await buildApp({ config, db: t.db, logger: false });
    try {
      const page = await spa.inject({ method: 'GET', url: '/aulas/123' });
      expect(page.statusCode).toBe(200);
      expect(page.body).toContain('LexiCán');
      expect((await spa.inject({ method: 'GET', url: '/api/missing' })).statusCode).toBe(404);
      expect((await spa.inject({ method: 'GET', url: '/api/auth/providers' })).json()).toEqual({
        cas: false,
        password: false,
      });
      expect(
        (
          await spa.inject({
            method: 'POST',
            url: '/api/auth/login',
            headers: { origin: ORIGIN },
            payload: {},
          })
        ).statusCode,
      ).toBe(404);
    } finally {
      await spa.close();
      rmSync(dist, { recursive: true, force: true });
    }
  });
});
