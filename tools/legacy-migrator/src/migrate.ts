import { createHash, randomUUID } from 'node:crypto';
import { copyFile, mkdir, readFile, readdir, stat as fsStat } from 'node:fs/promises';
import { join } from 'node:path';
import {
  SENSE_FIELD_CODES,
  headwordKey,
  initialOf,
  normalizeText,
  sortKeyOf,
  type EntrySnapshot,
  type MediaKind,
  type MemberRole,
  type SenseField,
  type Vocabulary,
} from '@lexican/core';
import {
  appSettings,
  authIdentities,
  classroomSettings,
  comments,
  dictionaries,
  dictionaryMemberships,
  entries,
  entryRevisions,
  entrySenses,
  mediaAssets,
  schools,
  seedVocabulary,
  senseMedia,
  senseTopics,
  submissions,
  userSchools,
  users,
  vocabularyValues,
  type Db,
} from '@lexican/db';
import { eq, sql, type SQL } from 'drizzle-orm';
import type { Acepcion, DpEntrada, LegacyData, Master, Medio, Tematica } from './legacy.ts';
import { COUNTED_ONLY } from './legacy.ts';
import { anomaly, newReport, orphan, stat, type Report } from './report.ts';
import {
  LEGACY_MEDIA,
  buildSnapshot,
  codeFromLabel,
  commentsVisibilityOf,
  dateOrNull,
  entryStateOf,
  fieldCodeOf,
  generateJoinCode,
  globalRoleOf,
  groupLabelOf,
  htmlLosses,
  htmlToText,
  isJoinCode,
  legacyJoinCode,
  maxSensesOf,
  memberRoleOf,
  normalizeLabel,
  orderSenses,
  originalNameOf,
  safeFileName,
  sniffMedia,
  submissionStatusOf,
  vocabularyOfField,
  type SenseData,
} from './transform.ts';

export interface MigrateOptions {
  /** Legacy `storage/app/public` directory (contains `dp/medios/{imagenes,audios,videos}`). */
  mediaSource: string;
  /** Root of the API filesystem MediaStorage (`<root>/<first 2 chars of key>/<key>`). */
  mediaTarget: string;
  /** Run inside a transaction and roll it back; media files are hashed but not copied. */
  dryRun: boolean;
  /** Roll back (and report a failure) when any orphan row was found. */
  failOnOrphans: boolean;
}

class Rollback extends Error {}

/** Legacy tables and the target table holding rows with that `legacy_source` (for the «new» count). */
const TARGET_OF: Record<string, string> = {
  users: 'users',
  personas: 'users',
  centros: 'schools',
  mst_ensenanzas: 'vocabulary_values',
  mst_nivel_estudios: 'vocabulary_values',
  mst_areas_materias: 'vocabulary_values',
  mst_tipos_diccionario_aula: 'vocabulary_values',
  mst_campos_valores: 'vocabulary_values',
  dic_personal: 'dictionaries',
  dp_entradas: 'entries',
  dp_acepciones: 'entry_senses',
  dp_acepciones_medios: 'media_assets',
  dic_aula: 'dictionaries',
  dic_aula_participantes: 'dictionary_memberships',
  envios_entradas: 'submissions',
  envios_acepciones: 'entry_senses',
  envios_acepciones_medios: 'media_assets',
  dic_aula_entradas: 'entries',
  comentarios_generales: 'comments',
  comentarios_entradas: 'comments',
};

const NOT_MIGRATED_REASON: Record<string, string> = {
  audits:
    'Log técnico de owen-it; solo se cuenta. El historial funcional nace en entry_revisions/audit_events (§53).',
  password_resets: 'Tokens temporales de Laravel; sin valor tras el corte.',
  avisos: 'Esquema muerto: ninguna funcionalidad escribe avisos.',
  avisos_dp_envios: 'Esquema muerto (avisos).',
  avisos_coment_generales: 'Esquema muerto (avisos).',
  avisos_coment_entradas: 'Esquema muerto (avisos).',
  avisos_entradas_compartidas: 'Esquema muerto (avisos).',
  entradas_compartidas: 'Esquema muerto: sin modelo ni pantalla.',
  dic_aula_destinatario_avisos:
    'Avisos por correo e invitaciones sin implementación; contiene emails (minimización §21).',
  mst_ensenanzas_estudios: 'Producto cartesiano sin uso.',
  mst_estudios_areas_materias: 'Producto cartesiano sin uso.',
  centros_dic_aula: 'Tabla eliminada por una migración de 2021.',
  user_roles: 'Roles secundarios de Voyager; el rol global sale de users.role_id.',
  permissions: 'Voyager (sustituido por roles explícitos).',
  permission_role: 'Voyager.',
  menus: 'Voyager.',
  menu_items: 'Voyager.',
  data_types: 'Voyager (BREAD).',
  data_rows: 'Voyager (BREAD).',
  settings: 'Voyager (ajustes del panel, ninguno de dominio).',
  translations: 'Voyager (i18n del panel).',
  pages: 'Voyager CMS de ejemplo.',
  posts: 'Voyager CMS de ejemplo.',
  categories: 'Voyager CMS de ejemplo.',
  migrations: 'Control de migraciones de Laravel.',
};

const groupBy = <T, K>(rows: T[], key: (r: T) => K): Map<K, T[]> => {
  const m = new Map<K, T[]>();
  for (const r of rows) {
    const k = key(r);
    const list = m.get(k);
    if (list) list.push(r);
    else m.set(k, [r]);
  }
  return m;
};
const byId = <T extends { id: number }>(rows: T[]): Map<number, T> =>
  new Map(rows.map((r) => [r.id, r]));
const headwordColumns = (h: string) => ({
  headword: normalizeText(h),
  headwordKey: headwordKey(h),
  initial: initialOf(h),
  sortKey: sortKeyOf(h),
});

async function scalar(db: Db, q: SQL): Promise<number> {
  const r = (await db.execute(q)) as unknown as { rows: { n: number | string | null }[] };
  return Number(r.rows[0]?.n ?? 0);
}

/** Runs the whole migration in one PostgreSQL transaction (rolled back on dry-run). Never throws: failures go to the report. */
export async function migrateLegacy(
  db: Db,
  legacy: LegacyData,
  opts: MigrateOptions,
): Promise<Report> {
  const report = newReport(opts.dryRun);
  const t0 = performance.now();
  try {
    await db.transaction(async (tx) => {
      await run(tx, legacy, opts, report);
      if (opts.failOnOrphans && report.orphans > 0)
        throw new Rollback(
          `${report.orphans} registros huérfanos con --fail-on-orphans: transacción revertida`,
        );
      if (opts.dryRun) throw new Rollback('dry-run');
    });
    report.committed = true;
  } catch (e) {
    if (!(e instanceof Rollback))
      report.failure = e instanceof Error ? (e.stack ?? e.message) : String(e);
    else if (e.message !== 'dry-run') report.failure = e.message;
  }
  report.finishedAt = new Date().toISOString();
  report.durationMs = Math.round(performance.now() - t0);
  return report;
}

interface Asset {
  id: string;
  kind: MediaKind;
  mime: string;
  originalName: string;
}

type SenseRow = SenseData & { legacyId: number; createdAt: Date | null; updatedAt: Date | null };

async function run(db: Db, L: LegacyData, opts: MigrateOptions, report: Report): Promise<void> {
  const now = new Date();
  const R = report;
  const legacyCounts: Record<string, number> = {
    roles: L.roles.length,
    users: L.users.length,
    personas: L.personas.length,
    users_personas: L.usersPersonas.length,
    centros: L.centros.length,
    users_centros: L.usersCentros.length,
    mst_ensenanzas: L.ensenanzas.length,
    mst_nivel_estudios: L.nivelEstudios.length,
    mst_areas_materias: L.areasMaterias.length,
    mst_tipos_diccionario_aula: L.tiposDiccionario.length,
    mst_campos_entrada: L.camposEntrada.length,
    mst_campos_valores: L.camposValores.length,
    mst_pautas: L.mstPautas.length,
    dic_personal: L.dicPersonal.length,
    dp_entradas: L.dpEntradas.length,
    dp_acepciones: L.dpAcepciones.length,
    dp_acepciones_tematicas: L.dpTematicas.length,
    dp_acepciones_medios: L.dpMedios.length,
    dic_aula: L.dicAula.length,
    dic_aula_atemporales: L.atemporales.length,
    dic_aula_pautas: L.dicAulaPautas.length,
    dic_aula_participantes: L.participantes.length,
    dic_aula_campos: L.dicAulaCampos.length,
    dp_envios: L.dpEnvios.length,
    envios_entradas: L.enviosEntradas.length,
    envios_acepciones: L.enviosAcepciones.length,
    envios_acepciones_tematicas: L.enviosTematicas.length,
    envios_acepciones_medios: L.enviosMedios.length,
    dic_aula_entradas: L.dicAulaEntradas.length,
    comentarios_generales: L.comentariosGenerales.length,
    comentarios_entradas: L.comentariosEntradas.length,
  };
  for (const [t, n] of Object.entries(legacyCounts)) stat(R, t).legacy = n;
  for (const t of COUNTED_ONLY)
    R.notMigrated[t] = { count: L.counts[t] ?? null, reason: NOT_MIGRATED_REASON[t] ?? '' };

  // ── 1. Controlled vocabularies (match the seed by label; never by legacy id) ──────────────────────────
  await seedVocabulary(db);
  type VRow = typeof vocabularyValues.$inferSelect;
  const vByLabel = new Map<string, VRow>();
  const vByLegacy = new Map<string, VRow>();
  const vCodes = new Set<string>();
  const vMaxPos = new Map<Vocabulary, number>();
  const registerV = (v: VRow) => {
    if (!vByLabel.has(`${v.vocabulary}|${normalizeLabel(v.label)}`))
      vByLabel.set(`${v.vocabulary}|${normalizeLabel(v.label)}`, v);
    if (v.legacySource !== null && v.legacyId !== null)
      vByLegacy.set(`${v.legacySource}|${v.legacyId}`, v);
    vCodes.add(`${v.vocabulary}|${v.code}`);
    vMaxPos.set(v.vocabulary, Math.max(vMaxPos.get(v.vocabulary) ?? -1, v.position));
  };
  for (const v of await db.select().from(vocabularyValues)) registerV(v);

  async function master(source: string, row: Master, vocab: Vocabulary): Promise<string> {
    const lk = `${source}|${row.id}`;
    const known = vByLegacy.get(lk);
    if (known?.vocabulary === vocab) return known.id;
    const match = vByLabel.get(`${vocab}|${normalizeLabel(row.descripcion)}`);
    if (match) {
      if (match.legacySource === null && !known) {
        await db
          .update(vocabularyValues)
          .set({ legacySource: source, legacyId: row.id })
          .where(eq(vocabularyValues.id, match.id));
        match.legacySource = source;
        match.legacyId = row.id;
        vByLegacy.set(lk, match);
      }
      return match.id;
    }
    const base = codeFromLabel(row.descripcion);
    let code = base;
    for (let i = 2; vCodes.has(`${vocab}|${code}`); i++) code = `${base}_${i}`;
    const [created] = await db
      .insert(vocabularyValues)
      .values({
        vocabulary: vocab,
        code,
        label: row.descripcion.trim(),
        position: (vMaxPos.get(vocab) ?? -1) + 1,
        featured: /^\s/.test(row.descripcion), // legacy «leading space sorts first» trick
        active: row.estado !== '0' && !dateOrNull(row.deleted_at),
        legacySource: known ? null : source,
        legacyId: known ? null : row.id,
      })
      .returning();
    registerV(created!);
    anomaly(
      R,
      source,
      row.id,
      'needs human review',
      `«${row.descripcion.trim()}» sin equivalente en el seed (${vocab}): creado con código «${code}»`,
    );
    return created!.id;
  }

  const masterSets: [string, Master[], Vocabulary][] = [
    ['mst_ensenanzas', L.ensenanzas, 'education_stage'],
    ['mst_nivel_estudios', L.nivelEstudios, 'study_level'],
    ['mst_areas_materias', L.areasMaterias, 'subject'],
    ['mst_tipos_diccionario_aula', L.tiposDiccionario, 'dictionary_type'],
  ];
  const masterById = new Map<string, Map<number, Master>>();
  for (const [source, rows, vocab] of masterSets) {
    masterById.set(source, byId(rows));
    for (const row of rows) {
      await master(source, row, vocab);
      stat(R, source).mapped++;
    }
  }
  async function masterRef(
    source: string,
    legacyId: number | null,
    vocab: Vocabulary,
    table: string,
    rowId: number,
  ): Promise<string | null> {
    if (legacyId === null) return null;
    const row = masterById.get(source)?.get(legacyId);
    if (!row) {
      anomaly(
        R,
        table,
        rowId,
        'needs human review',
        `referencia rota a ${source}.id=${legacyId}: queda vacío`,
      );
      return null;
    }
    return master(source, row, vocab);
  }

  const campoById = byId(L.camposEntrada);
  const fieldOfCampo = new Map<number, SenseField>();
  for (const c of L.camposEntrada) {
    const f = fieldCodeOf(c.nombre_campo);
    if (f) fieldOfCampo.set(c.id, f);
    else
      anomaly(
        R,
        'mst_campos_entrada',
        c.id,
        'needs human review',
        `campo «${c.nombre_campo}» sin equivalente: su configuración por aula no se migra`,
      );
    stat(R, 'mst_campos_entrada')[f ? 'mapped' : 'invalid']++;
  }
  const valueById = byId(L.camposValores);
  const valueVocab = new Map<number, Vocabulary>();
  for (const v of L.camposValores) {
    const voc = vocabularyOfField(campoById.get(v.mst_campo_entrada_id)?.nombre_campo ?? '');
    if (!voc) continue; // fresh-seed id mismatch (e.g. languages under «Imagen»): resolved by the column that uses it
    valueVocab.set(v.id, voc);
    await master('mst_campos_valores', v, voc);
    stat(R, 'mst_campos_valores').mapped++;
  }
  const valueCache = new Map<string, string>();
  const crossReported = new Set<number>();
  async function valueRef(
    legacyId: number | null,
    vocab: Vocabulary,
    table: string,
    rowId: number,
  ): Promise<string | null> {
    if (legacyId === null) return null;
    const cached = valueCache.get(`${legacyId}|${vocab}`);
    if (cached) return cached;
    const v = valueById.get(legacyId);
    if (!v) {
      anomaly(
        R,
        table,
        rowId,
        'needs human review',
        `referencia rota a mst_campos_valores.id=${legacyId} (${vocab}): queda vacío`,
      );
      return null;
    }
    const declared = valueVocab.get(legacyId);
    if (declared !== vocab && !crossReported.has(legacyId)) {
      crossReported.add(legacyId);
      if (!declared) stat(R, 'mst_campos_valores').mapped++;
      anomaly(
        R,
        'mst_campos_valores',
        legacyId,
        'repairable automatically',
        `«${v.descripcion.trim()}» cuelga del campo ${v.mst_campo_entrada_id} pero se usa como ${vocab}: se asigna por uso`,
      );
    }
    const id = await master('mst_campos_valores', v, vocab);
    valueCache.set(`${legacyId}|${vocab}`, id);
    return id;
  }

  // ── 2. Users (users + personas + users_personas → users + auth_identities; no national ids) ─────────
  const roleName = new Map(L.roles.map((r) => [r.id, r.name]));
  const personaById = byId(L.personas);
  const userOfPersona = new Map<number, string>();
  const userOfLegacyUser = new Map<number, string>();
  const linksByUser = groupBy(
    L.usersPersonas.filter((l) => !dateOrNull(l.deleted_at)),
    (l) => l.user_id,
  );
  for (const l of L.usersPersonas)
    if (dateOrNull(l.deleted_at)) stat(R, 'users_personas').skipped++;
  const usedEmails = new Set<string>();
  const usedSubjects = new Set<string>();
  for (const u of L.users) {
    const links = (linksByUser.get(u.id) ?? []).filter((l) => {
      if (personaById.has(l.persona_id)) return true;
      orphan(
        R,
        'users_personas',
        u.id,
        'cannot migrate',
        `users_personas apunta a persona ${l.persona_id} inexistente`,
      );
      return false;
    });
    if (links.length > 1)
      anomaly(
        R,
        'users',
        u.id,
        'needs human review',
        `usuario con ${links.length} personas; se usa la persona ${links.at(-1)!.persona_id}`,
      );
    const personaId = links.at(-1)?.persona_id;
    const p = personaId !== undefined ? personaById.get(personaId) : undefined;
    if (!p)
      anomaly(
        R,
        'users',
        u.id,
        'needs human review',
        'usuario sin persona: nombre provisional «Usuario legacy N»',
      );
    const firstName = normalizeText(p?.nombre ?? '');
    const lastName = normalizeText(p?.apellidos ?? '');
    const email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((u.email ?? '').trim())
      ? u.email!.trim()
      : null;
    const emailFree = email !== null && !usedEmails.has(email.toLowerCase());
    if (email && !emailFree)
      anomaly(
        R,
        'users',
        u.id,
        'needs human review',
        'email repetido (sin distinguir mayúsculas): no se migra',
      );
    if (email && emailFree) usedEmails.add(email.toLowerCase());
    let role = globalRoleOf(u.role_id === null ? undefined : roleName.get(u.role_id));
    if (!role) {
      role = 'student';
      anomaly(
        R,
        'users',
        u.id,
        'needs rule',
        `rol legacy desconocido (role_id=${u.role_id}): se asigna «student»`,
      );
    }
    const values = {
      email: emailFree ? email : null,
      displayName: `${firstName} ${lastName}`.trim() || `Usuario legacy ${u.id}`,
      firstName,
      lastName,
      globalRole: role,
      status: p && dateOrNull(p.deleted_at) ? ('disabled' as const) : ('active' as const),
      createdAt: dateOrNull(u.created_at) ?? now,
      updatedAt: dateOrNull(u.updated_at) ?? now,
    };
    const [row] = await db
      .insert(users)
      .values({ ...values, legacySource: 'users', legacyId: u.id })
      .onConflictDoUpdate({ target: [users.legacySource, users.legacyId], set: values })
      .returning({ id: users.id });
    userOfLegacyUser.set(u.id, row!.id);
    stat(R, 'users').mapped++;
    for (const l of links) {
      if (userOfPersona.has(l.persona_id))
        anomaly(
          R,
          'users_personas',
          l.persona_id,
          'needs human review',
          `persona enlazada a varios usuarios; se usa el primero (usuario ${u.id} ignorado)`,
        );
      else userOfPersona.set(l.persona_id, row!.id);
      stat(R, 'users_personas').mapped++;
    }
    const subject = (u.name ?? '').trim();
    if (!subject)
      anomaly(
        R,
        'users',
        u.id,
        'cannot migrate',
        'users.name vacío: sin identidad CAS (no podrá iniciar sesión)',
      );
    else if (usedSubjects.has(subject.toLowerCase()))
      anomaly(
        R,
        'users',
        u.id,
        'needs human review',
        'identificador CAS repetido: la identidad queda en el primer usuario',
      );
    else {
      usedSubjects.add(subject.toLowerCase());
      await db
        .insert(authIdentities)
        .values({ userId: row!.id, provider: 'cas', subject, createdAt: values.createdAt })
        .onConflictDoNothing();
    }
  }
  for (const p of L.personas) {
    if (userOfPersona.has(p.id)) {
      stat(R, 'personas').mapped++;
      continue;
    }
    // A persona without account still owns data: keep it as a disabled user without identity.
    const values = {
      displayName:
        `${normalizeText(p.nombre)} ${normalizeText(p.apellidos)}`.trim() ||
        `Persona legacy ${p.id}`,
      firstName: normalizeText(p.nombre),
      lastName: normalizeText(p.apellidos),
      globalRole: 'student' as const,
      status: 'disabled' as const,
      createdAt: dateOrNull(p.created_at) ?? now,
      updatedAt: dateOrNull(p.updated_at) ?? now,
    };
    const [row] = await db
      .insert(users)
      .values({ ...values, legacySource: 'personas', legacyId: p.id })
      .onConflictDoUpdate({ target: [users.legacySource, users.legacyId], set: values })
      .returning({ id: users.id });
    userOfPersona.set(p.id, row!.id);
    stat(R, 'personas').mapped++;
    anomaly(
      R,
      'personas',
      p.id,
      'needs human review',
      'persona sin usuario: se crea un usuario deshabilitado sin identidad CAS',
    );
  }

  // ── 3. Schools (centros, users_centros) ──────────────────────────────────────────────────────────────
  const schoolOfCentro = new Map<number, string>();
  const schoolByCode = new Map<string, string>();
  const skippedCentros = new Set<number>();
  for (const c of L.centros) {
    const code = c.cod_centro.trim();
    if (dateOrNull(c.deleted_at) || code === '00000000' || !code) {
      stat(R, 'centros').skipped++;
      skippedCentros.add(c.id);
      if (code === '00000000')
        anomaly(
          R,
          'centros',
          c.id,
          'repairable automatically',
          'centro «sin centro» (00000000): no se migra',
        );
      continue;
    }
    const known = schoolByCode.get(code);
    if (known) {
      schoolOfCentro.set(c.id, known);
      stat(R, 'centros').mapped++;
      anomaly(
        R,
        'centros',
        c.id,
        'repairable automatically',
        `cod_centro ${code} repetido: fusionado con el primero`,
      );
      continue;
    }
    const [row] = await db
      .insert(schools)
      .values({
        code,
        name: normalizeText(c.denominacion) || code,
        legacySource: 'centros',
        legacyId: c.id,
      })
      .onConflictDoUpdate({
        target: schools.code,
        set: {
          name: normalizeText(c.denominacion) || code,
          legacySource: 'centros',
          legacyId: c.id,
        },
      })
      .returning({ id: schools.id });
    schoolOfCentro.set(c.id, row!.id);
    schoolByCode.set(code, row!.id);
    stat(R, 'centros').mapped++;
  }
  for (const uc of L.usersCentros) {
    const userId = userOfLegacyUser.get(uc.user_id);
    const schoolId = schoolOfCentro.get(uc.centro_id);
    if (dateOrNull(uc.deleted_at) || skippedCentros.has(uc.centro_id)) {
      stat(R, 'users_centros').skipped++;
      continue;
    }
    if (!userId || !schoolId) {
      orphan(
        R,
        'users_centros',
        uc.user_id,
        'cannot migrate',
        `users_centros (${uc.user_id}, ${uc.centro_id}) sin usuario o centro`,
      );
      continue;
    }
    await db.insert(userSchools).values({ userId, schoolId }).onConflictDoNothing();
    stat(R, 'users_centros').mapped++;
  }

  // ── 4-8. Personal dictionaries, entries, senses, topics, media ───────────────────────────────────────
  const dictOfDicPersonal = new Map<number, string>();
  const ownerOfDicPersonal = new Map<number, string>();
  const personalByPersona = groupBy(L.dicPersonal, (d) => d.persona_id);
  const activePersonal = new Map<number, number>(); // persona → dic_personal id kept active (legacy uses max id)
  for (const [personaId, list] of personalByPersona) {
    const alive = list.filter((d) => !dateOrNull(d.deleted_at));
    if (alive.length) activePersonal.set(personaId, Math.max(...alive.map((d) => d.id)));
  }
  for (const d of L.dicPersonal) {
    const ownerId = userOfPersona.get(d.persona_id);
    if (!ownerId) {
      orphan(R, 'dic_personal', d.id, 'cannot migrate', `persona ${d.persona_id} inexistente`);
      continue;
    }
    let deletedAt = dateOrNull(d.deleted_at);
    if (!deletedAt && activePersonal.get(d.persona_id) !== d.id) {
      deletedAt = dateOrNull(d.updated_at) ?? now;
      anomaly(
        R,
        'dic_personal',
        d.id,
        'needs human review',
        `la persona tiene varios diccionarios personales; activo el ${activePersonal.get(d.persona_id)}, este queda borrado`,
      );
    }
    const values = {
      kind: 'personal' as const,
      title: normalizeText(d.titulo) || 'Mi diccionario personal',
      avatar: 'default',
      ownerId,
      createdAt: dateOrNull(d.created_at) ?? now,
      updatedAt: dateOrNull(d.updated_at) ?? now,
      deletedAt,
    };
    const [row] = await db
      .insert(dictionaries)
      .values({ ...values, legacySource: 'dic_personal', legacyId: d.id })
      .onConflictDoUpdate({
        target: [dictionaries.legacySource, dictionaries.legacyId],
        set: values,
      })
      .returning({ id: dictionaries.id });
    dictOfDicPersonal.set(d.id, row!.id);
    ownerOfDicPersonal.set(d.id, ownerId);
    stat(R, 'dic_personal').mapped++;
  }

  // Media (§46): never modify originals; one asset per distinct content.
  const referenced = new Set<string>();
  for (const m of [...L.dpMedios, ...L.enviosMedios]) {
    const lm = LEGACY_MEDIA[m.tipo_medio ?? ''];
    const name = safeFileName(m.url_interna);
    if (lm && name) referenced.add(join(opts.mediaSource, 'dp', 'medios', lm.dir, name));
  }
  const assetByPath = new Map<string, Asset | null>();
  const assetBySha = new Map<string, Asset>();
  const keyOfAsset = new Map<string, string>();
  async function ensureCopied(path: string, key: string, size: number): Promise<void> {
    if (opts.dryRun) return;
    const target = join(opts.mediaTarget, key.slice(0, 2), key);
    const existing = await fsStat(target).catch(() => null);
    if (existing?.size === size) return;
    await mkdir(join(opts.mediaTarget, key.slice(0, 2)), { recursive: true, mode: 0o750 });
    await copyFile(path, target);
    R.media.bytesCopied += size;
  }
  async function importMedia(m: Medio, table: string, ownerId: string): Promise<Asset | null> {
    const lm = LEGACY_MEDIA[m.tipo_medio ?? ''];
    if (!lm) {
      stat(R, table).invalid++;
      anomaly(R, table, m.id, 'cannot migrate', `tipo_medio desconocido «${m.tipo_medio}»`);
      return null;
    }
    const name = safeFileName(m.url_interna);
    if (!name) {
      R.media.brokenRefs++;
      stat(R, table).invalid++;
      anomaly(
        R,
        table,
        m.id,
        'cannot migrate',
        m.url_externa
          ? 'medio por URL externa: no soportado'
          : `url_interna vacía o no válida «${m.url_interna ?? ''}»`,
      );
      return null;
    }
    const rel = `dp/medios/${lm.dir}/${name}`;
    const path = join(opts.mediaSource, 'dp', 'medios', lm.dir, name);
    if (assetByPath.has(path)) {
      const a = assetByPath.get(path)!;
      if (a) {
        R.media.sameContent++;
        stat(R, table).mapped++;
      } else stat(R, table).invalid++;
      return a;
    }
    const bytes = await readFile(path).catch(() => null);
    if (!bytes) {
      R.media.missing++;
      stat(R, table).invalid++;
      anomaly(R, table, m.id, 'cannot migrate', `fichero no encontrado: ${rel}`);
      assetByPath.set(path, null);
      return null;
    }
    const sniffed = bytes.length ? sniffMedia(bytes, lm.kind) : null;
    if (!sniffed) {
      R.media.corrupt++;
      stat(R, table).invalid++;
      anomaly(
        R,
        table,
        m.id,
        'cannot migrate',
        `fichero vacío o de formato no admitido: ${rel} (${bytes.length} bytes)`,
      );
      assetByPath.set(path, null);
      return null;
    }
    if (sniffed.kind !== lm.kind)
      anomaly(
        R,
        table,
        m.id,
        'needs human review',
        `tipo_medio ${lm.kind} pero el contenido es ${sniffed.mime}: se clasifica por contenido`,
      );
    const sha256 = createHash('sha256').update(bytes).digest('hex');
    let asset = assetBySha.get(sha256);
    let key: string;
    if (asset) {
      R.media.sameContent++;
      key = keyOfAsset.get(asset.id)!;
    } else {
      const [existing] = await db
        .select()
        .from(mediaAssets)
        .where(eq(mediaAssets.sha256, sha256))
        .limit(1);
      if (existing) {
        asset = {
          id: existing.id,
          kind: existing.kind,
          mime: existing.mime,
          originalName: existing.originalName,
        };
        key = existing.storageKey;
      } else {
        key = `${randomUUID()}.${sniffed.ext}`;
        const [row] = await db
          .insert(mediaAssets)
          .values({
            kind: sniffed.kind,
            mime: sniffed.mime,
            byteSize: bytes.length,
            sha256,
            storageKey: key,
            originalName: originalNameOf(m.nombre),
            createdBy: ownerId,
            createdAt: dateOrNull(m.created_at) ?? now,
            legacySource: table,
            legacyId: m.id,
          })
          .returning();
        asset = { id: row!.id, kind: row!.kind, mime: row!.mime, originalName: row!.originalName };
        R.media.migrated++;
      }
      assetBySha.set(sha256, asset);
      keyOfAsset.set(asset.id, key);
    }
    await ensureCopied(path, key, bytes.length);
    assetByPath.set(path, asset);
    stat(R, table).mapped++;
    return asset;
  }

  /** Legacy senses (dp_acepciones or envios_acepciones) → ordered sense data with topics and media. */
  async function senseRows(
    table: 'dp_acepciones' | 'envios_acepciones',
    rows: Acepcion[],
    topics: Map<number, Tematica[]>,
    medios: Map<number, Medio[]>,
    ownerId: string,
    entryLegacyId: number,
  ): Promise<SenseRow[]> {
    const topicTable =
      table === 'dp_acepciones' ? 'dp_acepciones_tematicas' : 'envios_acepciones_tematicas';
    const mediaTable =
      table === 'dp_acepciones' ? 'dp_acepciones_medios' : 'envios_acepciones_medios';
    const alive = rows.filter((a) => {
      if (!entryStateOf(a.estado, a.deleted_at, a.updated_at).deletedAt) return true;
      stat(R, table).skipped++;
      stat(R, topicTable).skipped += (topics.get(a.id) ?? []).length;
      stat(R, mediaTable).skipped += (medios.get(a.id) ?? []).length;
      return false;
    });
    const { rows: ordered, renumbered } = orderSenses(alive);
    if (renumbered)
      anomaly(
        R,
        table === 'dp_acepciones' ? 'dp_entradas' : 'envios_entradas',
        entryLegacyId,
        'repairable automatically',
        `orden de acepciones (${ordered.map((a) => a.orden).join(',')}) renumerado 1..${ordered.length}`,
      );
    const out: SenseRow[] = [];
    for (const a of ordered) {
      const topicIds: string[] = [];
      for (const t of topics.get(a.id) ?? []) {
        if (dateOrNull(t.deleted_at)) {
          stat(R, topicTable).skipped++;
          continue;
        }
        const id = await valueRef(t.tematica_id, 'topic', topicTable, a.id);
        if (id && !topicIds.includes(id)) topicIds.push(id);
        stat(R, topicTable)[id ? 'mapped' : 'invalid']++;
      }
      const media: Asset[] = [];
      const live = (medios.get(a.id) ?? []).filter((m) => {
        if (!dateOrNull(m.deleted_at) && m.estado !== '0') return true;
        stat(R, mediaTable).skipped++;
        return false;
      });
      for (const group of groupBy(
        live,
        (m) => LEGACY_MEDIA[m.tipo_medio ?? '']?.kind ?? m.tipo_medio,
      ).values()) {
        group.sort(
          (x, y) =>
            (dateOrNull(y.created_at)?.getTime() ?? 0) -
              (dateOrNull(x.created_at)?.getTime() ?? 0) || y.id - x.id,
        );
        for (const extra of group.slice(1)) {
          R.media.duplicates++;
          stat(R, mediaTable).skipped++;
          anomaly(
            R,
            mediaTable,
            extra.id,
            'repairable automatically',
            `medio duplicado del mismo tipo en la acepción ${a.id}: se conserva el más reciente (${group[0]!.id})`,
          );
        }
        const asset = await importMedia(group[0]!, mediaTable, ownerId);
        if (!asset) continue;
        if (media.some((x) => x.kind === asset.kind)) {
          anomaly(
            R,
            mediaTable,
            group[0]!.id,
            'needs human review',
            `la acepción ${a.id} ya tiene un ${asset.kind}: no se adjunta`,
          );
          continue;
        }
        media.push(asset);
      }
      out.push({
        legacyId: a.id,
        definition: a.definicion ?? '',
        extraInfo: a.frase_ejemplo ?? '',
        example: a.ejemplo2 ?? '',
        partOfSpeechId: await valueRef(a.cat_gramatical_id, 'part_of_speech', table, a.id),
        genderId: await valueRef(a.genero_id, 'gender', table, a.id),
        numberId: await valueRef(a.numero_id, 'number', table, a.id),
        languageId: await valueRef(a.idioma_id, 'language', table, a.id),
        foreignForm: a.idioma_palabra ?? '',
        hidden: a.estado === '2',
        topicIds,
        media,
        createdAt: dateOrNull(a.created_at),
        updatedAt: dateOrNull(a.updated_at),
      });
      stat(R, table).mapped++;
    }
    return out;
  }

  /** Replace the senses of an entry (re-runs rewrite them; the legacy key is kept on one copy only). */
  async function writeSenses(
    entryId: string,
    rows: SenseRow[],
    legacySource: string | null,
  ): Promise<void> {
    await db.delete(entrySenses).where(eq(entrySenses.entryId, entryId));
    for (const [i, s] of rows.entries()) {
      const [row] = await db
        .insert(entrySenses)
        .values({
          entryId,
          position: i + 1,
          definition: s.definition,
          extraInfo: s.extraInfo,
          example: s.example,
          partOfSpeechId: s.partOfSpeechId,
          genderId: s.genderId,
          numberId: s.numberId,
          languageId: s.languageId,
          foreignForm: s.foreignForm,
          hidden: s.hidden,
          createdAt: s.createdAt ?? now,
          updatedAt: s.updatedAt ?? now,
          legacySource,
          legacyId: legacySource ? s.legacyId : null,
        })
        .returning({ id: entrySenses.id });
      if (s.topicIds.length)
        await db
          .insert(senseTopics)
          .values(s.topicIds.map((topicId) => ({ senseId: row!.id, topicId })));
      if (s.media.length)
        await db
          .insert(senseMedia)
          .values(s.media.map((m) => ({ senseId: row!.id, mediaId: m.id, kind: m.kind })));
    }
  }

  interface EntryValues {
    dictionaryId: string;
    headword: string;
    hidden: boolean;
    deletedAt: Date | null;
    createdBy: string | null;
    updatedBy: string | null;
    createdAt: Date | null;
    updatedAt: Date | null;
    sourceSubmissionId: string | null;
    legacySource: string;
    legacyId: number;
  }
  async function upsertEntry(e: EntryValues): Promise<string> {
    const values = {
      dictionaryId: e.dictionaryId,
      ...headwordColumns(e.headword),
      hidden: e.hidden,
      sourceSubmissionId: e.sourceSubmissionId,
      createdBy: e.createdBy,
      updatedBy: e.updatedBy,
      createdAt: e.createdAt ?? now,
      updatedAt: e.updatedAt ?? now,
      deletedAt: e.deletedAt,
    };
    const [row] = await db
      .insert(entries)
      .values({ ...values, legacySource: e.legacySource, legacyId: e.legacyId })
      .onConflictDoUpdate({ target: [entries.legacySource, entries.legacyId], set: values })
      .returning({ id: entries.id });
    return row!.id;
  }
  /** Headwords are unique per dictionary among live entries (legacy has no constraint): later duplicates are soft-deleted. */
  const liveKeys = new Map<string, Map<string, number>>();
  function claimHeadword(
    dictionaryId: string,
    headword: string,
    table: string,
    legacyId: number,
  ): boolean {
    const keys = liveKeys.get(dictionaryId) ?? new Map<string, number>();
    liveKeys.set(dictionaryId, keys);
    const first = keys.get(headwordKey(headword));
    if (first !== undefined) {
      anomaly(
        R,
        table,
        legacyId,
        'needs human review',
        `«${normalizeText(headword)}» repetida en el mismo diccionario (ya migrada desde ${first}): se migra como borrada`,
      );
      return false;
    }
    keys.set(headwordKey(headword), legacyId);
    return true;
  }

  const dpSenses = groupBy(L.dpAcepciones, (a) => a.entrada_id);
  const dpTopics = groupBy(L.dpTematicas, (t) => t.acepcion_id);
  const dpMedia = groupBy(L.dpMedios, (m) => m.acepcion_id);
  const entryOfDpEntrada = new Map<number, string>();
  const dicPersonalOfDpEntrada = new Map<number, number>();
  for (const e of L.dpEntradas) {
    const dictionaryId = dictOfDicPersonal.get(e.dic_personal_id);
    const ownerId = ownerOfDicPersonal.get(e.dic_personal_id);
    if (!dictionaryId || !ownerId) {
      orphan(
        R,
        'dp_entradas',
        e.id,
        'cannot migrate',
        `diccionario personal ${e.dic_personal_id} no migrado`,
      );
      for (const a of dpSenses.get(e.id) ?? [])
        orphan(R, 'dp_acepciones', a.id, 'cannot migrate', `entrada ${e.id} no migrada`);
      continue;
    }
    if (!normalizeText(e.entrada)) {
      stat(R, 'dp_entradas').invalid++;
      anomaly(R, 'dp_entradas', e.id, 'cannot migrate', 'entrada vacía');
      continue;
    }
    const state = entryStateOf(e.estado, e.deleted_at, e.updated_at);
    if (!state.deletedAt && !claimHeadword(dictionaryId, e.entrada, 'dp_entradas', e.id))
      state.deletedAt = dateOrNull(e.updated_at) ?? now;
    const id = await upsertEntry({
      dictionaryId,
      headword: e.entrada,
      hidden: state.hidden,
      deletedAt: state.deletedAt,
      createdBy: ownerId,
      updatedBy: ownerId,
      createdAt: dateOrNull(e.created_at),
      updatedAt: dateOrNull(e.updated_at),
      sourceSubmissionId: null,
      legacySource: 'dp_entradas',
      legacyId: e.id,
    });
    entryOfDpEntrada.set(e.id, id);
    dicPersonalOfDpEntrada.set(e.id, e.dic_personal_id);
    await writeSenses(
      id,
      await senseRows('dp_acepciones', dpSenses.get(e.id) ?? [], dpTopics, dpMedia, ownerId, e.id),
      'dp_acepciones',
    );
    stat(R, 'dp_entradas').mapped++;
  }

  // ── 9-11. Classrooms, settings, guidelines, members ───────────────────────────────────────────────────
  const classroomOfAula = new Map<number, string>();
  const deletedAula = new Map(
    L.dicAula.flatMap((a) =>
      dateOrNull(a.deleted_at) ? [[a.id, dateOrNull(a.deleted_at)!] as const] : [],
    ),
  );
  const ownerOfAula = new Map<number, string>();
  const codeUses = new Map<string, number>();
  for (const a of L.dicAula) {
    const c = legacyJoinCode(a.codigo);
    if (c) codeUses.set(c, (codeUses.get(c) ?? 0) + 1);
  }
  const usedCodes = new Set(
    (await db.select({ c: classroomSettings.joinCode }).from(classroomSettings)).map((r) => r.c),
  );
  const campos = groupBy(L.dicAulaCampos, (c) => c.dic_aula_id);
  const pautas = groupBy(L.dicAulaPautas, (p) => p.dic_aula_id);
  const atemporales = groupBy(L.atemporales, (t) => t.dic_aula_id);
  for (const a of L.dicAula) {
    const ownerId = userOfPersona.get(a.persona_id);
    if (!ownerId) {
      orphan(
        R,
        'dic_aula',
        a.id,
        'cannot migrate',
        `profesor (persona ${a.persona_id}) inexistente`,
      );
      continue;
    }
    const values = {
      kind: 'classroom' as const,
      title: normalizeText(a.titulo) || `Diccionario de aula ${a.id}`,
      description: normalizeText(a.descripcion ?? ''),
      ownerId,
      createdAt: dateOrNull(a.created_at) ?? now,
      updatedAt: dateOrNull(a.updated_at) ?? now,
      deletedAt: dateOrNull(a.deleted_at),
    };
    const [d] = await db
      .insert(dictionaries)
      .values({ ...values, legacySource: 'dic_aula', legacyId: a.id })
      .onConflictDoUpdate({
        target: [dictionaries.legacySource, dictionaries.legacyId],
        set: values,
      })
      .returning({ id: dictionaries.id });
    const dictionaryId = d!.id;
    classroomOfAula.set(a.id, dictionaryId);
    ownerOfAula.set(a.id, ownerId);

    const [existing] = await db
      .select({ c: classroomSettings.joinCode })
      .from(classroomSettings)
      .where(eq(classroomSettings.dictionaryId, dictionaryId));
    // Codes stored by an earlier run that no longer pass the current rule are regenerated too.
    let joinCode = existing && isJoinCode(existing.c) ? existing.c : undefined;
    if (!joinCode) {
      const legacyCode = legacyJoinCode(a.codigo);
      if (legacyCode && codeUses.get(legacyCode) === 1 && !usedCodes.has(legacyCode))
        joinCode = legacyCode;
      else {
        do joinCode = generateJoinCode();
        while (usedCodes.has(joinCode));
        anomaly(
          R,
          'dic_aula',
          a.id,
          'repairable automatically',
          `código «${a.codigo ?? ''}» no válido o repetido: se genera ${joinCode}`,
        );
      }
    }
    usedCodes.add(joinCode);

    const perm = (atemporales.get(a.id) ?? []).filter(
      (t) => t.estado === '1' && !dateOrNull(t.deleted_at),
    );
    stat(R, 'dic_aula_atemporales').mapped += perm.length;
    stat(R, 'dic_aula_atemporales').skipped += (atemporales.get(a.id) ?? []).length - perm.length;
    if (perm.some((t) => dateOrNull(t.hasta)))
      anomaly(
        R,
        'dic_aula_atemporales',
        perm[0]!.id,
        'needs human review',
        'atemporal con fecha «hasta»: se migra como permanente sin fecha',
      );
    let validityYears = Number(a.vigencia ?? 1);
    if (!Number.isInteger(validityYears) || validityYears < 1 || validityYears > 10) {
      const fixed = Math.min(10, Math.max(1, Math.trunc(validityYears) || 1));
      anomaly(
        R,
        'dic_aula',
        a.id,
        'repairable automatically',
        `vigencia ${a.vigencia} fuera de 1..10: ${fixed}`,
      );
      validityYears = fixed;
    }
    const maxSenses = maxSensesOf(a.max_acepciones_entrada);
    if (a.max_acepciones_entrada !== null && maxSenses === null && a.max_acepciones_entrada !== 0)
      anomaly(
        R,
        'dic_aula',
        a.id,
        'repairable automatically',
        `max_acepciones_entrada=${a.max_acepciones_entrada} es el booleano legacy (RULE-I34): sin límite`,
      );

    const visible = new Set<SenseField>();
    const required = new Set<SenseField>();
    const cfg = (campos.get(a.id) ?? []).filter((c) => {
      if (!dateOrNull(c.deleted_at) && c.estado !== '0') return true;
      stat(R, 'dic_aula_campos').skipped++;
      return false;
    });
    for (const c of cfg) {
      const f = fieldOfCampo.get(c.mst_campo_entrada_id);
      if (!f) {
        stat(R, 'dic_aula_campos').invalid++;
        continue;
      }
      if (c.visible === '1' || c.obligatorio === '1') visible.add(f);
      if (c.obligatorio === '1') required.add(f);
      stat(R, 'dic_aula_campos').mapped++;
    }
    if (cfg.length === 0) {
      for (const f of SENSE_FIELD_CODES) visible.add(f);
      anomaly(
        R,
        'dic_aula',
        a.id,
        'repairable automatically',
        'sin dic_aula_campos: todos los campos visibles, ninguno obligatorio',
      );
    }

    const pauta = (pautas.get(a.id) ?? []).filter((p) => !dateOrNull(p.deleted_at)).at(-1);
    stat(R, 'dic_aula_pautas').skipped += (pautas.get(a.id) ?? []).length - (pauta ? 1 : 0);
    let guidelines = '';
    if (pauta) {
      guidelines = htmlToText(pauta.texto ?? '');
      const lost = htmlLosses(pauta.texto ?? '');
      if (lost.length)
        anomaly(
          R,
          'dic_aula_pautas',
          pauta.id,
          'needs human review',
          `pautas con ${lost.map((t) => `<${t}>`).join(', ')}: se pierden al pasar a texto`,
        );
      if ((pauta.url_fichero ?? '').trim())
        anomaly(
          R,
          'dic_aula_pautas',
          pauta.id,
          'needs human review',
          'fichero adjunto a las pautas: no se migra',
        );
      stat(R, 'dic_aula_pautas').mapped++;
    }
    const cv = commentsVisibilityOf(a.comentarios_visibles, a.comentarios_visibles_anteriores_a);
    if (cv.problem) anomaly(R, 'dic_aula', a.id, 'needs rule', cv.problem);

    const settings = {
      schoolYear: a.ano_ini_curso_escolar,
      validityYears,
      timeless: perm.length > 0,
      dictionaryTypeId: await masterRef(
        'mst_tipos_diccionario_aula',
        a.mst_tipo_dic_id,
        'dictionary_type',
        'dic_aula',
        a.id,
      ),
      educationStageId: await masterRef(
        'mst_ensenanzas',
        a.mst_ensenanza_id,
        'education_stage',
        'dic_aula',
        a.id,
      ),
      studyLevelId: await masterRef(
        'mst_nivel_estudios',
        a.mst_nivel_estudios_id,
        'study_level',
        'dic_aula',
        a.id,
      ),
      subjectId: await masterRef(
        'mst_areas_materias',
        a.mst_area_materia_id,
        'subject',
        'dic_aula',
        a.id,
      ),
      groupLabel: groupLabelOf(a.letra_grupo),
      maxSenses,
      visibleFields: SENSE_FIELD_CODES.filter((f) => visible.has(f)),
      requiredFields: SENSE_FIELD_CODES.filter((f) => required.has(f)),
      guidelines,
      visibleToStudents: a.visible_estudiante === '1',
      submissionsEnabled: a.envios_habilitados === '1' || a.envios_habilitados === '2',
      submissionsStartAt: dateOrNull(a.envios_fecha_ini),
      submissionsEndAt: null, // legacy never reads envios_fecha_fin (DATA_OBJECTS K.1)
      commentsVisibility: cv.visibility,
      commentsVisibleBefore: cv.before,
    };
    await db
      .insert(classroomSettings)
      .values({ dictionaryId, joinCode, ...settings })
      .onConflictDoUpdate({
        target: classroomSettings.dictionaryId,
        set: { joinCode, ...settings },
      });
    stat(R, 'dic_aula').mapped++;
  }

  const members = new Map<
    string,
    {
      dictionaryId: string;
      userId: string;
      role: MemberRole;
      active: boolean;
      legacySource: string;
      legacyId: number;
    }
  >();
  for (const p of L.participantes) {
    if (dateOrNull(p.deleted_at)) {
      stat(R, 'dic_aula_participantes').skipped++;
      continue;
    }
    const dictionaryId = classroomOfAula.get(p.dic_aula_id);
    const userId = userOfPersona.get(p.persona_id);
    if (!dictionaryId || !userId) {
      orphan(
        R,
        'dic_aula_participantes',
        p.id,
        'cannot migrate',
        `aula ${p.dic_aula_id} o persona ${p.persona_id} no migradas`,
      );
      continue;
    }
    const role = memberRoleOf(roleName.get(p.rol_diccionario_id));
    if (!role) {
      stat(R, 'dic_aula_participantes').invalid++;
      anomaly(
        R,
        'dic_aula_participantes',
        p.id,
        'needs human review',
        `rol de aula «${roleName.get(p.rol_diccionario_id) ?? p.rol_diccionario_id}» desconocido: no se migra`,
      );
      continue;
    }
    const key = `${dictionaryId}|${userId}`;
    const prev = members.get(key);
    if (prev) {
      prev.role = prev.role === 'teacher' || role === 'teacher' ? 'teacher' : 'student';
      prev.active ||= p.estado === '1';
      anomaly(
        R,
        'dic_aula_participantes',
        p.id,
        'repairable automatically',
        `participante repetido (también ${prev.legacyId}): se fusiona`,
      );
    } else
      members.set(key, {
        dictionaryId,
        userId,
        role,
        active: p.estado === '1',
        legacySource: 'dic_aula_participantes',
        legacyId: p.id,
      });
    stat(R, 'dic_aula_participantes').mapped++;
  }
  for (const [aulaId, dictionaryId] of classroomOfAula) {
    const userId = ownerOfAula.get(aulaId)!;
    const m = members.get(`${dictionaryId}|${userId}`);
    if (!m)
      members.set(`${dictionaryId}|${userId}`, {
        dictionaryId,
        userId,
        role: 'teacher',
        active: true,
        legacySource: 'dic_aula',
        legacyId: aulaId,
      });
    else if (m.role !== 'teacher' || !m.active) {
      anomaly(
        R,
        'dic_aula_participantes',
        m.legacyId,
        'repairable automatically',
        'el propietario del aula figuraba como alumno o deshabilitado: pasa a profesor activo',
      );
      m.role = 'teacher';
      m.active = true;
    }
  }
  for (const m of members.values())
    await db
      .insert(dictionaryMemberships)
      .values(m)
      .onConflictDoUpdate({
        target: [dictionaryMemberships.dictionaryId, dictionaryMemberships.userId],
        set: { role: m.role, active: m.active, legacySource: m.legacySource, legacyId: m.legacyId },
      });
  const isTeacher = (dictionaryId: string, userId: string) =>
    members.get(`${dictionaryId}|${userId}`)?.role === 'teacher';

  // ── 12-14. Submissions, immutable snapshots, published classroom entries ─────────────────────────────
  const envioById = byId(L.dpEnvios);
  const dicPersonalById = byId(L.dicPersonal);
  const dpEntradaById = byId<DpEntrada>(L.dpEntradas);
  const eeSenses = groupBy(L.enviosAcepciones, (a) => a.entrada_id);
  const eeTopics = groupBy(L.enviosTematicas, (t) => t.acepcion_id);
  const eeMedia = groupBy(L.enviosMedios, (m) => m.acepcion_id);
  const daeByEe = groupBy(L.dicAulaEntradas, (d) => d.envio_entrada_id);
  // Only the latest pending envío per (personal entry, classroom) stays pending (new unique rule).
  const latestPending = new Map<string, number>();
  for (const ee of L.enviosEntradas) {
    const envio = envioById.get(ee.dp_envio_id);
    if (!envio) continue;
    const own = (daeByEe.get(ee.id) ?? []).some((d) => d.dic_aula_id === envio.dic_aula_id);
    if (submissionStatusOf(ee.estado, ee.deleted_at, own) === 'pending')
      latestPending.set(`${ee.dp_entrada_id}|${envio.dic_aula_id}`, ee.id);
  }
  interface Sub {
    id: string;
    classroomId: string;
    aulaId: number;
    submitterId: string;
    editorId: string | null;
    senses: SenseRow[];
    headword: string;
    deletedAt: Date | null;
    hiddenByState: boolean;
  }
  const subOfEe = new Map<number, Sub>();
  const revisionNo = new Map<string, number>();
  const placeholderOfDp = new Map<number, string>();
  for (const ee of L.enviosEntradas) {
    const envio = envioById.get(ee.dp_envio_id);
    const classroomId = envio && classroomOfAula.get(envio.dic_aula_id);
    const dp = envio && dicPersonalById.get(envio.dic_personal_id);
    const submitterId = dp && userOfPersona.get(dp.persona_id);
    if (!envio || !classroomId || !dp || !submitterId) {
      orphan(
        R,
        'envios_entradas',
        ee.id,
        'cannot migrate',
        `sin envío (${ee.dp_envio_id}), aula o alumno migrados`,
      );
      continue;
    }
    let sourceEntryId = entryOfDpEntrada.get(ee.dp_entrada_id);
    if (!sourceEntryId) {
      // Orphan: the personal entry is gone. Keep the submission (and its publication) on a deleted placeholder.
      const dictionaryId = dictOfDicPersonal.get(envio.dic_personal_id);
      if (!dictionaryId || dpEntradaById.has(ee.dp_entrada_id)) {
        orphan(
          R,
          'envios_entradas',
          ee.id,
          'cannot migrate',
          `entrada personal ${ee.dp_entrada_id} no migrada`,
        );
        continue;
      }
      sourceEntryId =
        placeholderOfDp.get(ee.dp_entrada_id) ??
        (await upsertEntry({
          dictionaryId,
          headword: ee.entrada,
          hidden: false,
          deletedAt: dateOrNull(ee.created_at) ?? now,
          createdBy: submitterId,
          updatedBy: submitterId,
          createdAt: dateOrNull(ee.created_at),
          updatedAt: dateOrNull(ee.created_at),
          sourceSubmissionId: null,
          legacySource: 'envios_entradas',
          legacyId: ee.id,
        }));
      placeholderOfDp.set(ee.dp_entrada_id, sourceEntryId);
      orphan(
        R,
        'envios_entradas',
        ee.id,
        'repairable automatically',
        `dp_entrada ${ee.dp_entrada_id} inexistente: se crea una entrada personal borrada como origen del envío`,
      );
    }
    const senses = await senseRows(
      'envios_acepciones',
      eeSenses.get(ee.id) ?? [],
      eeTopics,
      eeMedia,
      submitterId,
      ee.id,
    );
    const snapshot: EntrySnapshot = buildSnapshot(normalizeText(ee.entrada), senses);
    const daes = daeByEe.get(ee.id) ?? [];
    const own = daes.find((d) => d.dic_aula_id === envio.dic_aula_id);
    let status = submissionStatusOf(ee.estado, ee.deleted_at, !!own);
    if (
      status === 'pending' &&
      latestPending.get(`${ee.dp_entrada_id}|${envio.dic_aula_id}`) !== ee.id
    ) {
      status = 'withdrawn';
      anomaly(
        R,
        'envios_entradas',
        ee.id,
        'repairable automatically',
        'otro envío pendiente más reciente de la misma entrada al mismo aula: este pasa a «withdrawn»',
      );
    }
    if (ee.estado === '3' && !own)
      anomaly(
        R,
        'envios_entradas',
        ee.id,
        'needs rule',
        'estado publicado sin dic_aula_entradas en su aula: se migra como «withdrawn»',
      );
    const editorId = ee.updated_by_persona_id
      ? (userOfPersona.get(ee.updated_by_persona_id) ?? null)
      : null;
    const number = (revisionNo.get(sourceEntryId) ?? 0) + 1;
    revisionNo.set(sourceEntryId, number);
    const submittedAt = dateOrNull(ee.created_at) ?? dateOrNull(envio.created_at) ?? now;
    const [rev] = await db
      .insert(entryRevisions)
      .values({
        entryId: sourceEntryId,
        number,
        reason: 'submit',
        snapshot,
        createdBy: submitterId,
        createdAt: submittedAt,
      })
      .onConflictDoUpdate({
        target: [entryRevisions.entryId, entryRevisions.number],
        set: { reason: 'submit', snapshot, createdBy: submitterId, createdAt: submittedAt },
      })
      .returning({ id: entryRevisions.id });
    const values = {
      classroomId,
      sourceEntryId,
      submittedBy: submitterId,
      revisionId: rev!.id,
      status,
      submittedAt,
      reviewedAt: own ? (dateOrNull(own.created_at) ?? submittedAt) : null,
      reviewedBy: own ? (editorId ?? ownerOfAula.get(envio.dic_aula_id)!) : null,
      publishedEntryId: null,
    };
    const [sub] = await db
      .insert(submissions)
      .values({ ...values, legacySource: 'envios_entradas', legacyId: ee.id })
      .onConflictDoUpdate({ target: [submissions.legacySource, submissions.legacyId], set: values })
      .returning({ id: submissions.id });
    subOfEe.set(ee.id, {
      id: sub!.id,
      classroomId,
      aulaId: envio.dic_aula_id,
      submitterId,
      editorId,
      senses,
      headword: ee.entrada,
      deletedAt:
        dateOrNull(ee.deleted_at) ??
        (ee.estado === '0' ? (dateOrNull(ee.updated_at) ?? now) : null),
      hiddenByState: ee.estado === '2',
    });
    stat(R, 'envios_entradas').mapped++;
  }
  stat(R, 'dp_envios').mapped = new Set(
    L.enviosEntradas.filter((ee) => subOfEe.has(ee.id)).map((ee) => ee.dp_envio_id),
  ).size;

  const firstDaeOfEe = new Set<number>();
  for (const dae of L.dicAulaEntradas) {
    const s = subOfEe.get(dae.envio_entrada_id);
    const dictionaryId = classroomOfAula.get(dae.dic_aula_id);
    if (!s || !dictionaryId) {
      orphan(
        R,
        'dic_aula_entradas',
        dae.id,
        'cannot migrate',
        `envío ${dae.envio_entrada_id} o aula ${dae.dic_aula_id} no migrados`,
      );
      continue;
    }
    const primary = !firstDaeOfEe.has(dae.envio_entrada_id);
    firstDaeOfEe.add(dae.envio_entrada_id);
    let deletedAt = dateOrNull(dae.deleted_at) ?? deletedAula.get(dae.dic_aula_id) ?? s.deletedAt;
    if (!deletedAt && !claimHeadword(dictionaryId, s.headword, 'dic_aula_entradas', dae.id))
      deletedAt = now;
    const editor = s.editorId ?? ownerOfAula.get(dae.dic_aula_id)!;
    const entryId = await upsertEntry({
      dictionaryId,
      headword: s.headword,
      hidden: dae.estado !== '1' || s.hiddenByState,
      deletedAt,
      createdBy: s.submitterId,
      updatedBy: editor,
      createdAt: dateOrNull(dae.created_at),
      updatedAt: dateOrNull(dae.updated_at),
      sourceSubmissionId: s.id,
      legacySource: 'dic_aula_entradas',
      legacyId: dae.id,
    });
    await writeSenses(entryId, s.senses, primary ? 'envios_acepciones' : null);
    await db
      .insert(entryRevisions)
      .values({
        entryId,
        number: 1,
        reason: 'publish',
        snapshot: buildSnapshot(normalizeText(s.headword), s.senses),
        createdBy: editor,
        createdAt: dateOrNull(dae.created_at) ?? now,
      })
      .onConflictDoUpdate({
        target: [entryRevisions.entryId, entryRevisions.number],
        set: {
          reason: 'publish',
          snapshot: buildSnapshot(normalizeText(s.headword), s.senses),
          createdBy: editor,
        },
      });
    if (dae.dic_aula_id === s.aulaId)
      await db
        .update(submissions)
        .set({ publishedEntryId: entryId })
        .where(sql`${submissions.id} = ${s.id} and ${submissions.publishedEntryId} is null`);
    stat(R, 'dic_aula_entradas').mapped++;
  }

  // ── 15. Comments (HTML → plain text) ─────────────────────────────────────────────────────────────────
  for (const c of L.comentariosGenerales) {
    const classroomId = classroomOfAula.get(c.dic_aula_id);
    const dp = c.dic_personal_id !== null ? dicPersonalById.get(c.dic_personal_id) : undefined;
    const studentId = dp && userOfPersona.get(dp.persona_id);
    const authorId = userOfPersona.get(c.persona_id);
    if (!classroomId || !studentId || !authorId) {
      orphan(
        R,
        'comentarios_generales',
        c.id,
        'cannot migrate',
        'aula, alumno o autor no migrados',
      );
      continue;
    }
    await upsertComment('comentarios_generales', c.id, classroomId, authorId, studentId, null, c);
  }
  for (const c of L.comentariosEntradas) {
    const s = c.envio_entrada_id !== null ? subOfEe.get(c.envio_entrada_id) : undefined;
    if (!s) {
      orphan(
        R,
        'comentarios_entradas',
        c.id,
        'cannot migrate',
        `envío ${c.envio_entrada_id} no migrado`,
      );
      continue;
    }
    const classroomId = classroomOfAula.get(c.dic_aula_id) ?? s.classroomId;
    if (classroomId !== s.classroomId)
      anomaly(
        R,
        'comentarios_entradas',
        c.id,
        'needs human review',
        'dic_aula_id distinto del aula del envío: se usa el del comentario',
      );
    // Legacy bug (FUNCTIONAL_INVENTORY §0 #10): persona_id stores a users.id.
    let authorId = userOfLegacyUser.get(c.persona_id);
    const asPersona = userOfPersona.get(c.persona_id);
    if (!authorId && asPersona) {
      authorId = asPersona;
      anomaly(
        R,
        'comentarios_entradas',
        c.id,
        'needs human review',
        'persona_id no es un users.id: se interpreta como persona',
      );
    } else if (
      authorId &&
      !isTeacher(classroomId, authorId) &&
      asPersona &&
      isTeacher(classroomId, asPersona)
    )
      anomaly(
        R,
        'comentarios_entradas',
        c.id,
        'needs human review',
        'autor ambiguo: como users.id no es profesor del aula, como persona sí',
      );
    if (!authorId) {
      orphan(
        R,
        'comentarios_entradas',
        c.id,
        'cannot migrate',
        `autor ${c.persona_id} no encontrado ni como usuario ni como persona`,
      );
      continue;
    }
    await upsertComment(
      'comentarios_entradas',
      c.id,
      classroomId,
      authorId,
      s.submitterId,
      s.id,
      c,
    );
  }
  async function upsertComment(
    table: string,
    legacyId: number,
    classroomId: string,
    authorId: string,
    studentId: string,
    submissionId: string | null,
    c: {
      comentario: string;
      fecha_envio: Date | null;
      estado: string | null;
      created_at: Date | null;
      updated_at: Date | null;
      deleted_at: Date | null;
    },
  ): Promise<void> {
    const body = htmlToText(c.comentario ?? '');
    if (!body) {
      stat(R, table).invalid++;
      anomaly(R, table, legacyId, 'cannot migrate', 'comentario vacío tras quitar el HTML');
      return;
    }
    const lost = htmlLosses(c.comentario);
    if (lost.length)
      anomaly(
        R,
        table,
        legacyId,
        'needs human review',
        `comentario con ${lost.map((t) => `<${t}>`).join(', ')}: se pierde al pasar a texto`,
      );
    if (body.length > 2000)
      anomaly(
        R,
        table,
        legacyId,
        'needs human review',
        `comentario de ${body.length} caracteres (la app admite 2000): se conserva completo`,
      );
    let deletedAt = dateOrNull(c.deleted_at);
    if (!deletedAt && c.estado === '0') {
      deletedAt = dateOrNull(c.updated_at) ?? now;
      anomaly(
        R,
        table,
        legacyId,
        'needs rule',
        'comentario «no visible» (estado 0): se migra como borrado',
      );
    }
    const values = {
      classroomId,
      authorId,
      studentId,
      submissionId,
      body,
      createdAt: dateOrNull(c.fecha_envio) ?? dateOrNull(c.created_at) ?? now,
      deletedAt,
    };
    await db
      .insert(comments)
      .values({ ...values, legacySource: table, legacyId })
      .onConflictDoUpdate({ target: [comments.legacySource, comments.legacyId], set: values });
    stat(R, table).mapped++;
  }

  // ── Master guidelines (mst_pautas, first active row) ──────────────────────────────────────────────────
  const mp = L.mstPautas.find((p) => p.estado === 1 && !dateOrNull(p.deleted_at));
  if (mp) {
    const value = htmlToText(mp.texto ?? '');
    await db
      .insert(appSettings)
      .values({ key: 'master_guidelines', value })
      .onConflictDoUpdate({ target: appSettings.key, set: { value } });
    stat(R, 'mst_pautas').mapped = 1;
  }
  stat(R, 'mst_pautas').skipped = L.mstPautas.length - (mp ? 1 : 0);
  stat(R, 'roles').mapped = L.roles.length; // used by name, not migrated as rows

  // ── Unreferenced files ───────────────────────────────────────────────────────────────────────────────
  for (const { dir } of Object.values(LEGACY_MEDIA)) {
    const folder = join(opts.mediaSource, 'dp', 'medios', dir);
    for (const f of await readdir(folder).catch(() => [] as string[])) {
      if (/_thumb\.jpg$/i.test(f)) R.media.thumbnailsIgnored++;
      else if (!referenced.has(join(folder, f))) {
        R.media.unreferenced++;
        anomaly(
          R,
          'media',
          null,
          'needs human review',
          `fichero sin referencia: dp/medios/${dir}/${f}`,
        );
      }
    }
  }

  // ── Counts in the target and verification ────────────────────────────────────────────────────────────
  for (const [table, target] of Object.entries(TARGET_OF))
    stat(R, table).new = await scalar(
      db,
      sql`select count(*)::int as n from ${sql.identifier(target)} where legacy_source = ${table}`,
    );
  await verify(db, R, opts);
}

async function verify(db: Db, R: Report, opts: MigrateOptions): Promise<void> {
  const check = async (name: string, q: SQL, detail: (n: number) => string) => {
    const n = await scalar(db, q);
    R.verification.push({ check: name, ok: n === 0, detail: detail(n) });
  };
  await check(
    'orden de acepciones contiguo (1..n)',
    sql`select count(*)::int as n from (select entry_id from entry_senses group by entry_id
        having min(position) <> 1 or max(position) <> count(*) or count(distinct position) <> count(*)) x`,
    (n) => `${n} entradas con huecos o repetidos`,
  );
  await check(
    'cada envío publicado tiene entrada de aula',
    sql`select count(*)::int as n from submissions s left join entries e on e.id = s.published_entry_id
        where s.status = 'published' and (e.id is null or e.source_submission_id is null)`,
    (n) => `${n} envíos publicados sin entrada`,
  );
  await check(
    'entradas de aula migradas con procedencia',
    sql`select count(*)::int as n from entries where legacy_source = 'dic_aula_entradas' and source_submission_id is null`,
    (n) => `${n} entradas sin source_submission_id`,
  );
  await check(
    'origen del envío en el diccionario personal del alumno',
    sql`select count(*)::int as n from submissions s join entries e on e.id = s.source_entry_id
        join dictionaries d on d.id = e.dictionary_id where d.kind <> 'personal' or d.owner_id <> s.submitted_by`,
    (n) => `${n} envíos con origen incoherente`,
  );
  await check(
    'alumnos que enviaron son miembros del aula',
    sql`select count(distinct (s.classroom_id, s.submitted_by))::int as n from submissions s
        left join dictionary_memberships m on m.dictionary_id = s.classroom_id and m.user_id = s.submitted_by where m.user_id is null`,
    (n) =>
      `${n} pares (aula, alumno) con envíos y sin pertenencia (alumno dado de baja en el legacy; revisar)`,
  );
  await check(
    'comentarios de envío dirigidos a quien envió',
    sql`select count(*)::int as n from comments c join submissions s on s.id = c.submission_id where c.student_id <> s.submitted_by`,
    (n) => `${n} comentarios incoherentes`,
  );
  await check(
    'aulas con al menos un profesor activo',
    sql`select count(*)::int as n from dictionaries d where d.kind = 'classroom' and not exists
        (select 1 from dictionary_memberships m where m.dictionary_id = d.id and m.role = 'teacher' and m.active)`,
    (n) => `${n} aulas sin profesor`,
  );
  await check(
    'una acepción tiene como mucho un medio por tipo',
    sql`select count(*)::int as n from (select sense_id, kind from sense_media group by sense_id, kind having count(*) > 1) x`,
    (n) => `${n} duplicados`,
  );
  await check(
    'sin columnas de identificadores personales (NIF/NIE, CIAL, pasaporte)',
    sql`select count(*)::int as n from information_schema.columns where table_schema = 'public'
        and (column_name ilike '%nif%' or column_name ilike '%nie' or column_name ilike '%cial%' or column_name ilike '%pasaporte%' or column_name ilike '%passport%')`,
    (n) => `${n} columnas sospechosas`,
  );
  if (!opts.dryRun) {
    let bad = 0;
    const rows = await db
      .select({ key: mediaAssets.storageKey, sha: mediaAssets.sha256 })
      .from(mediaAssets)
      .where(sql`${mediaAssets.legacySource} is not null`);
    for (const r of rows) {
      const bytes = await readFile(join(opts.mediaTarget, r.key.slice(0, 2), r.key)).catch(
        () => null,
      );
      if (!bytes || createHash('sha256').update(bytes).digest('hex') !== r.sha) bad++;
    }
    R.verification.push({
      check: 'medios copiados con checksum correcto',
      ok: bad === 0,
      detail: `${rows.length - bad}/${rows.length} ficheros verificados`,
    });
  }
}
