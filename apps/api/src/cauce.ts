import {
  NOT_AUTHORIZED,
  type InstitutionalDirectory,
  type InstitutionalProfile,
} from '@lexican/app';
import { DomainError } from '@lexican/core';
import { parseXmlSafe } from '@lexican/http';

export type { InstitutionalDirectory };

/* Institutional directory (CAUCE) lookup for a CAS subject (FUNCTIONAL_INVENTORY §11, prompt §39). Server only. */

/** Legacy placeholder school for directory rows without a centre code. */
const NO_SCHOOL = { code: '00000000', name: 'Sin centro educativo' };

/** Legacy CAUCE responses may carry HTML named entities (&aacute;) that XML does not define (§11 TODO). */
const HTML_ENTITIES: Record<string, string> = {
  aacute: 'á',
  eacute: 'é',
  iacute: 'í',
  oacute: 'ó',
  uacute: 'ú',
  ntilde: 'ñ',
  uuml: 'ü',
  ccedil: 'ç',
  Aacute: 'Á',
  Eacute: 'É',
  Iacute: 'Í',
  Oacute: 'Ó',
  Uacute: 'Ú',
  Ntilde: 'Ñ',
  Uuml: 'Ü',
  Ccedil: 'Ç',
  ordf: 'ª',
  ordm: 'º',
  nbsp: ' ',
};
const text = (v: unknown): string =>
  typeof v === 'string'
    ? v.replace(/&([A-Za-z]+);/g, (m, n: string) => HTML_ENTITIES[n] ?? m).trim()
    : '';
const rec = (v: unknown): Record<string, unknown> =>
  v && typeof v === 'object' ? (v as Record<string, unknown>) : {};

/**
 * Map a `CheckUsuarioAutorizadoResponse` to a profile. Only names, optional email and schools are read:
 * NifNie, Pasaporte and CIAL are deliberately ignored and never leave this function (§21/§39).
 */
export function parseCauceResponse(subject: string, xml: string): InstitutionalProfile {
  const root = rec(parseXmlSafe(xml).CheckUsuarioAutorizadoResponse);
  if (text(root.MensajeError)) throw new DomainError('forbidden', NOT_AUTHORIZED);
  const info = rec(rec(root.Usuario).InfoUsuario);
  const firstName = text(info.Nombre);
  const lastName = text(info.Apellidos);
  if (!firstName && !lastName) throw new DomainError('forbidden', NOT_AUTHORIZED);
  const centres = rec(root.Centro).InfoCentro;
  const schools = (Array.isArray(centres) ? centres : []).map((c) => {
    const r = rec(c);
    const code = text(r.Codigo);
    return code
      ? { code: code.slice(0, 20), name: text(r.Nombre).slice(0, 255) || code, role: text(r.Rol) }
      : { ...NO_SCHOOL, role: text(r.Rol) };
  });
  const email = text(info.Email ?? info.CorreoElectronico);
  return {
    subject,
    firstName,
    lastName,
    email: /^[^@\s]+@[^@\s]+$/.test(email) ? email : null,
    schools,
  };
}

export function httpDirectory(cfg: {
  url: string;
  token: string;
  timeoutMs?: number;
}): InstitutionalDirectory {
  return {
    async lookup(subject) {
      let res: Response;
      try {
        // TLS is verified (default fetch); the legacy disabled it.
        res = await fetch(`${cfg.url}${encodeURIComponent(subject)}`, {
          headers: { authorization: `Bearer ${cfg.token}`, accept: 'application/xml' },
          signal: AbortSignal.timeout(cfg.timeoutMs ?? 5000),
          redirect: 'error',
        });
      } catch {
        throw new DomainError(
          'unavailable',
          'El directorio institucional no responde. Inténtalo más tarde.',
        );
      }
      if (!res.ok)
        throw new DomainError(
          'unavailable',
          'El directorio institucional no responde. Inténtalo más tarde.',
        );
      try {
        return parseCauceResponse(subject, await res.text());
      } catch (e) {
        if (e instanceof DomainError) throw e;
        throw new DomainError('forbidden', NOT_AUTHORIZED);
      }
    },
  };
}

/** Fixed profiles for tests and local development (no network). */
export function fakeDirectory(
  profiles: Record<string, Omit<InstitutionalProfile, 'subject'>>,
): InstitutionalDirectory {
  return {
    async lookup(subject) {
      const p = profiles[subject];
      if (!p) throw new DomainError('forbidden', NOT_AUTHORIZED);
      return { subject, ...p };
    },
  };
}
