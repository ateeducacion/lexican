import { readFileSync } from 'node:fs';
import { seedDemo } from '@lexican/app';
import { seedVocabulary } from '@lexican/db';
import { loadConfig } from '../config.ts';
import { openDb } from '../db.ts';
import { fsMediaStorage } from '../media-storage.ts';

/**
 * `db:seed` loads product vocabularies; `db:seed -- --demo` also loads the fictitious demo dataset, only with
 * APP_ENV=local or test (refused in production).
 */
const demo = process.argv.includes('--demo');
// Checked before anything else: APP_ENV defaults to production, so demo data needs an explicit local/test profile.
if (demo && (process.env.APP_ENV ?? 'production') === 'production')
  throw new Error('--demo is not allowed with APP_ENV=production.');
const config = loadConfig();
const { db, pool } = openDb(config.databaseUrl);
try {
  await seedVocabulary(db);
  if (demo) {
    const dir = process.env.DEMO_ASSETS_DIR
      ? new URL(`file://${process.env.DEMO_ASSETS_DIR.replace(/\/?$/, '/')}`)
      : new URL('../../../web/public/demo/', import.meta.url);
    const images = ['guagua.png', 'gofio.png'].map((name) => ({
      name,
      bytes: new Uint8Array(readFileSync(new URL(name, dir))),
    }));
    await seedDemo(
      {
        db,
        clock: () => new Date(),
        media: fsMediaStorage(config.mediaDir),
        mediaUrl: (id) => `/media/${id}`,
        schoolYear: config.schoolYear,
      },
      images,
    );
  }
  console.warn(demo ? 'Vocabularies and demo data seeded.' : 'Vocabularies seeded.');
} finally {
  await pool.end();
}
