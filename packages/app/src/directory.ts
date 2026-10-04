import { DEMO_ACCOUNTS, DEMO_SCHOOL, DomainError } from '@lexican/core';
import type { InstitutionalProfile } from './auth.ts';

/** Resolves a validated CAS subject to a profile: CAUCE in production, fixtures with the test CAS. */
export interface InstitutionalDirectory {
  lookup(subject: string): Promise<InstitutionalProfile>;
}

export const NOT_AUTHORIZED = 'Tu usuario no está autorizado en LexiCán.';

/**
 * Fixed profiles for the public test CAS (local Docker and the Pages demo): `alice` → the demo teacher, `bob` → a
 * demo student, anyone else refused. Never grants admin: roles come from the directory mapping (teacher/student).
 */
export function testDirectory(): InstitutionalDirectory {
  return {
    async lookup(subject) {
      const a = DEMO_ACCOUNTS.find((x) => 'casSubject' in x && x.casSubject === subject);
      if (!a) throw new DomainError('forbidden', NOT_AUTHORIZED);
      return {
        subject,
        firstName: a.firstName,
        lastName: a.lastName,
        email: null,
        // CAUCE role 1 = teacher (RULE-063); 3 = student.
        schools: [{ ...DEMO_SCHOOL, role: a.role === 'teacher' ? '1' : '3' }],
      };
    },
  };
}
