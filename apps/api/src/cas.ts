import { XMLParser } from 'fast-xml-parser';

/**
 * Minimal CAS 3.0 client (TECH_RESEARCH §4): no CAS npm package, just fetch + fast-xml-parser.
 * fast-xml-parser never resolves external entities; we also refuse any DTD so entity-expansion bugs can't apply.
 */

const parser = new XMLParser({
  removeNSPrefix: true,
  ignoreAttributes: false,
  attributeNamePrefix: '@_',
  trimValues: true,
  parseTagValue: false,
  isArray: (name) => name === 'InfoCentro',
});

type Xml = Record<string, unknown>;

export function parseXmlSafe(xml: string): Xml {
  if (/<!DOCTYPE|<!ENTITY/i.test(xml)) throw new Error('XML with DTD is not allowed');
  return parser.parse(xml) as Xml;
}

const obj = (v: unknown): Xml | undefined => (v && typeof v === 'object' ? (v as Xml) : undefined);

export type CasResult = { ok: true; user: string } | { ok: false; code: string };

export function parseServiceResponse(xml: string): CasResult {
  const r = obj(parseXmlSafe(xml).serviceResponse);
  const success = obj(r?.authenticationSuccess);
  const user = success?.user;
  if (typeof user === 'string' && user.trim()) return { ok: true, user: user.trim() };
  const code = obj(r?.authenticationFailure)?.['@_code'];
  return { ok: false, code: typeof code === 'string' ? code : 'INVALID_RESPONSE' };
}

export async function validateTicket(
  casBaseUrl: string,
  service: string,
  ticket: string,
): Promise<CasResult> {
  const url = new URL(`${casBaseUrl}/p3/serviceValidate`);
  url.search = new URLSearchParams({ service, ticket }).toString();
  const res = await fetch(url, { signal: AbortSignal.timeout(5000), redirect: 'error' });
  if (!res.ok) return { ok: false, code: `HTTP_${res.status}` };
  return parseServiceResponse(await res.text());
}

/** SessionIndex (the service ticket) of a back-channel `samlp:LogoutRequest`, or null. */
export function parseLogoutRequest(xml: string): string | null {
  const idx = obj(parseXmlSafe(xml).LogoutRequest)?.SessionIndex;
  return typeof idx === 'string' && idx.length > 0 && idx.length <= 512 ? idx : null;
}
