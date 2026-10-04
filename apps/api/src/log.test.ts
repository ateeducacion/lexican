import { afterEach, expect, it, vi } from 'vitest';
import { createLogger } from './log.ts';

afterEach(() => vi.restoreAllMocks());

it('writes one JSON line per event at or above the level, nothing when silent', () => {
  const err = vi.spyOn(console, 'error').mockImplementation(() => undefined);
  const log = createLogger('warn');
  log.info({ a: 1 }, 'hidden');
  log.warn({ code: 'x' }, 'shown');
  log.error({}, 'also shown');
  expect(err).toHaveBeenCalledTimes(2);
  expect(JSON.parse(err.mock.calls[0]![0] as string)).toMatchObject({
    level: 'warn',
    msg: 'shown',
    code: 'x',
  });
  createLogger('silent').error({}, 'never');
  expect(err).toHaveBeenCalledTimes(2);
});
