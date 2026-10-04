import { expect, it } from 'vitest';
import { ListEntriesQuery } from './contracts.ts';

it('parses query-string booleans correctly', () => {
  const id = '00000000-0000-4000-8000-000000000000';
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: 'false' }).includeHidden).toBe(false);
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: 'true' }).includeHidden).toBe(true);
  expect(ListEntriesQuery.parse({ dictionaryId: id, includeHidden: false }).includeHidden).toBe(false);
});
