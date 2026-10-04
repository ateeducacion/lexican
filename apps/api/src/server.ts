import { mkdir } from 'node:fs/promises';
import { buildApp } from './app.ts';
import { loadConfig } from './config.ts';
import { openDb, runMigrations } from './db.ts';

/** Production entry point, run by Bun: `bun apps/api/dist/server.js` (image) or `bun apps/api/src/server.ts`. */
const config = loadConfig();
const { db, pool } = openDb(config.databaseUrl);
if (config.migrateOnStart) await runMigrations(pool);
await mkdir(config.mediaDir, { recursive: true, mode: 0o750 });

const { app, services, log } = buildApp({ config, db });
if (!config.cas && !config.devLogin)
  log.warn({}, 'No login provider enabled (set CAS_URL or AUTH_DEV_LOGIN).');
// Before serving, so no upload is in flight: files left by a crash between write and insert.
const swept = await services.media.sweepOrphans();
if (swept) log.warn({ swept }, 'removed orphaned media files');

const server = Bun.serve({
  port: config.port,
  hostname: config.host,
  // Bun rejects larger bodies before Hono sees them; the API enforces the exact limits while streaming.
  maxRequestBodySize: config.maxUploadBytes + 1024 * 1024,
  fetch: (req, srv) => app.fetch(req, { remoteAddress: srv.requestIP(req)?.address }),
});
log.info({ port: server.port, appEnv: config.appEnv, bun: Bun.version }, 'listening');

let closing = false;
for (const signal of ['SIGTERM', 'SIGINT'] as const) {
  process.once(signal, async () => {
    if (closing) return;
    closing = true;
    log.info({ signal }, 'shutting down');
    const timer = setTimeout(() => process.exit(1), 10_000);
    try {
      await server.stop(); // stop accepting, let in-flight requests finish
      await pool.end();
    } finally {
      clearTimeout(timer);
      process.exit(0);
    }
  });
}
