import { openDb, runMigrations } from '../db.ts';

const url = process.env.DATABASE_URL;
if (!url) throw new Error('DATABASE_URL is required.');
const { client } = openDb(url);
try {
  await runMigrations(client);
  console.warn('Migrations applied.');
} finally {
  await client.close();
}
