import { describe, expect, it } from 'vitest';
import { ClassroomInput, JoinInput, ListEntriesQuery } from './contracts.ts';
import { mediaKindOf } from './fields.ts';

it('parses query-string booleans correctly', () => {
  const id = '00000000-0000-4000-8000-000000000000';
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: 'false' }).includeHidden).toBe(
    false,
  );
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: 'true' }).includeHidden).toBe(
    true,
  );
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: false }).includeHidden).toBe(
    false,
  );
});

describe('ClassroomInput', () => {
  const issues = (input: object) =>
    ClassroomInput.safeParse({ title: 'Aula', ...input }).error?.issues.map((i) => [
      i.path.join('.'),
      i.message,
    ]) ?? [];

  it('accepts an end date equal to or after the start date', () => {
    expect(issues({ submissionsStartAt: '2025-10-01', submissionsEndAt: '2025-10-01' })).toEqual(
      [],
    );
    expect(issues({ submissionsStartAt: '2025-10-01' })).toEqual([]);
    expect(issues({ submissionsEndAt: '2025-10-01' })).toEqual([]);
  });

  it('refuses an end date before the start date', () => {
    expect(issues({ submissionsStartAt: '2025-10-02', submissionsEndAt: '2025-10-01' })).toEqual([
      ['submissionsEndAt', 'La fecha de fin debe ser posterior a la de inicio'],
    ]);
  });

  it('requires the limit date when comments are visible only before a date', () => {
    expect(issues({ commentsVisibility: 'before_date' })).toEqual([
      ['commentsVisibleBefore', 'Indica la fecha límite de los comentarios visibles'],
    ]);
    expect(
      issues({ commentsVisibility: 'before_date', commentsVisibleBefore: '2025-12-01' }),
    ).toEqual([]);
  });

  it('caps validity at ten school years', () => {
    expect(issues({ validityYears: 11 }).map(([p]) => p)).toEqual(['validityYears']);
  });
});

describe('JoinInput (SEC-002)', () => {
  it('normalizes case and refuses look-alike characters', () => {
    expect(JoinInput.parse({ code: ' abc234 ' }).code).toBe('ABC234');
    expect(JoinInput.safeParse({ code: 'ABC10O' }).success).toBe(false);
  });
});

describe('mediaKindOf', () => {
  it('classifies allowed MIME types and refuses the rest', () => {
    expect(mediaKindOf('image/webp')).toBe('image');
    expect(mediaKindOf('audio/x-wav')).toBe('audio');
    expect(mediaKindOf('video/webm')).toBe('video');
    expect(mediaKindOf('image/svg+xml')).toBeNull();
  });
});
