import { DomainError, type GlobalRole, type ParsedInput, type UserView } from '@lexican/core';
import { authIdentities, type Db, schools, userSchools, users } from '@lexican/db';
import { and, eq, sql } from 'drizzle-orm';
import { type Actor, audit, type Deps, requireUser } from './context.ts';
import { toUserView } from './views.ts';

const ITERATIONS = 100_000;
const b64 = (u: Uint8Array) => btoa(String.fromCharCode(...u));
const unb64 = (s: string) => Uint8Array.from(atob(s), (c) => c.charCodeAt(0));

async function pbkdf2(password: string, salt: Uint8Array, iterations: number): Promise<Uint8Array> {
  const key = await crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(password),
    'PBKDF2',
    false,
    ['deriveBits'],
  );
  const bits = await crypto.subtle.deriveBits(
    { name: 'PBKDF2', hash: 'SHA-256', salt: salt as BufferSource, iterations },
    key,
    256,
  );
  return new Uint8Array(bits);
}

/** PBKDF2-SHA256 via WebCrypto, so the same code runs in Node and in the browser demo. */
export async function hashPassword(password: string): Promise<string> {
  const salt = crypto.getRandomValues(new Uint8Array(16));
  return `pbkdf2$${ITERATIONS}$${b64(salt)}$${b64(await pbkdf2(password, salt, ITERATIONS))}`;
}

export async function verifyPassword(password: string, stored: string): Promise<boolean> {
  const [scheme, iter, salt, hash] = stored.split('$');
  if (scheme !== 'pbkdf2' || !iter || !salt || !hash) return false;
  const got = await pbkdf2(password, unb64(salt), Number(iter));
  const want = unb64(hash);
  return got.length === want.length && got.every((b, i) => b === want[i]);
}

/** Result of the institutional directory (CAUCE) for a CAS subject; no national ids are kept (§21). */
export interface InstitutionalProfile {
  subject: string;
  firstName: string;
  lastName: string;
  email: string | null;
  schools: { code: string; name: string; role: string }[];
}

export type CasProvider = 'cas' | 'cas_test';

/** CAUCE roles 1 (public school teacher), 4 (teacher-centre staff), 5 (education technician) → teacher (RULE-063). */
export const TEACHER_ROLE_CODES = new Set(['1', '4', '5']);
export const roleFromDirectory = (p: InstitutionalProfile): GlobalRole =>
  p.schools.some((s) => TEACHER_ROLE_CODES.has(s.role)) ? 'teacher' : 'student';

export async function createPasswordUser(
  db: Db,
  u: {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    globalRole: GlobalRole;
    avatar?: string;
  },
): Promise<string> {
  const [row] = await db
    .insert(users)
    .values({
      email: u.email,
      firstName: u.firstName,
      lastName: u.lastName,
      displayName: `${u.firstName} ${u.lastName}`.trim(),
      globalRole: u.globalRole,
      avatar: u.avatar ?? 'default',
    })
    .returning({ id: users.id });
  await db.insert(authIdentities).values({
    userId: row!.id,
    provider: 'password',
    subject: u.email.toLowerCase(),
    secretHash: await hashPassword(u.password),
  });
  return row!.id;
}

export function authServices(deps: Deps) {
  const { db } = deps;

  async function userView(id: string): Promise<UserView> {
    const [u] = await db.select().from(users).where(eq(users.id, id));
    if (!u) throw new DomainError('unauthenticated', 'Tu sesión ha caducado. Vuelve a entrar.');
    return toUserView(u);
  }

  return {
    userView,

    async me(actor: Actor | null): Promise<{ user: UserView | null }> {
      return { user: actor ? await userView(actor.userId).catch(() => null) : null };
    },

    /** Password login: demo accounts and the dev/test provider only (production uses CAS). */
    async login(
      _actor: Actor | null,
      { email, password }: ParsedInput<'login'>,
    ): Promise<UserView> {
      const [row] = await db
        .select({ u: users, hash: authIdentities.secretHash, identityId: authIdentities.id })
        .from(authIdentities)
        .innerJoin(users, eq(users.id, authIdentities.userId))
        .where(and(eq(authIdentities.provider, 'password'), eq(authIdentities.subject, email)));
      const ok = row?.hash ? await verifyPassword(password, row.hash) : false;
      if (!row || !ok || row.u.status !== 'active')
        throw new DomainError('unauthenticated', 'Correo o contraseña incorrectos.');
      await db
        .update(authIdentities)
        .set({ lastLoginAt: deps.clock() })
        .where(eq(authIdentities.id, row.identityId));
      await audit(
        db,
        { userId: row.u.id, globalRole: row.u.globalRole },
        'auth.login',
        'user',
        row.u.id,
        { provider: 'password' },
      );
      return toUserView(row.u);
    },

    async logout(actor: Actor | null) {
      if (actor) await audit(db, actor, 'auth.logout', 'user', actor.userId);
      return { ok: true as const };
    },

    /**
     * CAS sign-in: create or update the user and recompute the global role on every login. `provider` is the issuer:
     * identities of the test CAS (`cas_test`) and the institutional one (`cas`) never link, even with equal subjects.
     * Disabled accounts are refused.
     */
    async signInInstitutional(
      profile: InstitutionalProfile,
      provider: CasProvider = 'cas',
    ): Promise<UserView> {
      const userId = await db.transaction(async (tx) => {
        const displayName = `${profile.firstName} ${profile.lastName}`.trim() || profile.subject;
        const [identity] = await tx
          .select()
          .from(authIdentities)
          .where(
            and(eq(authIdentities.provider, provider), eq(authIdentities.subject, profile.subject)),
          );
        let id = identity?.userId;
        if (id) {
          const [u] = await tx.select({ status: users.status }).from(users).where(eq(users.id, id));
          if (u?.status !== 'active')
            throw new DomainError('forbidden', 'Tu cuenta está desactivada en LexiCán.');
        }
        const directoryRole = roleFromDirectory(profile);
        if (id) {
          await tx
            .update(users)
            .set({
              firstName: profile.firstName,
              lastName: profile.lastName,
              displayName,
              // Local grants (admin/support) survive; teacher/student follow the directory.
              globalRole: sql`case when ${users.globalRole} in ('admin','support') then ${users.globalRole} else ${directoryRole}::global_role end`,
              updatedAt: new Date(),
            })
            .where(eq(users.id, id));
          await tx
            .update(authIdentities)
            .set({ lastLoginAt: deps.clock() })
            .where(eq(authIdentities.id, identity!.id));
        } else {
          const values = {
            firstName: profile.firstName,
            lastName: profile.lastName,
            displayName,
            globalRole: directoryRole,
          };
          // An email already used by another account must not block institutional sign-in (SEC-005): store none.
          let [u] = await tx
            .insert(users)
            .values({ ...values, email: profile.email })
            .onConflictDoNothing()
            .returning({ id: users.id });
          if (!u)
            [u] = await tx
              .insert(users)
              .values({ ...values, email: null })
              .returning({ id: users.id });
          id = u!.id;
          await tx
            .insert(authIdentities)
            .values({ userId: id, provider, subject: profile.subject, lastLoginAt: deps.clock() });
        }
        await tx.delete(userSchools).where(eq(userSchools.userId, id));
        for (const s of profile.schools) {
          const [school] = await tx
            .insert(schools)
            .values({ code: s.code, name: s.name })
            .onConflictDoUpdate({ target: schools.code, set: { name: s.name } })
            .returning({ id: schools.id });
          await tx
            .insert(userSchools)
            .values({ userId: id, schoolId: school!.id, roleCode: s.role })
            .onConflictDoNothing();
        }
        return id;
      });
      const view = await userView(userId);
      await audit(db, { userId, globalRole: view.globalRole }, 'auth.login', 'user', userId, {
        provider,
      });
      return view;
    },

    async actorFor(userId: string): Promise<Actor | null> {
      const [u] = await db
        .select({ id: users.id, role: users.globalRole, status: users.status })
        .from(users)
        .where(eq(users.id, userId));
      return u && u.status === 'active' ? { userId: u.id, globalRole: u.role } : null;
    },

    requireUser,
  };
}
