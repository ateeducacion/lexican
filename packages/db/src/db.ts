import type { PgDatabase, PgQueryResultHKT } from 'drizzle-orm/pg-core';
import type * as schema from './schema.ts';

/** Drizzle handle accepted everywhere: PGlite (demo, tests) and node-postgres (production). */
export type Db = PgDatabase<PgQueryResultHKT, typeof schema>;
