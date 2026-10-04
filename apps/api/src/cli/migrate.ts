import { openDb, runMigrations } from '../db.ts';

const url = process.env.DATABASE_URL;
if (!url) throw new Error('DATABASE_URL is required.');
const { pool } = openDb(url);
try {
  await runMigrations(pool);
  console.warn('Migrations applied.');
} finally {
  await pool.end();
}
