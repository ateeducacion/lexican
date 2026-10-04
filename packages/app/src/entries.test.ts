import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { auditEvents, users } from '@lexican/db';
import { desc, eq } from 'drizzle-orm';
import { demoSeedVersion, type Actor } from './index.ts';
import { verifyPassword } from './auth.ts';
import { appFixture, PNG, type AppFixture } from './testing/fixture.ts';

describe('entries, media and service entry points', () => {
  let f: AppFixture;
  let teacher: Actor, ana: Actor, ben: Actor;
  let aula: string, anaDict: string;
  const missing = '00000000-0000-4000-8000-000000000000';
  const lastAudit = async () => (await f.db.select().from(auditEvents).orderBy(desc(auditEvents.createdAt)).limit(1))[0];

  beforeAll(async () => {
    f = await appFixture('2025-10-15T10:00:00Z', { maxUploadBytes: 1024, maxUploadBytesPerDay: 4096 });
    teacher = await f.user('teacher', 'profe');
    ana = await f.user('student', 'ana');
    ben = await f.user('student', 'ben');
    const c = await f.classroom(teacher, { title: 'Aula' });
    aula = c.id;
    await f.call('joinClassroom', ana, { code: c.code });
    await f.call('joinClassroom', ben, { code: c.code });
    anaDict = (await f.call('myDictionary', ana, {})).id;
  }, 60_000);
  afterAll(() => f.close());

  it('validates raw input before any rule: field paths, root errors and null input', async () => {
    await expect(f.call('createEntry', ana, 'texto')).rejects.toMatchObject({ code: 'validation', details: { _: [expect.any(String)] } });
    await expect(f.call('getEntry', ana, { entryId: 'no-uuid' })).rejects.toMatchObject({ details: { entryId: [expect.any(String)] } });
    expect(await f.call('me', null, null)).toEqual({ user: null });
    expect(await f.call('logout', null, undefined)).toEqual({ ok: true });
  });

  it('renames the personal dictionary', async () => {
    const d = await f.call('updateMyDictionary', ana, { title: 'Mis palabras', avatar: 'volcan-2' });
    expect(d).toMatchObject({ id: anaDict, title: 'Mis palabras', avatar: 'volcan-2', kind: 'personal', myRole: null });
    await expect(f.call('updateMyDictionary', ana, { title: 'X', avatar: '../evil' })).rejects.toMatchObject({ code: 'validation' });
    expect(await f.call('myClassrooms', await f.user('student', 'nadie'), {})).toEqual([]);
  });

  it('creates entries only in the own personal dictionary', async () => {
    const input = { headword: 'mojo', senses: [{ definition: 'Salsa' }] };
    await expect(f.call('createEntry', teacher, { dictionaryId: aula, entry: input })).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('createEntry', ben, { dictionaryId: anaDict, entry: input })).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('createEntry', ana, { dictionaryId: missing, entry: input })).rejects.toMatchObject({ code: 'not_found' });
  });

  it('rejects missing media and more than one file of a kind per sense', async () => {
    const a = await f.svc.media.uploadMedia(ana, { bytes: PNG, name: 'a.png' });
    const b = await f.svc.media.uploadMedia(ana, { bytes: PNG, name: 'b.png' });
    const create = (mediaIds: string[]) =>
      f.call('createEntry', ana, { dictionaryId: anaDict, entry: { headword: 'drago', senses: [{ definition: 'Árbol', mediaIds }] } });
    await expect(create([missing])).rejects.toMatchObject({ code: 'validation', message: 'Falta un archivo adjunto.' });
    await expect(create([a.id, b.id])).rejects.toMatchObject({ code: 'validation', message: expect.stringMatching(/una imagen/) });
    expect((await create([a.id])).senses[0]!.media.map((m) => m.id)).toEqual([a.id]);
  });

  it('keeps sense ids on update and refuses a sense of another entry', async () => {
    const e = await f.entry(ana, 'guirre', { example: 'Un guirre' });
    const other = await f.entry(ana, 'cuervo');
    const senses = [{ ...e.senses[0]!, media: undefined, definition: 'Alimoche' }, { definition: 'Persona flaca' }];
    const u = await f.call('updateEntry', ana, { entryId: e.id, version: e.version, entry: { headword: 'guirre', senses } });
    expect(u.senses.map((s) => [s.definition, s.id === e.senses[0]!.id])).toEqual([
      ['Alimoche', true],
      ['Persona flaca', false],
    ]);
    await expect(
      f.call('updateEntry', ana, { entryId: e.id, version: u.version, entry: { headword: 'guirre', senses: [{ id: other.senses[0]!.id, definition: 'x' }] } }),
    ).rejects.toMatchObject({ code: 'validation', message: 'La acepción no pertenece a esta entrada.' });
    await expect(
      f.call('updateEntry', ana, { entryId: e.id, version: u.version, entry: { headword: 'Cuervo', senses: [{ definition: 'x' }] } }),
    ).rejects.toMatchObject({ code: 'conflict', details: { headword: [expect.any(String)] } });
    await expect(f.call('updateEntry', ben, { entryId: e.id, version: u.version, entry: { headword: 'x', senses: [{ definition: 'x' }] } })).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(f.call('getEntry', ana, { entryId: missing })).rejects.toMatchObject({ code: 'not_found' });
  });

  it('search escapes LIKE wildcards and matches accents loosely', async () => {
    await f.entry(ana, '50% gofio');
    await f.entry(ana, 'añil');
    const q = async (text: string) => (await f.call('listEntries', ana, { dictionaryId: anaDict, q: text })).items.map((i) => i.headword);
    expect(await q('%')).toEqual(['50% gofio']);
    expect(await q('_')).toEqual([]);
    expect(await q('ANIL')).toEqual([]); // ñ is its own letter
    expect(await q('AÑIL')).toEqual(['añil']);
    const page = await f.call('listEntries', ana, { dictionaryId: anaDict, limit: 2, offset: 1 });
    expect(page.items).toHaveLength(2);
    expect(page.total).toBeGreaterThan(3);
  });

  describe('in a classroom', () => {
    let published: string;
    beforeAll(async () => {
      const topic = f.vocab['topic:dialecto_canario'];
      const e = await f.entry(ana, 'guagua', { topicIds: [topic!] });
      const sub = await f.submit(ana, e.id, aula);
      await f.call('publishSubmissions', teacher, { submissionIds: [sub] });
      published = (await f.call('getSubmission', teacher, { submissionId: sub })).publishedEntryId!;
      const full = await f.call('getEntry', teacher, { entryId: published });
      await f.call('updateEntry', teacher, {
        entryId: published,
        version: full.version,
        entry: { headword: 'guagua', senses: [...full.senses.map((s) => ({ ...s, media: undefined })), { definition: 'Bebé' }] },
      });
    });

    it('teacher edits keep a revision and an audit record', async () => {
      expect(await lastAudit()).toMatchObject({ action: 'entry.teacher_edit', entityId: published });
      expect((await f.call('getEntry', ben, { entryId: published })).senses).toHaveLength(2);
    });

    it('hiding the last visible sense is refused; hidden senses disappear for students, also in topic search', async () => {
      const full = await f.call('getEntry', teacher, { entryId: published });
      await expect(f.call('setSenseHidden', teacher, { senseId: missing, hidden: true })).rejects.toMatchObject({ code: 'not_found' });
      await expect(f.call('setSenseHidden', ben, { senseId: full.senses[0]!.id, hidden: true })).rejects.toMatchObject({ code: 'forbidden' });
      await f.call('setSenseHidden', teacher, { senseId: full.senses[0]!.id, hidden: true });
      await expect(f.call('setSenseHidden', teacher, { senseId: full.senses[1]!.id, hidden: true })).rejects.toMatchObject({
        code: 'validation',
        message: expect.stringMatching(/al menos una acepción visible/),
      });
      expect((await f.call('getEntry', ben, { entryId: published })).senses.map((s) => s.definition)).toEqual(['Bebé']);
      expect((await f.call('getEntry', teacher, { entryId: published })).senses).toHaveLength(2);
      const topicId = f.vocab['topic:dialecto_canario'];
      // Only the hidden sense carries the topic: students no longer find the entry by it, the teacher does.
      expect((await f.call('listEntries', ben, { dictionaryId: aula, topicId })).total).toBe(0);
      expect((await f.call('listEntries', teacher, { dictionaryId: aula, topicId })).total).toBe(1);
      await f.call('setSenseHidden', teacher, { senseId: full.senses[0]!.id, hidden: false });
      expect((await f.call('listEntries', ben, { dictionaryId: aula, topicId })).total).toBe(1);
    });

    it('exports visible entries; hidden ones only for editors who ask for them', async () => {
      await f.call('setEntryHidden', teacher, { entryId: published, hidden: true });
      await expect(f.call('getEntry', ben, { entryId: published })).rejects.toMatchObject({ code: 'not_found' });
      expect((await f.call('exportDictionary', ben, { dictionaryId: aula, includeHidden: true })).entries).toEqual([]);
      expect((await f.call('exportDictionary', teacher, { dictionaryId: aula })).entries).toEqual([]);
      const all = await f.call('exportDictionary', teacher, { dictionaryId: aula, includeHidden: 'true' });
      expect(all.dictionary.title).toBe('Aula');
      expect(all.entries.map((e) => [e.headword, e.hidden])).toEqual([['guagua', true]]);
      expect((await f.call('listEntries', teacher, { dictionaryId: aula, includeHidden: false })).total).toBe(0);
      await f.call('setEntryHidden', teacher, { entryId: published, hidden: false });
      await expect(f.call('exportDictionary', ben, { dictionaryId: anaDict })).rejects.toMatchObject({ code: 'not_found' });
    });

    it('unpublishing a classroom entry is audited and keeps the personal entry', async () => {
      await expect(f.call('deleteEntry', ben, { entryId: published })).rejects.toMatchObject({ code: 'forbidden' });
      expect(await f.call('deleteEntry', teacher, { entryId: published })).toEqual({ ok: true });
      expect(await lastAudit()).toMatchObject({ action: 'entry.unpublish', entityId: published });
      await expect(f.call('getEntry', teacher, { entryId: published })).rejects.toMatchObject({ code: 'not_found' });
      expect((await f.call('listEntries', ana, { dictionaryId: anaDict, q: 'guagua' })).total).toBe(1);
    });
  });

  describe('media', () => {
    it('refuses empty, oversized and over-quota uploads', async () => {
      await expect(f.svc.media.uploadMedia(ben, { bytes: new Uint8Array(0), name: 'x.png' })).rejects.toMatchObject({
        message: 'El archivo está vacío.',
      });
      const big = new Uint8Array(2048);
      big.set(PNG);
      await expect(f.svc.media.uploadMedia(ben, { bytes: big, name: 'x.png' })).rejects.toMatchObject({ message: expect.stringMatching(/máximo/) });
      await expect(f.svc.media.uploadMedia(null, { bytes: PNG, name: 'x.png' })).rejects.toMatchObject({ code: 'unauthenticated' });
      // ~70 bytes each against a 4 KB daily quota.
      const n = Math.floor(4096 / PNG.byteLength);
      for (let i = 0; i < n; i++) await f.svc.media.uploadMedia(ben, { bytes: PNG, name: `${i}.png` });
      await expect(f.svc.media.uploadMedia(ben, { bytes: PNG, name: 'otra.png' })).rejects.toMatchObject({ message: expect.stringMatching(/24 horas/) });
      // The quota is per user.
      expect((await f.svc.media.uploadMedia(teacher, { bytes: PNG, name: 'otra.png' })).kind).toBe('image');
    });

    it('cleans the original name and returns not found for unknown or lost files', async () => {
      const m = await f.svc.media.uploadMedia(ana, { bytes: PNG, name: '../../etc/pa$$wd<script>.png' });
      expect(m.originalName).toBe('.._.._etc_pa__wd_script_.png');
      expect((await f.svc.media.readMedia(ana, m.id)).name).toBe(m.originalName);
      await expect(f.svc.media.readMedia(ana, missing)).rejects.toMatchObject({ code: 'not_found' });
      f.blobs.clear();
      await expect(f.svc.media.readMedia(ana, m.id)).rejects.toMatchObject({ code: 'not_found' });
    });
  });

  describe('auth', () => {
    it('rejects malformed password hashes and unknown or disabled accounts', async () => {
      expect(await verifyPassword('x', 'md5$abc')).toBe(false);
      expect(await verifyPassword('x', 'pbkdf2$1000$')).toBe(false);
      expect(await f.call('me', { userId: missing, globalRole: 'student' }, {})).toEqual({ user: null });
      await f.db.update(users).set({ status: 'disabled' }).where(eq(users.id, ben.userId));
      await expect(f.call('login', null, { email: 'ben@ejemplo.com', password: 'x' })).rejects.toMatchObject({ code: 'unauthenticated' });
      expect(await f.svc.auth.actorFor(ben.userId)).toBeNull();
      expect(await f.svc.auth.actorFor(ana.userId)).toEqual(ana);
      await f.db.update(users).set({ status: 'active' }).where(eq(users.id, ben.userId));
    });

    it('institutional sign-in falls back to the subject as display name and keeps local admin grants', async () => {
      const profile = { subject: 'cas-sin-nombre', firstName: '', lastName: '', email: null, schools: [{ code: '35000001', name: 'IES Ficticio', role: '1' }] };
      const u = await f.svc.auth.signInInstitutional(profile);
      expect(u).toMatchObject({ displayName: 'cas-sin-nombre', globalRole: 'teacher', email: null });
      await f.db.update(users).set({ globalRole: 'admin' }).where(eq(users.id, u.id));
      const again = await f.svc.auth.signInInstitutional({ ...profile, firstName: 'Nombre', schools: [] });
      expect(again).toMatchObject({ id: u.id, displayName: 'Nombre', globalRole: 'admin' });
      await f.db.update(users).set({ globalRole: 'teacher' }).where(eq(users.id, u.id));
      expect((await f.svc.auth.signInInstitutional({ ...profile, schools: [] })).globalRole).toBe('student');
    });
  });

  it('reports the demo seed version (none on an empty database)', async () => {
    expect(await demoSeedVersion(f.db)).toBeNull();
  });
});
