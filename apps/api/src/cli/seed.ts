import { readFileSync } from 'node:fs';
import { seedDemo } from '@lexican/app';
import { seedVocabulary } from '@lexican/db';
import { loadConfig } from '../config.ts';
import { openDb } from '../db.ts';
import { fsMediaStorage } from '../media-storage.ts';

/** `db:seed` loads product vocabularies; `db:seed -- --demo` also loads the fictitious demo dataset (local dev only). */
const demo = process.argv.includes('--demo');
const config = loadConfig();
const { db, pool } = openDb(config.databaseUrl);
try {
  await seedVocabulary(db);
  if (demo) {
    if (config.production) throw new Error('--demo is not allowed with NODE_ENV=production.');
    const dir = new URL('../../../web/public/demo/', import.meta.url);
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
