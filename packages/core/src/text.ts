/** Trim and collapse inner whitespace (legacy `normalizaEspacios`). */
export const normalizeText = (s: string): string => s.trim().replace(/\s+/g, ' ');

/**
 * Uniqueness key for a headword: case-insensitive but accent-sensitive,
 * so "Árbol" and "árbol" collide while "arbol" and "árbol" do not (brief §5.11).
 */
export const headwordKey = (headword: string): string =>
  normalizeText(headword).toLocaleLowerCase('es');

/** Alphabet used by the "search by initial" bar (legacy A–N, Ñ, O–Z). */
export const ALPHABET = [...'ABCDEFGHIJKLMN', 'Ñ', ...'OPQRSTUVWXYZ'] as const;

/** Index letter of a headword: accents are dropped except for Ñ; non-letters go under '#'. */
export const initialOf = (headword: string): string => {
  const first = normalizeText(headword).charAt(0).toLocaleUpperCase('es');
  if (first === 'Ñ') return 'Ñ';
  const base = first.normalize('NFD').replace(/\p{M}/gu, '');
  return /^[A-Z]$/.test(base) ? base : '#';
};

/**
 * Byte-comparable sort key for Spanish order: accents ignored, ñ after n, case-insensitive.
 * Compared with COLLATE "C" so PGlite and Postgres order identically.
 */
export const sortKeyOf = (headword: string): string =>
  headwordKey(headword)
    .replace(/ñ/g, 'n\u{10FFFF}')
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .replace(/n\u{10FFFF}/gu, 'n~');
