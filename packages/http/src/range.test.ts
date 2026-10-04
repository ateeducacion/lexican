import { describe, expect, it } from 'vitest';
import { blobResponse, parseRange } from './range.ts';

describe('byte ranges', () => {
  it('parse single ranges, open and suffix ranges; ignore malformed and multi-range headers', () => {
    expect(parseRange('bytes=0-3', 10)).toEqual({ start: 0, end: 3 });
    expect(parseRange('bytes=4-', 10)).toEqual({ start: 4, end: 9 });
    expect(parseRange('bytes=-3', 10)).toEqual({ start: 7, end: 9 });
    expect(parseRange('bytes=-30', 10)).toEqual({ start: 0, end: 9 });
    expect(parseRange('bytes=8-100', 10)).toEqual({ start: 8, end: 9 });
    expect(parseRange('bytes=10-', 10)).toBe('unsatisfiable');
    expect(parseRange('bytes=-0', 10)).toBe('unsatisfiable');
    expect(parseRange('bytes=0-', 0)).toBe('unsatisfiable');
    for (const h of [
      undefined,
      '',
      'bytes=5-2',
      'items=0-1',
      'bytes=0-1,4-5',
      'bytes=-',
      'bytes=a-b',
    ])
      expect(parseRange(h, 10), String(h)).toBeNull();
  });

  it('answer 200, 206 and 416 with the right headers', async () => {
    const blob = new Blob(['0123456789']);
    const full = blobResponse(blob, null, { 'content-type': 'audio/ogg' });
    expect(full.status).toBe(200);
    expect(Object.fromEntries(full.headers)).toMatchObject({
      'accept-ranges': 'bytes',
      'content-length': '10',
      'content-type': 'audio/ogg',
    });
    const part = blobResponse(blob, 'bytes=2-4', {});
    expect(part.status).toBe(206);
    expect(part.headers.get('content-range')).toBe('bytes 2-4/10');
    expect(part.headers.get('content-length')).toBe('3');
    expect(await part.text()).toBe('234');
    const none = blobResponse(blob, 'bytes=20-', {});
    expect(none.status).toBe(416);
    expect(none.headers.get('content-range')).toBe('bytes */10');
    expect(blobResponse(blob, 'bytes=2-4', {}, true).status).toBe(200);
  });
});
