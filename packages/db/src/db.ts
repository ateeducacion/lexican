import type { SQL } from 'drizzle-orm';
import type { PgAsyncDatabase, PgQueryResultHKT } from 'drizzle-orm/pg-core';

/** Drizzle handle accepted everywhere: PGlite (demo, tests) and Bun.SQL (production). */
export type Db = PgAsyncDatabase<PgQueryResultHKT>;

/** Native execute results differ: PGlite wraps rows, Bun.SQL returns the array itself. */
export async function queryRows<T>(db: Db, query: SQL): Promise<T[]> {
  const result = await db.execute(query);
  return (Array.isArray(result) ? Array.from(result) : (result as { rows: unknown[] }).rows) as T[];
}

/** Bind JSONB as JSON text: Bun.SQL otherwise infers primitive types or encodes strings twice. */
export const jsonbTextCodec = {
  normalizeParam: JSON.stringify,
  castParam: (parameter: string) => `${parameter}::text::jsonb`,
};
