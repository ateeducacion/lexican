import { mkdir } from 'node:fs/promises';
import { buildApp } from './app.ts';
import { loadConfig } from './config.ts';
import { openDb, runMigrations } from './db.ts';

const config = loadConfig();
const { db, pool } = openDb(config.databaseUrl);
if (config.migrateOnStart) await runMigrations(pool);
await mkdir(config.mediaDir, { recursive: true, mode: 0o750 });

const app = await buildApp({ config, db });
if (!config.cas && !config.devLogin) app.log.warn('No login provider enabled (set CAS_BASE_URL or AUTH_DEV_LOGIN).');

let closing = false;
for (const signal of ['SIGTERM', 'SIGINT'] as const) {
  process.once(signal, async () => {
    if (closing) return;
    closing = true;
    app.log.info({ signal }, 'shutting down');
    const timer = setTimeout(() => process.exit(1), 10_000).unref();
    try {
      await app.close();
      await pool.end();
    } finally {
      clearTimeout(timer);
      process.exit(0);
    }
  });
}

await app.listen({ port: config.port, host: config.host });
