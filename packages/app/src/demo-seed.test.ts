import { expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { openPglite } from '@lexican/db/testing';
import { createServices, seedDemo } from './index.ts';

it('seeds a coherent demo once (idempotent) that exercises every workflow state', async () => {
  const t = await openPglite();
  const blobs = new Map<string, Uint8Array>();
  const deps = { db: t.db, clock: () => new Date(), media: { put: async (k: string, b: Uint8Array) => void blobs.set(k, b), get: async (k: string) => blobs.get(k) ?? null }, mediaUrl: (id: string) => id };
  const img = (n: string) => ({ name: n, bytes: new Uint8Array(readFileSync(new URL(`../../../apps/web/public/demo/${n}`, import.meta.url))) });
  await seedDemo(deps, [img('guagua.png'), img('gofio.png')]);
  await seedDemo(deps);
  const svc = createServices(deps);
  const teacher = await svc.call('login', null, { email: 'profesor@ejemplo.com', password: 'profesor' });
  const actor = { userId: teacher.id, globalRole: teacher.globalRole };
  const [classroom] = await svc.call('myClassrooms', actor, {});
  expect(classroom?.pendingCount).toBe(2);
  const statuses = (await svc.call('listSubmissions', actor, { classroomId: classroom!.id })).map((s) => s.status).sort();
  expect(statuses).toEqual(['pending', 'pending', 'published', 'published', 'published', 'rejected']);
  expect((await svc.call('listEntries', actor, { dictionaryId: classroom!.id })).items.map((i) => i.headword)).toEqual(['cholas', 'gofio', 'guagua']);
  expect(blobs.size).toBe(2);
  await t.close();
});
