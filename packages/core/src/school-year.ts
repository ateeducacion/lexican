/**
 * School-year rules (legacy INICIO_CURSO / VIGENCIA_MAX), rebuilt as pure functions
 * with an injected date. Brief §5.1–5.4, RULE-005/006/007/009.
 */

export interface SchoolYearConfig {
  /** Day and month on which a school year starts (legacy default 30 August). */
  startMonth: number; // 1-12
  startDay: number; // 1-31
}

export const DEFAULT_SCHOOL_YEAR: SchoolYearConfig = { startMonth: 8, startDay: 30 };
export const MAX_VALIDITY_YEARS = 10;

/** Calendar year in which the school year containing `date` started. */
export function schoolYearOf(date: Date, cfg: SchoolYearConfig = DEFAULT_SCHOOL_YEAR): number {
  const y = date.getFullYear();
  const md = (date.getMonth() + 1) * 100 + date.getDate();
  return md >= cfg.startMonth * 100 + cfg.startDay ? y : y - 1;
}

/** "2024-2025" label used across the UI. */
export const formatSchoolYear = (startYear: number): string => `${startYear}-${startYear + 1}`;

export interface ValidityInput {
  schoolYear: number;
  validityYears: number;
  timeless: boolean;
}

/** A classroom is current while fewer than `validityYears` school years have elapsed, or forever if timeless. */
export function isClassroomCurrent(c: ValidityInput, now: Date, cfg?: SchoolYearConfig): boolean {
  if (c.timeless) return true;
  return schoolYearOf(now, cfg) - c.schoolYear < c.validityYears;
}

/** Last school year (start year) in which the classroom is current, or null when timeless. */
export const lastCurrentSchoolYear = (c: ValidityInput): number | null =>
  c.timeless ? null : c.schoolYear + c.validityYears - 1;

/**
 * Validity needed to keep a classroom current in the present school year (admin "reactivate").
 * Capped at MAX_VALIDITY_YEARS: returns null when it cannot be reactivated.
 */
export function validityToReactivate(
  schoolYear: number,
  now: Date,
  cfg?: SchoolYearConfig,
): number | null {
  const needed = schoolYearOf(now, cfg) - schoolYear + 1;
  return needed <= MAX_VALIDITY_YEARS ? Math.max(1, needed) : null;
}

export interface WindowInput extends ValidityInput {
  submissionsEnabled: boolean;
  submissionsStartAt: Date | null;
  submissionsEndAt: Date | null;
}

export type WindowState = 'open' | 'disabled' | 'not_started' | 'ended' | 'expired';

/** Whether students may submit now; applies to single and bulk submission alike (RULE-074 fixed). */
export function submissionWindowState(
  c: WindowInput,
  now: Date,
  cfg?: SchoolYearConfig,
): WindowState {
  if (!isClassroomCurrent(c, now, cfg)) return 'expired';
  if (!c.submissionsEnabled) return 'disabled';
  if (c.submissionsStartAt && now < c.submissionsStartAt) return 'not_started';
  if (c.submissionsEndAt && now > c.submissionsEndAt) return 'ended';
  return 'open';
}
