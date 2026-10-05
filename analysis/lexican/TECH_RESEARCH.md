# LexiCán: technology research (input for ADRs)

> Investigación inicial del 2026-10-04; incluye propuestas y ejemplos que se sustituyeron antes de la primera versión.
> Para desarrollar o desplegar LexiCán, consultar [developers.md](../../developers.md) y los [ADRs vigentes](../../docs/adr/).

Date: 2026-10-04. Scope: 1er-prompt.md §3, §8, §14–15, §38–42, §44–47, §105–106.

Method: versions, dates and licenses come from `npm view <pkg> version time license` (run 2026-10-04). Advisories come from the GitHub Advisory DB (`gh api /advisories?ecosystem=npm&affects=<pkg>`). Docs come from Context7 and the official sites. Every snippet marked **[verified]** was compiled and run in a scratch project with `tsc --strict`, Vitest 5.0.3, Vite 8.3.2, Playwright 1.63/Chromium and `postgres:18-alpine` via Testcontainers. Nothing was installed in the repo.

## 0. Version baseline (npm, 2026-10-04)

| Package | Latest | Published | License | Notes |
|---|---|---|---|---|
| Node.js | 24.21.0 (LTS "Krypton") | 2026-09-07 | MIT | 24 goes to **maintenance on 2026-10-20**; **Node 26 becomes LTS on 2026-10-28** (nodejs/Release schedule.json). Use `engines: ">=24"` and switch CI to 26 after 28 Oct. |
| typescript | 7.0.2 (latest) / 6.0.3 | 2026-07-08 / 2026-04-16 | Apache-2.0 | See §10. Pin `~6.0.3`. |
| vite | 8.3.2 | 2026-10-01 | MIT | Rolldown-based. Advisories GHSA-fx2h/4w7w are fixed in ≥8.0.16. |
| react | 19.3.0 | 2026-10-02 | MIT | |
| react-router | **8.4.0** (v7 line 7.18.4) | 2026-09-15 | MIT | v8 GA 2026-06-17. See §9. |
| fastify | 5.12.5 | 2026-09-30 | MIT | **≥5.12.5 is required**: 4 advisories published 2026-09-30, including an auth bypass via malformed URLs (GHSA-p68q-wchp-6fh7, <5.12.2). |
| drizzle-orm / drizzle-kit | 0.45.3 / 0.31.11 | 2026-09-21 | Apache-2.0 / MIT | **≥0.45.2 is required** (GHSA-gpj5-g38j-94v9, identifier SQLi). v1.0.0-rc.5 exists on the `rc` tag. See §2. |
| pg | 8.23.1 | 2026-09-30 | MIT | |
| @electric-sql/pglite | 0.5.8 | 2026-08-26 | Apache-2.0 | Postgres 18.3 since 0.5.0. No advisories. |
| vitest | 5.0.3 | 2026-09-30 | MIT | Peer `vite ^6.4 \|\| ^7 \|\| ^8`. |
| @playwright/test | 1.63.0 | 2026-10-04 | Apache-2.0 | |
| zod | 4.6.5 | 2026-09-13 | MIT | |
| testcontainers / @testcontainers/postgresql | 12.2.0 | 2026-09-28 | MIT | |

---

## 1. PGlite in the Vite demo

**Decision.** Use `@electric-sql/pglite` 0.5.x on the main thread with `idb://lexican-demo-v1`. Load it with a dynamic `import()` only in the `demo` build mode. Vite needs only `optimizeDeps.exclude` (plus `worker.format: 'es'` if a worker is ever added). Reset closes the client and calls `indexedDB.deleteDatabase('/pglite/lexican-demo-v1')`. Guard against two tabs with the Web Locks API instead of PGliteWorker for now.

- **License and maintenance.** Apache-2.0. 0.5.8 published 2026-08-26. 0.5.0 upgraded to Postgres 18.3 and moved extensions out of the main package. No GHSA advisories.
- **Sources.** https://pglite.dev/docs/bundler-support · https://pglite.dev/docs/filesystems · https://pglite.dev/docs/multi-tab-worker · https://github.com/electric-sql/pglite/blob/main/packages/pglite/CHANGELOG.md

**Measured facts [verified]:**

- **Assets.** PGlite references `new URL('./pglite.wasm' | './pglite.data' | './initdb.wasm', import.meta.url)`. Vite 8 emits these as hashed assets automatically, and `base: '/lexican/'` is honoured. No `assetsInclude` and no copy plugin are needed.
- **Demo build output.**

  | File | Raw | gzip |
  |---|---|---|
  | `pglite-*.wasm` | 10.09 MB | 3.47 MB |
  | `pglite-*.data` | 6.30 MB | ~1.85 MB if the host compresses it |
  | `initdb-*.wasm` | 395 kB | 148 kB |
  | demo JS chunk (pglite + drizzle) | 710 kB | 164 kB |

  The login entry chunk is 2.5 kB. Without `--mode demo` the build contains **zero** PGlite bytes, because the `import.meta.env.MODE` check tree-shakes the dynamic import.
- **Timings in Chromium.** The login text renders about 30–60 ms after navigation. The DB is created, migrated and ready about 1.0–1.4 s later on a cold start.
- **Persistence.** After `insert` and a reload the data is still there. After reset and a reload the database is recreated empty. The IndexedDB database name is `"/pglite/" + dataDir`: `indexedDB.databases()` returned `['/pglite/lexican-demo-v1']`. This comes from Emscripten IDBFS mounting at `/pglite/<dataDir>`.
- **Gotcha: `relaxedDurability: true` lost the last write on an immediate reload** (the count stayed 1 instead of 2). Do not use it in the demo. Keep the default, which flushes to IndexedDB after each query.
- **Build warning.** Rolldown warns about direct `eval` inside Emscripten glue. The warning is harmless. If a CSP is ever applied to the demo it needs `'wasm-unsafe-eval'`. GitHub Pages sends no CSP.
- **Bundle-size discrepancy.** The Drizzle PGlite page says "2.6mb gzipped". The measured total is about 5.5 MB gzip (wasm 3.47 MB plus data 1.85 MB). GitHub Pages compression of `.data` (`application/octet-stream`) must be checked in Phase 3. Budget for about 3.5–5.5 MB on the first demo visit.
- **Bundled extensions.** `@electric-sql/pglite/contrib/*` ships `pg_trgm`, `unaccent`, `citext` and others. These are useful for search parity with production Postgres.

**Worker vs main thread.** PGlite is single-connection. Two tabs on the same `idb://` directory are unsafe. `PGliteWorker` (from `@electric-sql/pglite/worker`) does leader election across tabs and moves queries off the main thread. It needs a `?worker` import and `worker.format: 'es'`.

Two problems with PGliteWorker:

- The Context7 snippet for it is syntactically broken (unbalanced braces) and omits `worker.format`. The official page has the fix.
- `drizzle-orm/pglite` types its client as `TClient extends PGlite`, so a `PGliteWorker` needs a cast. This was not verified at runtime.

For a single-user demo the simpler option is a main-thread instance plus a Web Locks guard. Upgrade to PGliteWorker if real users open several tabs.

```ts
// apps/web/src/demo/db.ts  [verified in Chromium via Playwright]
import { PGlite } from '@electric-sql/pglite';
import { drizzle } from 'drizzle-orm/pglite';
import * as schema from '@lexican/db/schema';
import { migrateBundled } from '@lexican/db/migrate-bundled';
import journal from '@lexican/db/drizzle/meta/_journal.json';

const sqlFiles = import.meta.glob<string>('../../../../packages/db/drizzle/*.sql', { query: '?raw', import: 'default', eager: true });
const sqlByTag = Object.fromEntries(Object.entries(sqlFiles).map(([p, s]) => [p.split('/').pop()!.replace(/\.sql$/, ''), s]));

export const DATA_DIR = 'lexican-demo-v1';

export async function openDemoDb() {
  const client = await PGlite.create(`idb://${DATA_DIR}`); // NO relaxedDurability
  const db = drizzle({ client, schema });
  await migrateBundled(db, journal, sqlByTag);              // see §2
  return { client, db };
}

export async function resetDemoDb(client: PGlite) {
  await client.close();
  await new Promise<void>((resolve, reject) => {
    const req = indexedDB.deleteDatabase(`/pglite/${DATA_DIR}`);
    req.onsuccess = () => resolve();
    req.onerror = () => reject(req.error);
    req.onblocked = () => console.warn('reset blocked by another tab'); // proceeds once other tabs close
  });
  location.reload();
}

// ponytail: single-tab guard; switch to PGliteWorker if multi-tab demo use matters
export const acquireDemoLock = () =>
  new Promise<boolean>((ok) => navigator.locks.request('lexican-demo-db', { ifAvailable: true }, (lock) => {
    ok(!!lock);
    return lock ? new Promise(() => {}) : undefined; // hold for page lifetime
  }));
```

```ts
// apps/web/src/main.tsx – login renders first, PGlite fetched afterwards [verified]
if (import.meta.env.MODE === 'demo') void import('./demo/db');
```

```ts
// apps/web/vite.config.ts [verified]
import { defineConfig } from 'vite';
export default defineConfig(({ mode }) => ({
  base: mode === 'demo' ? '/lexican/' : '/',
  optimizeDeps: { exclude: ['@electric-sql/pglite'] },
  worker: { format: 'es' }, // only matters if PGliteWorker is adopted
}));
```

---

## 2. One Drizzle schema for PGlite and node-postgres, with migrations in the browser

**Decision.** Put a single `packages/db/src/schema.ts` (`drizzle-orm/pg-core`) in front of two drivers: `drizzle-orm/pglite` for the demo and tests, and `drizzle-orm/node-postgres` for production. Generate migrations with `drizzle-kit generate` only, never `push`.

- **Production** uses the stock `migrate()` from `drizzle-orm/node-postgres/migrator`.
- **The browser** uses a ~30-line `migrateBundled()`. It feeds Vite-bundled SQL (`import.meta.glob(..., { query: '?raw' })`) and `_journal.json` into the public `PgDialect.migrate()`. It writes the same `drizzle.__drizzle_migrations` rows, with the same SHA-256 hashes, as the Node migrator.
- **Pin `drizzle-orm@~0.45.3` / `drizzle-kit@~0.31.11`.**

**Why a custom migrator.** `drizzle-orm/pglite/migrator` is just `readMigrationFiles(config)` followed by `db.dialect.migrate(...)`. `readMigrationFiles` imports `node:fs` and `node:crypto`, so it cannot run in a browser. I read this in the 0.45.3 source. `PgDialect.migrate(migrations, session, config)` is public. The only internal piece is that `db.dialect` is `@internal`, which is avoided by constructing `new PgDialect()`. The applied/not-applied check compares `created_at` against the journal `when` and does not compare hashes.

A third-party option exists: `@proj-airi/drizzle-orm-browser-migrator` plus its unplugin, found via Context7 (MIT, 0.1.6, last published 2026-04-24). Not chosen: it adds two young dependencies for 30 lines of code.

**Drizzle v1 risk.** The official docs now tell you to install `drizzle-orm@rc` (1.0.0-rc.5), while npm `latest` is still 0.45.3. This is a discrepancy between the docs and npm. v1 removes `meta/_journal.json` and switches to one folder per migration (`<timestamp>_<name>/migration.sql`) with new tracking columns (`name`, `applied_at`) and a `drizzle-kit up` conversion step. `migrateBundled` must be rewritten when LexiCán moves to v1. Record that in the ADR as a known upgrade cost.

- **Sources.**
  - https://orm.drizzle.team/docs/connect-pglite
  - https://orm.drizzle.team/docs/upgrade-v1
  - https://orm.drizzle.team/docs/v0-v1-changes
  - https://github.com/drizzle-team/drizzle-orm/discussions/2832
  - https://github.com/proj-airi/drizzle-orm-browser
- **Context7.** `/drizzle-team/drizzle-orm-docs` returned no PGlite migrator content. Use the official docs and the source code instead.

```ts
// packages/db/src/migrate-bundled.ts  [verified: tsc --strict, PGlite + Postgres 18, idempotent on re-run]
import { PgDialect, type PgDatabase, type PgQueryResultHKT, type PgSession } from 'drizzle-orm/pg-core';
import type { MigrationMeta } from 'drizzle-orm/migrator';

export interface Journal { entries: { idx: number; when: number; tag: string; breakpoints: boolean }[] }

async function sha256(text: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

export async function migrateBundled(db: PgDatabase<PgQueryResultHKT, any>, journal: Journal, sqlByTag: Record<string, string>) {
  const migrations: MigrationMeta[] = [];
  for (const e of journal.entries) {
    const query = sqlByTag[e.tag];
    if (query === undefined) throw new Error(`Missing migration SQL for ${e.tag}`);
    migrations.push({ sql: query.split('--> statement-breakpoint'), bps: e.breakpoints, folderMillis: e.when, hash: await sha256(query) });
  }
  await new PgDialect().migrate(migrations, db._.session as unknown as PgSession, { migrationsFolder: '' });
}
```

Seed-version marker (§15): after `migrateBundled`, read and write a one-row `demo_meta(seed_version)` table and seed when it is missing or older.

---

## 3. Contract tests: same repository code on PGlite and real Postgres

**Decision.**

- **Database handle type.** Repositories accept `type Db = PgDatabase<PgQueryResultHKT, typeof schema>`. Both `PgliteDatabase` and `NodePgDatabase` are assignable to it.
- **Test runner.** Vitest `describe.each` over two factories: PGlite in-memory, and Postgres via `@testcontainers/postgresql` (`postgres:18-alpine`).
- **One mechanism everywhere.** Use Testcontainers locally and in GitHub Actions; `ubuntu-latest` runners have Docker. Do not keep a parallel docker-compose service. If a developer cannot run Docker, accept an optional `TEST_DATABASE_URL`.
- **Scaling up.** With many test files, start the container once in `globalSetup` and pass the URL with `project.provide('pgUrl', …)` / `inject('pgUrl')` (Vitest docs, confirmed via Context7).

**Is the API identical across drivers? [verified, 8/8 tests green on both]**

| Behaviour | Result on both drivers |
|---|---|
| `insert().returning()` | same row shape; `timestamp withTimezone` → `Date` |
| `db.transaction()` | commit works; `update … returning` inside a transaction works |
| rollback on throw | rolls back |
| unique violation | surfaces with `code '23505'`, read as `err.cause?.code ?? err.code` |
| `db.query.<table>.findFirst` (RQB v1) | works |
| re-running migrations | no-op |

**Semantic differences to remember.**

- PGlite is a single connection, so there are no concurrent transactions. Isolation-level and locking tests (`SELECT … FOR UPDATE`, optimistic concurrency in §65) must run only against Postgres; tag them.
- `bytea` comes back as `Uint8Array` from PGlite and as `Buffer` from pg. `Buffer` is a subclass of `Uint8Array`, so type the column as `Uint8Array`.

- **Versions.** testcontainers 12.2.0 (MIT, 2026-09-28) · vitest 5.0.3.
- **Sources.** https://node.testcontainers.org/modules/postgresql/ · https://vitest.dev/config/globalsetup

```ts
// packages/db/test/repo.contract.test.ts  [verified]
import { PGlite } from '@electric-sql/pglite';
import { drizzle as drizzlePglite } from 'drizzle-orm/pglite';
import { drizzle as drizzlePg } from 'drizzle-orm/node-postgres';
import { PostgreSqlContainer } from '@testcontainers/postgresql';
import { Pool } from 'pg';

const drivers = [
  { name: 'pglite', setup: async () => { const c = new PGlite(); return { db: drizzlePglite({ client: c, schema }), close: () => c.close() }; } },
  { name: 'postgres', setup: async () => {
      const ctr = await new PostgreSqlContainer('postgres:18-alpine').start();
      const pool = new Pool({ connectionString: ctr.getConnectionUri() });
      return { db: drizzlePg({ client: pool, schema }), close: async () => { await pool.end(); await ctr.stop(); } };
  } },
];
describe.each(drivers)('repository contract: $name', ({ setup }) => {
  let ctx: Awaited<ReturnType<(typeof drivers)[number]['setup']>>;
  beforeAll(async () => { ctx = await setup(); await migrateBundled(ctx.db, journal, sqlByTag); }, 120_000);
  afterAll(() => ctx.close());
  it('transaction rollback', async () => {
    await expect(ctx.db.transaction(async (tx) => { await tx.insert(dictionaries).values({ name: 'c' }); throw new Error('boom'); })).rejects.toThrow('boom');
    expect(await ctx.db.query.dictionaries.findFirst({ where: (t, { eq }) => eq(t.name, 'c') })).toBeUndefined();
  });
});
```

---

## 4. CAS 3.0 for Node

**Decision.** Implement the client flow directly. It is about 40 lines: `fetch` plus `fast-xml-parser` 5.11.x, with every DTD rejected before parsing. Put it behind an `InstitutionalAuth` adapter. Do not adopt a CAS npm package.

**Legacy facts.** `composer.json` pins `apereo/phpcas 1.5.0`. `config/cas.php` defaults to `CAS_VERSION=3.0`. `app/Cas/CasManager.php` calls `phpCAS::handleLogoutRequests` (SLO is used), `phpCAS::getAttributes`, and can call `setNoCasServerValidation` (check TLS validation in the audit).

**Candidate libraries.** All are unmaintained.

| Package | Version | Last registry change | Notes |
|---|---|---|---|
| `cas-authentication` | 0.0.8 | 2022-06 | Express |
| `node-cas` | 1.0.1 | 2022-06 | |
| `fastify-cas` | 2.0.0 | 2022-05 | |
| `passport-cas` | 0.1.1 | 2022-06 | |
| `passport-cas2` | 0.0.12 | 2022-06 | |
| `http-cas-client` | 0.4.3 | 2022-05 | |
| `connect-cas2` | 1.2.5 | 2022-06 | |
| `nodejs-cas` | 1.0.12 | 2022-06 | last commit ~10 years ago |

All are MIT, none supports Fastify 5, and §38 forbids adopting an abandoned CAS library. The protocol surface we need is tiny:

1. Redirect to `{cas}/login?service=…`.
2. On callback, `GET {cas}/p3/serviceValidate?service&ticket`.
3. Parse `cas:authenticationSuccess` / `cas:user` / `cas:attributes`.
4. Logout redirects to `{cas}/logout?service=…`.
5. Back-channel SLO: CAS POSTs `application/x-www-form-urlencoded` with field `logoutRequest` containing `<samlp:LogoutRequest>…<samlp:SessionIndex>ST-…`. Delete the session whose `cas_ticket` equals the SessionIndex. This is why the sessions table (§5) stores the service ticket.

**XML parser choice.** `fast-xml-parser` 5.11.2 (MIT, 2026-09-29, pure JS) was chosen over `@xmldom/xmldom`.

- It never resolves external entities, so XXE file or URL fetches are impossible.
- Its advisory history is all about DOCTYPE entity expansion: GHSA-8r6m (2026-07, fixed 5.10.1), GHSA-8gc5 (fixed 5.5.6) and GHSA-m7jm (critical, fixed 5.3.5).
- CAS responses never contain a DTD, so the mitigation is to **refuse any `<!DOCTYPE` or `<!ENTITY`** before parsing. Built-in entities (`&amp;`) still decode.
- xmldom had 8 high advisories on 2026-09-08 and is heavier.

The CAS 3.0 spec (3.0.3) also allows `format=JSON`. Whether the Gobierno de Canarias CAS supports it must be checked during the audit. XML is still needed for SLO either way.

- **Sources.**
  - https://apereo.github.io/cas/7.3.x/protocol/CAS-Protocol-Specification.html (§2.5, §2.6 `/p3/serviceValidate`, SLO section)
  - https://github.com/NaturalIntelligence/fast-xml-parser (Context7 `/naturalintelligence/fast-xml-parser`: `processEntities` limits, `removeNSPrefix`)
  - GHSA list above.

```ts
// apps/api/src/auth/cas.ts  [verified: success, failure, DTD/XXE rejection, SLO parsing]
import { XMLParser } from 'fast-xml-parser';
const parser = new XMLParser({ removeNSPrefix: true, ignoreAttributes: false, attributeNamePrefix: '', trimValues: true, parseTagValue: false });

function parseSafe(xml: string): any {
  if (/<!DOCTYPE|<!ENTITY/i.test(xml)) throw new Error('CAS: DTD not allowed');
  return parser.parse(xml);
}
export type CasResult = { ok: true; user: string; attributes: Record<string, string | string[]> } | { ok: false; code: string; message: string };

export function parseServiceResponse(xml: string): CasResult {
  const r = parseSafe(xml)?.serviceResponse;
  if (r?.authenticationSuccess) return { ok: true, user: String(r.authenticationSuccess.user), attributes: r.authenticationSuccess.attributes ?? {} };
  const f = r?.authenticationFailure;
  return { ok: false, code: f?.code ?? 'INVALID_RESPONSE', message: String(f?.['#text'] ?? f ?? '') };
}
export async function validateTicket(casBaseUrl: string, service: string, ticket: string): Promise<CasResult> {
  const url = new URL(`${casBaseUrl}/p3/serviceValidate`);
  url.search = new URLSearchParams({ service, ticket }).toString();
  const res = await fetch(url, { signal: AbortSignal.timeout(5000), redirect: 'error' });
  if (!res.ok) return { ok: false, code: `HTTP_${res.status}`, message: 'CAS unavailable' };
  return parseServiceResponse(await res.text());
}
export function parseLogoutRequest(xml: string): string | null {
  const idx = parseSafe(xml)?.LogoutRequest?.SessionIndex;
  return typeof idx === 'string' && idx.startsWith('ST-') ? idx : null;
}
```

Hardening notes for the ADR:

- `service` must be a fixed configured URL, never built from the request `Host`.
- Repeated attributes become arrays (verified).
- Use `@fastify/formbody` or `request.body` parsing for the SLO POST.
- The SLO endpoint is CSRF-exempt but should check the source IP against `CAS_REAL_HOSTS`, as the legacy config does.

---

## 5. Fastify sessions, CSRF, rate limit, CSP

**Decision.**

- **Sessions.** A custom `sessions` table managed by Drizzle, plus `@fastify/cookie` 11.1.x.
  - The cookie value is an opaque 256-bit random id, `__Host-sid` with `HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age`. No signing is needed because it is unguessable.
  - The DB stores `sha256(id)`, so a DB leak does not leak live sessions.
  - Columns: `id_hash` (PK), `user_id` (FK), `cas_ticket` (for SLO), `created_at`, `last_seen_at`, `expires_at`.
  - Expiry is purged by a `DELETE … WHERE expires_at < now()` on login or by a cron job.
  - Regenerate the id on login (a new row), which prevents fixation.
- **CSRF.** No `@fastify/csrf-protection`. Rely on `SameSite=Lax` plus an `onRequest` **Origin allowlist check on every unsafe method**, and the API only accepts JSON or multipart. The CAS SLO back-channel is the only exemption.
  - Why `Lax` and not `Strict`: users arriving from CAS or from links in the institutional portal would look logged out on the first top-level navigation.
- **Rate limit.** `@fastify/rate-limit` 11.2 with `global: false` and a per-route `config.rateLimit` on the login, CAS callback and upload routes. Memory store, single instance, no Redis (§40).
- **CSP.** `@fastify/helmet` 13.1 with explicit directives (below). The SPA is served by the same origin, so `script-src 'self'` works with Vite output.

**Why not `@fastify/session`.** It is 11.1.3 (MIT, 2026-09-14) and its last advisory was GHSA-pj27, fixed in 10.9.0. But:

- Its store contract is callback-style `set/get/destroy` (express-session compatible).
- It defaults to `saveUninitialized: true`.
- There is no maintained Postgres store for Fastify: `connect-pg-simple` 10.0.0 was last updated 2024-09 and creates its own table outside Drizzle migrations.
- A Drizzle store would be the same amount of code as the custom table, and the custom table gives SLO lookup by ticket and "log out all my sessions" for free.

**Why not `@fastify/csrf-protection`.** It is 8.0.1 (MIT, 2026-08-02) and its README says CSRF remains the developer's responsibility. Its synchronizer tokens add a token endpoint and client plumbing. With a same-origin JSON API, a SameSite cookie and an Origin check, it is redundant. Revisit if the API is ever served cross-origin or accepts `application/x-www-form-urlencoded` from browsers.

- **Sources.** https://github.com/fastify/session · https://github.com/fastify/csrf-protection · https://github.com/fastify/fastify-rate-limit · https://github.com/fastify/fastify-helmet (Context7 `/fastify/fastify-helmet`) · https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

```ts
// apps/api/src/app.ts (excerpt)  [verified with fastify.inject: 403 without Origin, 400 on invalid body,
// Set-Cookie "__Host-sid=…; HttpOnly; Secure; SameSite=Lax", CSP header present, 429 after limit]
await app.register(cookie);
await app.register(helmet, { contentSecurityPolicy: { directives: {
  'default-src': ["'self'"], 'img-src': ["'self'", 'data:', 'blob:'], 'media-src': ["'self'", 'blob:'],
  'object-src': ["'none'"], 'frame-ancestors': ["'none'"] } } });
await app.register(rateLimit, { global: false });

app.addHook('onRequest', async (req, reply) => {
  if (['GET', 'HEAD', 'OPTIONS'].includes(req.method) || req.url === '/api/auth/cas/slo') return;
  if (!req.headers.origin || !ALLOWED_ORIGINS.has(req.headers.origin)) return reply.code(403).send({ error: 'bad origin' });
});

app.post('/api/auth/demo-login', { config: { rateLimit: { max: 10, timeWindow: '1 minute' } }, schema: { body: LoginBody } }, async (req, reply) => {
  const sid = randomBytes(32).toString('base64url');
  await sessionsRepo.create({ idHash: sha256(sid), userId, casTicket, expiresAt: new Date(Date.now() + TTL_MS) });
  reply.setCookie('__Host-sid', sid, { path: '/', httpOnly: true, secure: true, sameSite: 'lax', maxAge: TTL_MS / 1000 });
  return { ok: true as const };
});
```

---

## 6. Validation: Zod 4 vs TypeBox

**Decision.** Use **Zod 4** (`zod@^4.6`) as the single schema source in `packages/shared`, with **`fastify-type-provider-zod@^7`** on the API.

- Forms run the same schemas with `safeParse` and `z.flattenError` on submit. Use native `<form>` plus the Constraint Validation attributes. A form library is not needed yet.
- Spanish messages come from `z.config(z.locales.es())`, verified to output "Demasiado pequeño: se esperaba que texto tuviera >=1 caracteres".
- `z.toJSONSchema()` is available for OpenAPI docs if needed.

**Evidence.**

- **fastify-type-provider-zod.** 7.0.0 (MIT, 2026-06-24) has peers `fastify ^5.5.0` and `zod >=4.1.5`. Its README says v7 requires Zod ≥4.2 (it uses `.encode/.decode`) and that serializers use `z.output<T>`. The [verified] Fastify spike used `validatorCompiler` and `serializerCompiler` with a plain `import { z } from 'zod'`.
- **Discrepancy.** The README imports from `'zod/v4'`. In zod 4.x the root `'zod'` export *is* v4, so the subpath is unnecessary.
- **TypeBox.** `typebox` 1.3.34 (MIT, 2026-09-18) with `@fastify/type-provider-typebox` 6.1.0 (peer `typebox ^1.0.13`). Note the package moved from `@sinclair/typebox` to `typebox` at 1.0. TypeBox is faster on the server and is JSON Schema-native. But browser form validation would need its `Value`/compiler module and custom Spanish messages. LexiCán's request volume does not need the extra speed.
- **Advisories.** zod: only GHSA-m95q (≤3.22.2, 2023). fastify-type-provider-zod: none.
- **Sources.** https://zod.dev (Context7 `/colinhacks/zod`: error-customization, error-formatting) · https://github.com/turkerdev/fastify-type-provider-zod · https://github.com/sinclairzx81/typebox
- **Discrepancy.** Context7 shows `import { en } from "zod/locales"` and says locales come from `zod/v4/core`. `z.locales.es()` from the root works (verified) and is simpler.

```ts
// packages/shared/src/entry.schema.ts – shared by React and Fastify [verified]
import { z } from 'zod';
export const CreateEntry = z.object({ headword: z.string().trim().min(1).max(120), partOfSpeech: z.string().min(1).optional() });
// React: const r = CreateEntry.safeParse(Object.fromEntries(new FormData(form))); if (!r.success) show(z.flattenError(r.error).fieldErrors);
// Fastify: app.withTypeProvider<ZodTypeProvider>().post('/api/entries', { schema: { body: CreateEntry } }, h)
```

Authorization stays out of schemas, as §42 requires.

---

## 7. Uploads

**Decision.**

- **`@fastify/multipart` 10.1.2** (MIT). Set explicit `limits: { fileSize: 5 MiB, files: 1, fields: 10, parts: 11 }`. `toBuffer()` past the limit returns **413** (verified). Historical advisories: GHSA-27c6 (≤8.3.0) and GHSA-hpp2 (<6.0.1), both fixed.
- **Content sniffing with `file-type` 22.1.1** (MIT, ESM-only, `engines.node >=22`, pure JS). The allowlist is `image/png`, `image/jpeg`, `image/webp`, `audio/mpeg`, `audio/ogg` (extend after the media audit). Verified results:
  - a PNG uploaded as `evil.php` / `application/x-php` is detected as `image/png`;
  - `<script>` bytes sent with `Content-Type: image/png` get **415**.
  - Advisories: GHSA-j47w (zip bomb, fixed 21.3.2) and GHSA-5v7r (fixed 21.3.1). Stay on ≥22.
- **Images without native dependencies.** Use **`image-size` 2.0.4** (MIT, pure JS) to read dimensions, and reject `width*height > 40 MP`, which guards against decompression bombs. It throws on garbage (verified: `unsupported file type`).
  - Skip `sharp` 0.35.5 (Apache-2.0, but prebuilt libvips binaries are LGPL and native, which complicates Docker and CI). Add it later only if re-encoding or thumbnails become a requirement.
  - No SVG uploads.
  - The stored name is `crypto.randomUUID()` + the extension from `file-type`, placed under a non-public directory. Serve files via a route with `Content-Type` from the DB, `X-Content-Type-Options: nosniff` and `Content-Disposition: inline; filename="…"`.
- **Demo uploads.** Store them in PGlite as `bytea` in a demo-only `demo_media` table behind the same `MediaStorage` interface. Cap them at 2 MiB per file and 20 MiB total.
  - Reset wipes them together with the database in one `deleteDatabase`, and they stay transactional with entry rows.
  - The client-side checks are the same `file-type` sniff (it runs in browsers) and `createImageBitmap()` for corrupt-image detection.
  - Caveat: Emscripten IDBFS mirrors the data directory in memory, so large blobs cost RAM. That is why the caps exist.
  - Drizzle 0.45 has no `bytea` builder. Use `customType` (verified roundtrip, returns `Uint8Array`):

```ts
import { customType } from 'drizzle-orm/pg-core';
export const bytea = customType<{ data: Uint8Array; driverData: Uint8Array }>({ dataType: () => 'bytea' });
```

```ts
// apps/api/src/media/routes.ts (excerpt) [verified]
await app.register(multipart, { limits: { fileSize: 5 * 1024 * 1024, files: 1, fields: 10, parts: 11 } });
app.post('/api/media', async (req, reply) => {
  const file = await req.file(); if (!file) return reply.code(400).send();
  const buf = await file.toBuffer();                 // 413 past fileSize
  const type = await fileTypeFromBuffer(buf);
  if (!type || !ALLOWED.has(type.mime)) return reply.code(415).send();
  if (type.mime.startsWith('image/')) { const { width = 0, height = 0 } = imageSize(buf); if (width * height > 40e6) return reply.code(422).send(); }
  // MediaStorage.put(randomUUID() + '.' + type.ext, buf)
});
```

- **Sources.** https://github.com/fastify/fastify-multipart · https://github.com/sindresorhus/file-type · https://github.com/image-size/image-size

---

## 8. PDF export

**Decision.** Use **browser print CSS** (`window.print()` on a dedicated `/print` route with `@media print` and `@page`). It needs no library, works the same in the demo and in production, and gives the user "Save as PDF". Add a client library only if a layout requirement appears that print CSS cannot meet (running headers, for example). In that case use `pdfmake` (MIT, 0.3.11, 2026-06-12) loaded by `import()` from the export button, never in the initial bundle.

Rejected:

- **`jspdf` 4.2.1:** 8 high or critical advisories in Feb–Mar 2026, including PDF object and JS injection (GHSA-wfv2, -7x6v, -p5xg…). It also renders HTML badly without html2canvas.
- **pdfmake advisories:** GHSA-wp52 (SSRF, <0.3.6) and GHSA-rj3r. They are server-side URL-embedding issues. Keep it ≥0.3.6 if it is ever adopted.

```css
@media print {
  @page { size: A4; margin: 18mm 15mm; }
  nav, .toolbar, .no-print { display: none !important; }
  .entry { break-inside: avoid; }
  h2.letter { break-before: page; }
}
```

- **Sources.** https://developer.mozilla.org/docs/Web/CSS/@page · GHSA DB (jspdf, pdfmake).

---

## 9. React routing and server state

**Decision.** Use **`react-router` 8.x in data mode**. Call `createHashRouter` in the demo build and `createBrowserRouter` in production, chosen at build time by `import.meta.env.MODE`. Both are exported from `react-router`; `RouterProvider` comes from `react-router/dom`. Both were verified in 8.4.0. Loaders and actions call an `ApiClient` interface: the `fetch` implementation in production, the in-browser services plus PGlite in the demo. Actions revalidate loaders automatically.

**No TanStack Query.** Router loaders, actions, `useFetcher` and revalidation cover this app's server-state needs (lists, detail, mutate, refresh). This answers the §106 question "¿librería de server-state?" with *no* for now. Revisit if background polling or cross-route caching becomes painful.

**Why not TanStack Router.** `@tanstack/react-router` is 1.170.41 (MIT, active). It is more type-safe but adds file-route codegen. In addition, `@tanstack/*` suffered a **malware supply-chain incident in May 2026** (GHSA-g7cv-rxg3-hmpx, critical, `@tanstack/react-router 1.166.12`). It was fixed, but it argues for fewer dependencies.

**Version note.** The brief says "react-router 7", but **v8 is latest since 2026-06-17**.

- v8 is ESM-only, removes `react-router-dom`, makes the `future.v8_*` flags (including middleware) the default, and requires Node ≥22.22, React ≥19.2.7 and Vite ≥7. Data mode is unaffected.
- v7 advisories GHSA-chx6 (DoS) and GHSA-wrjc (open redirect) are fixed in 7.18.x. v8 is unaffected.
- **Discrepancy.** Context7 `/remix-run/react-router` only indexes up to 7.9.4. `/websites/reactrouter` matches the current API pages.

- **Sources.** https://reactrouter.com/start/modes · https://reactrouter.com/api/data-routers/createHashRouter · https://remix.run/blog/react-router-v8 · https://reactrouter.com/upgrading/v7

```tsx
// apps/web/src/router.tsx
import { createBrowserRouter, createHashRouter } from 'react-router';
import { RouterProvider } from 'react-router/dom';
const create = import.meta.env.MODE === 'demo' ? createHashRouter : createBrowserRouter;
export const router = create(routes); // demo URLs: https://ateeducacion.github.io/lexican/#/diccionarios/1
// root: <RouterProvider router={router} />
```

The hash router removes the need for a GitHub Pages `404.html` SPA hack. Keep `base: '/lexican/'` in Vite so assets resolve.

---

## 10. typescript-eslint vs TypeScript 7

**Confirmed.** `typescript-eslint@8.71.0` (2026-09-28) has peer `typescript: ">=4.8.4 <6.1.0"` and peer `eslint ^8.57 || ^9 || ^10`.

- TypeScript 7.0.2 (`latest`, GA 2026-07-08) is the Go-native compiler. Its npm package only exposes `./unstable/*` entry points: `require('typescript').createProgram` is `undefined` (verified).
- typescript-eslint support is tracked in PR #12803, "projectService.EXPERIMENTAL_backend for TypeScript 7.1 native parser support over IPC", which is still **open** (updated 2026-10-04). It targets TS **7.1**, which has not shipped (`next` is `7.1.0-dev.20261004`).

**Decision.** Pin `"typescript": "~6.0.3"` and `"typescript-eslint": "^8.71.0"` in the root `package.json`. Add a Dependabot ignore rule for `typescript >=6.1`. Revisit when typescript-eslint publishes TS 7.1 support. Vite and Vitest strip types themselves and do not need TS 7.

- **Sources.** `npm view typescript-eslint peerDependencies` · https://github.com/typescript-eslint/typescript-eslint/pull/12803 · https://mergify.com/blog/native-typescript-compiler-faster-typecheck

---

## 11. OASIS DMLex 1.0 (for docs/DMLEX-MAPPING.md)

**Status.** **OASIS Standard, 29 April 2025.** It was Committee Specification 01 in Nov 2024, and the `os/` directory on docs.oasis-open.org is dated 2025-04-29.

- **Spec:** https://docs.oasis-open.org/lexidma/dmlex/v1.0/os/dmlex-v1.0-os.html (PDF alongside)
- **JSON schemas (JSON Schema 2020-12, `$id http://docs.oasis-open.org/lexidma/ns/dmlex-1.0`):**
  - https://docs.oasis-open.org/lexidma/dmlex/v1.0/os/schemas/JSON/dmlex_no-crosslingual.schema.json, the monolingual variant with `required: ["langCode"]`, which is the one to use;
  - `…/dmlex.schema.json` for the crosslingual module, which additionally requires `translationLanguages`.
  - XML and RDF serializations live in sibling `schemas/XML/` and `schemas/RDF/` folders.
- **Exchange unit.** A JSON file holds one `lexicographicResource` *or* one `entry`, or JSON Lines of one of them.

**Modules.** Core (mandatory), Crosslingual, Controlled Values, Linking, Annotation, Etymology.

**Core objects and JSON property names**, taken from the official schema file:

| Object | JSON properties (required in **bold**) |
|---|---|
| lexicographicResource | title, uri, **langCode**, entries[], *Controlled Values module:* partOfSpeechTags[], labelTags[], labelTypeTags[], definitionTypeTags[], inflectedFormTags[], sourceIdentityTags[], transcriptionSchemeTags[]; *Linking:* relations[], relationTypes[] |
| entry | **headword**, id, homographNumber (string), partsOfSpeech[] (strings), labels[] (strings), pronunciations[], inflectedForms[], senses[], placeholderMarkers[], etymologies[] |
| sense | id, indicator, labels[], definitions[], examples[], *crosslingual:* headwordExplanations[], headwordTranslations[] |
| definition | **text**, definitionType, headwordMarkers[], collocateMarkers[] |
| example | **text**, sourceIdentity, sourceElaboration, soundFile, labels[], headwordMarkers[], collocateMarkers[] |
| pronunciation | soundFile, transcriptions[] ({**text**, scheme}), labels[] |
| inflectedForm | **text**, tag, labels[], pronunciations[] |
| partOfSpeechTag / labelTag | **tag**, description, for, sameAs[] (labelTag also has typeTag) |
| relation | **type**, description, members[] |

`listingOrder` in the abstract model is implicit in JSON array order.

Example below, **validated with Ajv 2020 against the official `dmlex_no-crosslingual.schema.json`**:

```json
{
  "title": "Diccionario de aula 3ºB",
  "langCode": "es",
  "partOfSpeechTags": [{ "tag": "n", "description": "nombre" }],
  "labelTags": [{ "tag": "coloq", "description": "coloquial" }],
  "entries": [{
    "id": "gofio-1", "headword": "gofio", "partsOfSpeech": ["n"],
    "pronunciations": [{ "soundFile": "media/gofio.mp3" }],
    "senses": [{
      "id": "gofio-1-s1", "indicator": "alimento", "labels": ["coloq"],
      "definitions": [{ "text": "Harina de cereales tostados, típica de Canarias." }],
      "examples": [{ "text": "Desayuno gofio con leche." }]
    }]
  }]
}
```

**Recommendation for §106 ("¿export DMLex ya?").** Yes, as a JSON export for a dictionary. It is a pure mapping function plus an Ajv validation of the vendored schema in a unit test. Import can wait.

**Discrepancy.** A WebFetch summary of the spec produced singular keys (`partsOfSpeech` was right, but `definition` and `example` came back as objects). The official JSON schema uses plural arrays (`definitions`, `examples`). The schema is authoritative.

---

## 12. Lexonomy (study, do not clone)

- **Guide.** https://guide.lexonomy.eu/ (sections: Dictionary, Structure/NVH, Edit, Formatting, Publish). The tool is at https://lexonomy.eu/ and is run by Lexical Computing.
- **Old open-source code.** https://github.com/elexis-eu/lexonomy (MIT, last push 2024-01-09). "New Lexonomy" is free for academic use up to 5,000 entries; other uses need contact (per the guide). It is a product to learn from, not a dependency.

**Key ideas worth adopting.**

1. **Per-dictionary schema.** Each dictionary defines its entry structure. New Lexonomy stores entries as **NVH** (name-value hierarchy: `name: value` lines, children indented two spaces) and imports XML by converting it to NVH. For LexiCán: keep a fixed relational core (entry, sense, definition, example, media) plus per-dictionary *configuration* of which optional fields are shown (§51), not a free schema.
2. **Per-dictionary user permissions** as independent flags: `canEdit`, `canConfig`, `canDownload`, `canUpload` (from `website/riot/dict-config-users.riot`). These map naturally to LexiCán `dictionary_members.role` (owner, editor, reviewer, viewer) plus capability checks.
3. **Publishing** has two routes: a built-in public viewer ("DICTIONARY > CONFIGURE > PUBLISH", mark Public, or a dedicated access link) or an external export (NVH/XML). **A published dictionary remains editable** by users with edit rights. This supports §50: keep the public viewer separate from the editor, and publish as a state rather than a copy.
4. Formatting is configured separately from structure (element styles in the editor do not affect published styling).

- **Sources.** https://guide.lexonomy.eu/publish/ · https://guide.lexonomy.eu/structure/understand-nvh/ · https://guide.lexonomy.eu/new-lexonomy/ · https://www.lexiconista.com/pdf/elex2017.pdf

---

## Context7 vs official docs: discrepancy log

| Topic | Context7 | Official / verified | Action |
|---|---|---|---|
| PGlite multi-tab `?worker` snippet | broken braces, no `worker.format` | pglite.dev says to set `worker.format: 'es'` | follow pglite.dev |
| PGlite size | — | Drizzle docs "2.6 MB gzipped"; measured ~5.5 MB gzip (wasm + data) | budget 3.5–5.5 MB |
| Drizzle PGlite migrator | no result in `/drizzle-team/drizzle-orm-docs` | source: `readMigrationFiles` uses `node:fs` | custom `migrateBundled` |
| Drizzle version | `/drizzle-team/drizzle-orm` versions up to kit 0.31.5 | docs install `drizzle-orm@rc` (v1); npm latest 0.45.3 | pin 0.45.x; ADR for the v1 migration-layout change |
| React Router | `/remix-run/react-router` up to 7.9.4 | npm latest 8.4.0 (2026-06-17 GA) | use v8; `/websites/reactrouter` is current |
| fastify-type-provider-zod | README imports `zod/v4` | `import { z } from 'zod'` works with 4.6.5 (verified) | use root import |
| Zod locales | `zod/locales` / `zod/v4/core` | `z.locales.es()` works (verified) | use `z.locales` |
| DMLex JSON | (WebFetch summary) singular keys | official schema: plural arrays | vendor the official schema |

## Scratch verification artefacts

These are not in the repo. Scratchpad: `…/scratchpad/t/`.

- `src/repo.contract.test.ts`: 8/8 green, PGlite plus Postgres 18 via Testcontainers.
- `src/cas.test.ts`: 4/4 green.
- `src/server.test.ts`: Fastify session, Origin, Zod, helmet, rate-limit and multipart/file-type, green.
- `src/bytea.test.ts`: green.
- `pw.mjs`: Playwright check of `idb://` persistence, reload, reset and the IndexedDB name.
- `vite build --mode demo` / `vite build`: output sizes listed in §1.
