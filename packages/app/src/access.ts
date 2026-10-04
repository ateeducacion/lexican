import { DomainError, isClassroomCurrent, type MemberRole } from '@lexican/core';
import { classroomSettings, dictionaries, dictionaryMemberships, type Db } from '@lexican/db';
import { and, eq, isNull } from 'drizzle-orm';
import { notFound, requireUser, type Actor, type Deps } from './context.ts';

export type DictionaryRow = typeof dictionaries.$inferSelect;
export type SettingsRow = typeof classroomSettings.$inferSelect;

export interface DictionaryAccess {
  dict: DictionaryRow;
  settings: SettingsRow | null;
  /** Active membership role in a classroom, or null. */
  role: MemberRole | null;
  isOwner: boolean;
  /** Can browse entries (hidden ones excluded unless `canEdit`). */
  canRead: boolean;
  /** Personal owner, or active teacher of the classroom. */
  canEdit: boolean;
  current: boolean;
}

/** Central authorization for dictionaries: every service goes through here (fixes RULE-041/053/217). */
export async function dictionaryAccess(deps: Deps, actor: Actor | null, dictionaryId: string): Promise<DictionaryAccess> {
  const a = requireUser(actor);
  const [row] = await deps.db
    .select({ dict: dictionaries, settings: classroomSettings })
    .from(dictionaries)
    .leftJoin(classroomSettings, eq(classroomSettings.dictionaryId, dictionaries.id))
    .where(and(eq(dictionaries.id, dictionaryId), isNull(dictionaries.deletedAt)));
  if (!row) throw notFound('El diccionario');
  const isOwner = row.dict.ownerId === a.userId;
  const staff = a.globalRole === 'admin' || a.globalRole === 'support';

  if (row.dict.kind === 'personal') {
    return { ...row, role: null, isOwner, canRead: isOwner, canEdit: isOwner, current: true };
  }
  const role = await memberRole(deps.db, dictionaryId, a.userId);
  const settings = row.settings!;
  const current = isClassroomCurrent(settings, deps.clock(), deps.schoolYear);
  const canEdit = role === 'teacher';
  const canRead = canEdit || staff || (role === 'student' && settings.visibleToStudents);
  return { ...row, role, isOwner, canRead, canEdit, current };
}

export async function memberRole(db: Db, dictionaryId: string, userId: string): Promise<MemberRole | null> {
  const [m] = await db
    .select({ role: dictionaryMemberships.role, active: dictionaryMemberships.active })
    .from(dictionaryMemberships)
    .where(and(eq(dictionaryMemberships.dictionaryId, dictionaryId), eq(dictionaryMemberships.userId, userId)));
  return m?.active ? m.role : null;
}

export function assertReadable(acc: DictionaryAccess): void {
  if (!acc.canRead) {
    if (acc.role === 'student') throw new DomainError('forbidden', 'El profesorado aún no ha hecho visible este diccionario.');
    throw notFound('El diccionario');
  }
}

/** Teacher of a classroom (or owner of a personal dictionary); classroom must be current for writes. */
export function assertEditable(acc: DictionaryAccess, { requireCurrent = true } = {}): void {
  if (!acc.canEdit) throw new DomainError('forbidden', 'No tienes permiso para modificar este diccionario.');
  if (requireCurrent && !acc.current)
    throw new DomainError('forbidden', 'El diccionario de aula ya no está vigente: solo se puede consultar.');
}

export async function classroomAccess(deps: Deps, actor: Actor | null, classroomId: string): Promise<DictionaryAccess> {
  const acc = await dictionaryAccess(deps, actor, classroomId);
  if (acc.dict.kind !== 'classroom') throw notFound('El diccionario de aula');
  return acc;
}
