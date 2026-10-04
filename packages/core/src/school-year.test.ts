import { describe, expect, it } from 'vitest';
import {
  formatSchoolYear,
  isClassroomCurrent,
  lastCurrentSchoolYear,
  schoolYearOf,
  submissionWindowState,
  validityToReactivate,
} from './school-year.ts';

const d = (s: string) => new Date(`${s}T12:00:00`);

describe('RULE-005 school year from start day', () => {
  it.each([
    ['2023-08-29', 2022],
    ['2023-08-30', 2023],
    ['2024-01-15', 2023],
    ['2023-12-31', 2023],
  ])('%s belongs to %i', (date, year) => expect(schoolYearOf(d(date))).toBe(year));

  it('honours a custom start day', () => {
    expect(schoolYearOf(d('2023-09-01'), { startMonth: 9, startDay: 2 })).toBe(2022);
  });
});

describe('RULE-006 classroom validity', () => {
  const c = { schoolYear: 2022, validityYears: 3, timeless: false };
  it('is current while elapsed < validity', () => {
    expect(isClassroomCurrent(c, d('2024-10-01'))).toBe(true);
    expect(isClassroomCurrent(c, d('2025-10-01'))).toBe(false);
  });
  it('validity 1 means the start year only', () => {
    const one = { ...c, validityYears: 1 };
    expect(isClassroomCurrent(one, d('2023-08-29'))).toBe(true);
    expect(isClassroomCurrent(one, d('2023-08-30'))).toBe(false);
  });
  it('timeless is always current', () => {
    expect(isClassroomCurrent({ ...c, timeless: true }, d('2040-01-01'))).toBe(true);
  });
});

describe('RULE-007 reactivation is capped', () => {
  it('computes the validity needed for the current year', () => {
    expect(validityToReactivate(2022, d('2024-10-01'))).toBe(3);
  });
  it('refuses beyond ten school years', () => {
    expect(validityToReactivate(2010, d('2024-10-01'))).toBeNull();
  });
});

describe('RULE-009 submission window', () => {
  const base = {
    schoolYear: 2024,
    validityYears: 1,
    timeless: false,
    submissionsEnabled: true,
    submissionsStartAt: null,
    submissionsEndAt: null,
  };
  const now = d('2024-11-10');
  it('is open when enabled without dates', () =>
    expect(submissionWindowState(base, now)).toBe('open'));
  it('is disabled when switched off', () =>
    expect(submissionWindowState({ ...base, submissionsEnabled: false }, now)).toBe('disabled'));
  it('respects start and end', () => {
    expect(submissionWindowState({ ...base, submissionsStartAt: d('2024-12-01') }, now)).toBe(
      'not_started',
    );
    expect(submissionWindowState({ ...base, submissionsEndAt: d('2024-11-01') }, now)).toBe(
      'ended',
    );
  });
  it('closes when the classroom expired', () =>
    expect(submissionWindowState(base, d('2025-09-10'))).toBe('expired'));
});

describe('school year helpers', () => {
  it('formats a school year label', () => expect(formatSchoolYear(2024)).toBe('2024-2025'));
  it('last current school year is start + validity - 1, none when timeless', () => {
    expect(lastCurrentSchoolYear({ schoolYear: 2022, validityYears: 3, timeless: false })).toBe(
      2024,
    );
    expect(
      lastCurrentSchoolYear({ schoolYear: 2022, validityYears: 3, timeless: true }),
    ).toBeNull();
  });
  it('reactivating a classroom of a future school year needs at least one year', () => {
    expect(validityToReactivate(2026, d('2024-10-01'))).toBe(1);
  });
});
