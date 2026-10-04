import type { DictionaryView, EntryView, VocabularyValue } from './types.ts';

/** Client-side exports of a dictionary (brief §5, inventory §12). Pure functions over `exportDictionary` output. */

const vocabMap = (vocab: VocabularyValue[]) => new Map(vocab.map((v) => [v.id, v]));
const labelOf = (m: Map<string, VocabularyValue>, id: string | null) => (id ? (m.get(id)?.label ?? '') : '');

export const CSV_HEADER = [
  'Entrada',
  'Acepción',
  'Categoría gramatical',
  'Género',
  'Número',
  'Definición',
  'Más datos',
  'Ejemplo de uso',
  'Lengua',
  'Palabra en otra lengua',
  'Temáticas',
] as const;

/** RFC 4180 field: quoted when it holds a comma, quote or line break; quotes doubled. */
const csvField = (s: string) => (/[",\r\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s);

/** One row per sense, CRLF line endings (RFC 4180). */
export function toCsv(entries: EntryView[], vocab: VocabularyValue[]): string {
  const m = vocabMap(vocab);
  const rows: string[][] = [[...CSV_HEADER]];
  for (const e of entries)
    e.senses.forEach((s, i) =>
      rows.push([
        e.headword,
        String(i + 1),
        labelOf(m, s.partOfSpeechId),
        labelOf(m, s.genderId),
        labelOf(m, s.numberId),
        s.definition,
        s.extraInfo,
        s.example,
        labelOf(m, s.languageId),
        s.foreignForm,
        s.topicIds.map((t) => labelOf(m, t)).join('; '),
      ]),
    );
  return rows.map((r) => r.map(csvField).join(',')).join('\r\n') + '\r\n';
}

/** Stable structured export: vocabulary references resolved to {code, label}; no internal ids except entry ids. */
export function toJson(dictionary: DictionaryView, entries: EntryView[], vocab: VocabularyValue[]) {
  const m = vocabMap(vocab);
  const ref = (id: string | null) => {
    const v = id ? m.get(id) : undefined;
    return v ? { code: v.code, label: v.label } : null;
  };
  return {
    format: 'lexican-export',
    version: 1,
    dictionary: {
      title: dictionary.title,
      description: dictionary.description,
      kind: dictionary.kind,
      schoolYear: dictionary.classroom?.schoolYear ?? null,
    },
    entries: entries.map((e) => ({
      id: e.id,
      headword: e.headword,
      hidden: e.hidden,
      author: e.author?.displayName ?? null,
      updatedAt: e.updatedAt,
      senses: e.senses.map((s) => ({
        definition: s.definition,
        extraInfo: s.extraInfo,
        example: s.example,
        partOfSpeech: ref(s.partOfSpeechId),
        gender: ref(s.genderId),
        number: ref(s.numberId),
        language: ref(s.languageId),
        foreignForm: s.foreignForm,
        hidden: s.hidden,
        topics: s.topicIds.map(ref).filter((t) => t !== null),
        media: s.media.map((md) => ({ kind: md.kind, mime: md.mime, name: md.originalName })),
      })),
    })),
  };
}

/* ---------- OASIS DMLex 1.0, JSON serialization (core + controlled values), see docs/DMLEX-MAPPING.md ---------- */

interface DmlexTag {
  tag: string;
  description?: string;
  typeTag?: string;
}
export interface DmlexSense {
  id: string;
  labels?: string[];
  definitions?: { text: string; definitionType?: string }[];
  examples?: { text: string }[];
}
export interface DmlexEntry {
  id: string;
  headword: string;
  partsOfSpeech?: string[];
  senses: DmlexSense[];
}
export interface DmlexResource {
  title: string;
  langCode: 'es';
  entries: DmlexEntry[];
  partOfSpeechTags?: DmlexTag[];
  labelTags?: DmlexTag[];
  labelTypeTags?: DmlexTag[];
  definitionTypeTags?: DmlexTag[];
}

const LABEL_TYPES = { gender: 'género', number: 'número', topic: 'temática' } as const;
export const EXTRA_INFO_DEFINITION_TYPE = 'mas-datos';

/**
 * Monolingual DMLex lexicographic resource. Parts of speech move up to the entry (DMLex has them only there);
 * gender, number and topics become typed sense labels; "más datos" becomes a second, typed definition.
 * Lost: other-language forms (crosslingual module not used), media, hidden flags (caller filters).
 */
export function toDmlex(dictionary: DictionaryView, entries: EntryView[], vocab: VocabularyValue[]): DmlexResource {
  const m = vocabMap(vocab);
  const pos = new Map<string, DmlexTag>();
  const labels = new Map<string, DmlexTag>();
  const usedTypes = new Set<keyof typeof LABEL_TYPES>();
  let usedExtra = false;

  const label = (id: string | null): string[] => {
    const v = id ? m.get(id) : undefined;
    if (!v || v.code === 'no_tiene') return [];
    const type = v.vocabulary as keyof typeof LABEL_TYPES;
    const tag = `${v.vocabulary}.${v.code}`;
    if (!labels.has(tag)) labels.set(tag, { tag, description: v.label, typeTag: type });
    usedTypes.add(type);
    return [tag];
  };

  const out: DmlexEntry[] = entries.map((e) => {
    const entryPos: string[] = [];
    const senses = e.senses.map((s): DmlexSense => {
      const p = s.partOfSpeechId ? m.get(s.partOfSpeechId) : undefined;
      if (p && p.code !== 'no_tiene') {
        if (!pos.has(p.code)) pos.set(p.code, { tag: p.code, description: p.label });
        if (!entryPos.includes(p.code)) entryPos.push(p.code);
      }
      const sl = [...label(s.genderId), ...label(s.numberId), ...s.topicIds.flatMap(label)];
      const definitions: NonNullable<DmlexSense['definitions']> = [{ text: s.definition }];
      if (s.extraInfo) {
        definitions.push({ text: s.extraInfo, definitionType: EXTRA_INFO_DEFINITION_TYPE });
        usedExtra = true;
      }
      return {
        id: s.id,
        ...(sl.length ? { labels: [...new Set(sl)] } : {}),
        definitions,
        ...(s.example ? { examples: [{ text: s.example }] } : {}),
      };
    });
    return { id: e.id, headword: e.headword, ...(entryPos.length ? { partsOfSpeech: entryPos } : {}), senses };
  });

  return {
    title: dictionary.title,
    langCode: 'es',
    entries: out,
    ...(pos.size ? { partOfSpeechTags: [...pos.values()] } : {}),
    ...(labels.size ? { labelTags: [...labels.values()] } : {}),
    ...(usedTypes.size
      ? { labelTypeTags: [...usedTypes].map((t) => ({ tag: t, description: LABEL_TYPES[t] })) }
      : {}),
    ...(usedExtra ? { definitionTypeTags: [{ tag: EXTRA_INFO_DEFINITION_TYPE, description: 'Más datos' }] } : {}),
  };
}
