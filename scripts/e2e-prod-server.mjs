// Production E2E server (playwright project `prod-chromium`): throwaway Postgres database, built web + API,
// migrations + demo seed, API on :3100 serving the SPA. The database and media dir are removed on exit.
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import pg from 'pg';

const base = process.env.E2E_DATABASE_URL;
if (!base) throw new Error('E2E_DATABASE_URL is required');
const root = new URL('..', import.meta.url).pathname;
const PORT = 3100;

const run = (cmd, args, env = process.env) => {
  const r = spawnSync(cmd, args, { cwd: root, env, stdio: 'inherit' });
  if (r.status !== 0) throw new Error(`${cmd} ${args.join(' ')} failed (${r.status})`);
};

if (!existsSync(join(root, 'apps/web/dist/index.html')))
  run('npm', ['run', 'build', '-w', '@lexican/web']);
if (!existsSync(join(root, 'apps/api/dist/server.js')))
  run('npm', ['run', 'build', '-w', '@lexican/api']);

const name = `lexican_e2e_${Date.now().toString(36)}`;
const admin = new pg.Client({ connectionString: base });
await admin.connect();
// Playwright stops web servers with SIGKILL unless `gracefulShutdown` is configured, which skips cleanup.
// Port 3100 is fixed, so only one run can be live: sweep databases left by earlier runs.
const stale = await admin.query(
  `select datname from pg_database where datname like 'lexican\\_e2e\\_%'`,
);
for (const { datname } of stale.rows)
  await admin.query(`drop database if exists "${datname}" with (force)`);
await admin.query(`create database ${name}`);
const url = new URL(base);
url.pathname = `/${name}`;
const mediaDir = mkdtempSync(join(tmpdir(), 'lexican-e2e-media-'));

let cleaned = false;
async function cleanup() {
  if (cleaned) return;
  cleaned = true;
  rmSync(mediaDir, { recursive: true, force: true });
  await admin.query(`drop database if exists ${name} with (force)`).catch((e) => console.error(e));
  await admin.end().catch(() => {});
}

const env = {
  ...process.env,
  NODE_ENV: 'test',
  DATABASE_URL: url.toString(),
  PORT: String(PORT),
  HOST: '127.0.0.1',
  PUBLIC_URL: `http://localhost:${PORT}`,
  WEB_DIST: join(root, 'apps/web/dist'),
  MEDIA_DIR: mediaDir,
  AUTH_DEV_LOGIN: 'true',
  // Every E2E test logs in; the default (10/min per IP) would throttle the suite.
  LOGIN_RATE_LIMIT: '1000',
  LOG_LEVEL: process.env.LOG_LEVEL ?? 'warn',
};

let server;
try {
  run('node', ['apps/api/dist/cli/migrate.js'], env);
  run('node', ['apps/api/dist/cli/seed.js', '--demo'], env);
  server = spawn('node', ['apps/api/dist/server.js'], { cwd: root, env, stdio: 'inherit' });
} catch (e) {
  await cleanup();
  throw e;
}

for (const sig of ['SIGTERM', 'SIGINT']) {
  process.once(sig, () => server.kill('SIGTERM'));
}
server.once('exit', async (code) => {
  await cleanup();
  process.exit(code ?? 0);
});
