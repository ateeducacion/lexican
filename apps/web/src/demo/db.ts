import { PGlite } from '@electric-sql/pglite';
import { drizzle } from 'drizzle-orm/pglite';
import { migrateBundled, schema, type Db, type Journal } from '@lexican/db';
import journal from '../../../../packages/db/migrations/meta/_journal.json';

/** Bump the suffix when the demo schema or seed changes incompatibly: browsers start from a fresh database. */
export const DATA_DIR = 'lexican-demo-v1';

const sqlFiles = import.meta.glob<string>('../../../../packages/db/migrations/*.sql', {
  query: '?raw',
  import: 'default',
  eager: true,
});
const sqlByTag = Object.fromEntries(
  Object.entries(sqlFiles).map(([path, sql]) => [path.split('/').pop()!.replace(/\.sql$/, ''), sql]),
);

export async function openDemoDb(): Promise<{ client: PGlite; db: Db }> {
  // Default durability: relaxedDurability lost writes on reload in our tests (analysis/lexican/TECH_RESEARCH.md §1).
  const client = await PGlite.create(`idb://${DATA_DIR}`);
  const db = drizzle({ client, schema }) as unknown as Db;
  await migrateBundled(db, journal as Journal, sqlByTag);
  return { client, db };
}

export async function deleteDemoDb(client: PGlite): Promise<void> {
  await client.close();
  await new Promise<void>((resolve, reject) => {
    const req = indexedDB.deleteDatabase(`/pglite/${DATA_DIR}`);
    req.onsuccess = () => resolve();
    req.onerror = () => reject(req.error);
    req.onblocked = () => resolve();
  });
}
