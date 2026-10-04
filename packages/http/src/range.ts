/**
 * Single byte range of an RFC 9110 `Range` header for a resource of `size` bytes.
 * - `null`: no usable range → serve the whole resource (200). Covers absent, malformed and multi-range headers
 *   (servers may ignore Range; multipart/byteranges is not implemented).
 * - `'unsatisfiable'`: 416, whose Content-Range is an asterisk followed by the size.
 */
export function parseRange(
  header: string | null | undefined,
  size: number,
): { start: number; end: number } | 'unsatisfiable' | null {
  const m = header?.trim().match(/^bytes=(\d*)-(\d*)$/i);
  if (!m) return null;
  const [, a, b] = m as unknown as [string, string, string];
  if (a === '' && b === '') return null;
  if (a === '') {
    const n = Number(b);
    if (n === 0 || size === 0) return 'unsatisfiable';
    return { start: Math.max(0, size - n), end: size - 1 };
  }
  const start = Number(a);
  if (b !== '' && Number(b) < start) return null;
  if (start >= size) return 'unsatisfiable';
  const end = b === '' ? size - 1 : Number(b);
  return { start, end: Math.min(end, size - 1) };
}

/** Response for a stored Blob honouring `Range` (GET and HEAD); headers common to all three outcomes come in `headers`. */
export function blobResponse(
  blob: Blob,
  rangeHeader: string | null | undefined,
  headers: Record<string, string>,
  ifRange = false,
): Response {
  const size = blob.size;
  const h = new Headers({ ...headers, 'accept-ranges': 'bytes' });
  // If-Range without validators (no ETag/Last-Modified here) can never match: send the whole representation.
  const r = ifRange ? null : parseRange(rangeHeader, size);
  if (r === 'unsatisfiable') {
    h.set('content-range', `bytes */${size}`);
    return new Response(null, { status: 416, headers: h });
  }
  if (!r) {
    h.set('content-length', String(size));
    return new Response(blob, { status: 200, headers: h });
  }
  h.set('content-range', `bytes ${r.start}-${r.end}/${size}`);
  h.set('content-length', String(r.end - r.start + 1));
  return new Response(blob.slice(r.start, r.end + 1), { status: 206, headers: h });
}
