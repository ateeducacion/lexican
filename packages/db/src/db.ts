import type { PgAsyncDatabase, PgQueryResultHKT } from 'drizzle-orm/pg-core';

/** Drizzle handle accepted everywhere: PGlite (demo, tests) and node-postgres (production). */
export type Db = PgAsyncDatabase<PgQueryResultHKT>;
