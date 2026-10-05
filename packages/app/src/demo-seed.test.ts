import { readFileSync } from 'node:fs';
import { openPglite } from '@lexican/db/testing';
import { expect, it } from 'vitest';
import { createServices, memoryMediaStorage, seedDemo, testDirectory } from './index.ts';

it('seeds a coherent demo once (idempotent) that exercises every workflow state', async () => {
  const t = await openPglite();
  const media = memoryMediaStorage();
  const blobs = media.blobs;
  const deps = { db: t.db, clock: () => new Date(), media, mediaUrl: (id: string) => id };
  const img = (n: string) => ({
    name: n,
    bytes: new Uint8Array(
      readFileSync(new URL(`../../../apps/web/public/demo/${n}`, import.meta.url)),
    ),
  });
  await seedDemo(deps, [img('guagua.png'), img('gofio.png')]);
  await seedDemo(deps);
  const svc = createServices(deps);
  const teacher = await svc.call('login', null, {
    email: 'profesor@ejemplo.com',
    password: 'profesor',
  });
  const actor = { userId: teacher.id, globalRole: teacher.globalRole };
  const [classroom] = await svc.call('myClassrooms', actor, {});
  expect(classroom?.pendingCount).toBe(2);
  const statuses = (await svc.call('listSubmissions', actor, { classroomId: classroom!.id }))
    .map((s) => s.status)
    .sort();
  expect(statuses).toEqual([
    'pending',
    'pending',
    'published',
    'published',
    'published',
    'rejected',
  ]);
  expect(
    (await svc.call('listEntries', actor, { dictionaryId: classroom!.id })).items.map(
      (i) => i.headword,
    ),
  ).toEqual(['cholas', 'gofio', 'guagua']);
  expect(blobs.size).toBe(2);
  // The public test CAS accounts sign in as their personas (issuer `cas_test`); others are refused.
  const dir = testDirectory();
  expect((await svc.auth.signInInstitutional(await dir.lookup('alice'), 'cas_test')).id).toBe(
    teacher.id,
  );
  expect((await svc.auth.signInInstitutional(await dir.lookup('bob'), 'cas_test')).email).toBe(
    'alumno1@ejemplo.com',
  );
  await expect(dir.lookup('mallory')).rejects.toMatchObject({ code: 'forbidden' });
  // The same subject under the institutional issuer is a different account.
  expect((await svc.auth.signInInstitutional(await dir.lookup('alice'), 'cas')).id).not.toBe(
    teacher.id,
  );
  expect(await svc.media.sweepOrphans()).toBe(0);
  await media.put('00000000-0000-4000-8000-000000000000.png', new Uint8Array([1]), 'image/png');
  expect(await svc.media.sweepOrphans()).toBe(1);
  expect(blobs.size).toBe(2);
  await t.close();
});

it('leaves nothing behind when seeding is interrupted, so the next start seeds again', async () => {
  const t = await openPglite();
  const media = memoryMediaStorage();
  const deps = { db: t.db, clock: () => new Date(), media, mediaUrl: (id: string) => id };
  const image = {
    name: 'guagua.png',
    bytes: new Uint8Array(
      readFileSync(new URL('../../../apps/web/public/demo/guagua.png', import.meta.url)),
    ),
  };
  // Fails after the accounts exist, like a mobile tab killed in the background during the first start.
  const broken = { ...deps, media: { ...media, put: () => Promise.reject(new Error('killed')) } };
  await expect(seedDemo(broken, [image])).rejects.toThrow();
  await seedDemo(deps);
  const user = await createServices(deps).call('login', null, {
    email: 'profesor@ejemplo.com',
    password: 'profesor',
  });
  expect(user.email).toBe('profesor@ejemplo.com');
  await t.close();
});
