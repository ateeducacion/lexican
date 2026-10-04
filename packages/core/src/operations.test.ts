import { describe, expect, it } from 'vitest';
import { bindPath, operations } from './operations.ts';

describe('bindPath', () => {
  it('fills path params from the input and leaves the rest for query or body', () => {
    expect(
      bindPath(operations.updateMember.path, { classroomId: 'c1', userId: 'u1', role: 'teacher' }),
    ).toEqual({
      url: '/api/classrooms/c1/members/u1',
      rest: { role: 'teacher' },
    });
  });

  it('URL-encodes parameter values', () => {
    expect(bindPath('/api/entries/:entryId', { entryId: 'a/b?c' }).url).toBe(
      '/api/entries/a%2Fb%3Fc',
    );
  });

  it('returns the path untouched when it has no params', () => {
    expect(bindPath('/api/me', { a: 1 })).toEqual({ url: '/api/me', rest: { a: 1 } });
  });
});

describe('operation table', () => {
  it('declares every path param in the input schema', () => {
    for (const [name, op] of Object.entries(operations)) {
      for (const [, param] of op.path.matchAll(/:(\w+)/g)) {
        expect(
          Object.keys((op.input as unknown as { shape: object }).shape),
          `${name}.${param}`,
        ).toContain(param);
      }
    }
  });
});
