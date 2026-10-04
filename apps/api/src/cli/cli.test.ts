import { mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { emptyDatabase } from '../testing/pg.ts';

/** The `db:migrate` and `db:seed` scripts run as modules: each import executes the CLI once. */
const server = process.env.TEST_DATABASE_URL;
const journal = JSON.parse(
  readFileSync(
    new URL('../../../../packages/db/migrations/meta/_journal.json', import.meta.url),
    'utf8',
  ),
) as { entries: unknown[] };

const run = async (
  script: 'migrate' | 'seed',
  env: Record<string, string | undefined>,
  args: string[] = [],
) => {
  vi.resetModules();
  for (const [k, v] of Object.entries(env)) vi.stubEnv(k, v);
  const argv = process.argv;
  process.argv = [...argv, ...args];
  try {
    await (script === 'migrate' ? import('./migrate.ts') : import('./seed.ts'));
  } finally {
    process.argv = argv;
  }
};

describe('migrate CLI without a database URL', () => {
  afterEach(() => vi.unstubAllEnvs());
  it('fails fast when DATABASE_URL is missing', async () => {
    await expect(run('migrate', { DATABASE_URL: undefined })).rejects.toThrow(
      'DATABASE_URL is required.',
    );
  });
});

describe.skipIf(!server)('database CLIs on PostgreSQL', () => {
  let db: Awaited<ReturnType<typeof emptyDatabase>>;
  let mediaDir: string;
  const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
  const count = async (table: string) =>
    Number((await db.query(`select count(*)::int as n from ${table}`)).rows[0].n);

  beforeAll(async () => {
    db = await emptyDatabase(server!);
    mediaDir = mkdtempSync(join(tmpdir(), 'lexican-cli-'));
  });
  afterAll(async () => {
    await db?.drop();
    rmSync(mediaDir, { recursive: true, force: true });
    warn.mockRestore();
  });
  afterEach(() => vi.unstubAllEnvs());

  it('migrate applies every journal entry once, idempotently', async () => {
    await run('migrate', { DATABASE_URL: db.url, MIGRATIONS_DIR: join(mediaDir, 'missing') });
    expect(warn).toHaveBeenLastCalledWith('Migrations applied.');
    expect(await count('drizzle.__drizzle_migrations')).toBe(journal.entries.length);
    await run('migrate', {
      DATABASE_URL: db.url,
      MIGRATIONS_DIR: new URL('../../../../packages/db/migrations', import.meta.url).pathname,
    });
    expect(await count('drizzle.__drizzle_migrations')).toBe(journal.entries.length);
  });

  const env = () => ({
    DATABASE_URL: db.url,
    PUBLIC_URL: 'http://localhost:3999',
    MEDIA_DIR: mediaDir,
    NODE_ENV: 'test',
  });

  it('seed loads vocabularies only, idempotently', async () => {
    await run('seed', env());
    await run('seed', env());
    expect(warn).toHaveBeenLastCalledWith('Vocabularies seeded.');
    expect(await count('vocabulary_values')).toBe(360);
    expect(await count('users')).toBe(0);
  });

  it('seed --demo is refused in production', async () => {
    await expect(run('seed', { ...env(), NODE_ENV: 'production' }, ['--demo'])).rejects.toThrow(
      '--demo is not allowed',
    );
    expect(await count('users')).toBe(0);
  });

  it('seed --demo loads the fictitious dataset and stores its images on disk', async () => {
    await run('seed', env(), ['--demo']);
    expect(warn).toHaveBeenLastCalledWith('Vocabularies and demo data seeded.');
    const emails = (await db.query('select email from users order by email')).rows.map(
      (r) => r.email as string,
    );
    expect(emails.length).toBeGreaterThan(0);
    expect(emails.every((e) => e.endsWith('@ejemplo.com'))).toBe(true);
    expect(await count('media_assets')).toBe(2);
  });

  it('refuses an invalid configuration without echoing values', async () => {
    await expect(run('seed', { ...env(), PUBLIC_URL: 'not a url' })).rejects.toThrow(
      /Invalid configuration: PUBLIC_URL/,
    );
  });
});
