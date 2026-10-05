import { and, eq, sql } from 'drizzle-orm';
import { boolean, jsonb, pgTable, text, timestamp, uuid } from 'drizzle-orm/pg-core';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { queryRows } from './db.ts';
import { globalRole } from './schema.ts';
import { openPglite, openPostgres, type TestDb } from './testing.ts';

const probe = pgTable('driver_probe', {
  id: uuid().primaryKey().defaultRandom(),
  key: text().notNull(),
  active: boolean().notNull().default(true),
  payload: jsonb().$type<unknown>(),
  tags: text().array(),
  at: timestamp({ withTimezone: true }),
  role: globalRole(),
});
const server = process.env.TEST_DATABASE_URL;
for (const driver of ['pglite', 'postgres'] as const) {
  describe.skipIf(driver === 'postgres' && !server)(`driver contract on ${driver}`, () => {
    let t: TestDb;
    beforeAll(async () => {
      t = driver === 'pglite' ? await openPglite() : await openPostgres(server!);
      await t.db.execute(sql`create table driver_probe (
        id uuid primary key default gen_random_uuid(), key text not null,
        active boolean not null default true, payload jsonb, tags text[], at timestamptz, role global_role
      )`);
      await t.db.execute(
        sql`create unique index driver_probe_key on driver_probe(key) where active`,
      );
    });
    afterAll(() => t.close());

    it('round-trips UUID, enum, timestamptz, arrays and nested JSONB; containment stays structural', async () => {
      const payload = { senses: [{ definition: 'Añil "azul" \\ árbol' }], nested: { value: null } };
      const at = new Date('2026-10-05T12:34:56.789Z');
      const tags = ['canario', 'a,b', '"quoted"', '\\', ''];
      const [row] = await t.db
        .insert(probe)
        .values({ key: 'round-trip', payload, at, tags, role: 'teacher' })
        .returning();
      expect(row!.id).toMatch(/^[0-9a-f-]{36}$/);
      expect(row).toMatchObject({ payload, at, tags, role: 'teacher' });
      expect((await t.db.select().from(probe).where(eq(probe.id, row!.id)))[0]).toEqual(row);
      const match = await t.db
        .select({ id: probe.id })
        .from(probe)
        .where(
          and(
            eq(probe.id, row!.id),
            sql`${probe.payload} -> 'senses' @> ${JSON.stringify(payload.senses)}::text::jsonb`,
          ),
        );
      expect(match).toEqual([{ id: row!.id }]);
      expect(
        await queryRows<{ n: number }>(t.db, sql`select count(*)::int as n from driver_probe`),
      ).toEqual([{ n: 1 }]);
    });

    it('round-trips JSONB primitives, strings and arrays without changing their types', async () => {
      for (const [i, payload] of [1, true, 'a "quoted" string', ['a', 'b']].entries()) {
        const [row] = await t.db
          .insert(probe)
          .values({ key: `json-${i}`, payload })
          .returning();
        expect(row!.payload).toEqual(payload);
        expect((await t.db.select().from(probe).where(eq(probe.id, row!.id)))[0]!.payload).toEqual(
          payload,
        );
      }
    });

    it('enforces partial uniqueness and rolls a failed transaction back before reusing its connection', async () => {
      await t.db.insert(probe).values({ key: 'partial' });
      await expect(
        t.db.transaction(async (tx) => {
          await tx.insert(probe).values({ key: 'rolled-back' });
          await tx.insert(probe).values({ key: 'partial' });
        }),
      ).rejects.toSatisfy(
        (error: { cause?: { code?: string; errno?: string } }) =>
          error.cause?.code === '23505' || error.cause?.errno === '23505',
      );
      expect(await t.db.select().from(probe).where(eq(probe.key, 'rolled-back'))).toEqual([]);
      await t.db.insert(probe).values({ key: 'partial', active: false });
      expect(await t.db.select().from(probe).where(eq(probe.key, 'partial'))).toHaveLength(2);
      await expect(
        t.db.transaction(async (tx) => {
          await tx.insert(probe).values({ key: 'explicit-rollback' });
          tx.rollback();
        }),
      ).rejects.toThrow();
      expect(await t.db.select().from(probe).where(eq(probe.key, 'explicit-rollback'))).toEqual([]);
    });

    it('commits 100 concurrent transactions without leaking failed work or mixing results', async () => {
      const rows = await Promise.all(
        Array.from({ length: 100 }, (_, i) =>
          t.db.transaction(async (tx) => {
            const [row] = await tx
              .insert(probe)
              .values({ key: `concurrent-${i}`, tags: [`${i}`] })
              .returning();
            return (await tx.select().from(probe).where(eq(probe.id, row!.id)))[0]!;
          }),
        ),
      );
      expect(new Set(rows.map((row) => row.id)).size).toBe(100);
      rows.forEach((row, i) =>
        expect(row).toMatchObject({ key: `concurrent-${i}`, tags: [`${i}`] }),
      );
    });
  });
}
