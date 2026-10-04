import { Ajv2020 } from 'ajv/dist/2020.js';
import { describe, expect, it } from 'vitest';
import schema from './dmlex-1.0.schema.json' with { type: 'json' };
import { toCsv, toDmlex, toJson } from './export.ts';
import type { DictionaryView, EntryView, VocabularyValue } from './types.ts';

const v = (
  id: string,
  vocabulary: VocabularyValue['vocabulary'],
  code: string,
  label: string,
  abbreviation: string | null = null,
): VocabularyValue => ({
  id,
  vocabulary,
  code,
  label,
  abbreviation,
  position: 0,
  featured: false,
  active: true,
});
const vocab = [
  v('p1', 'part_of_speech', 'sustantivo', 'Sustantivo', 'sust.'),
  v('g1', 'gender', 'femenino', 'Femenino', 'f.'),
  v('n1', 'number', 'singular', 'Singular', 'sing.'),
  v('t1', 'topic', 'dialecto_canario', 'Dialecto canario'),
  v('l1', 'language', 'ingles', 'Inglés'),
];
const dictionary = {
  id: 'd1',
  kind: 'classroom',
  title: 'Canarismos',
  description: 'Aula',
  classroom: { schoolYear: 2026 },
} as DictionaryView;
const sense = {
  id: 's1',
  position: 0,
  definition: 'Autobús, "de línea", público.',
  extraInfo: 'Uso coloquial',
  example: 'Cogí la guagua.',
  partOfSpeechId: 'p1',
  genderId: 'g1',
  numberId: 'n1',
  languageId: 'l1',
  foreignForm: 'bus',
  hidden: false,
  topicIds: ['t1'],
  media: [],
};
const entries = [
  {
    id: 'e1',
    dictionaryId: 'd1',
    headword: 'guagua',
    initial: 'G',
    hidden: false,
    version: 1,
    createdAt: '',
    updatedAt: '2026-10-01T00:00:00.000Z',
    author: { id: 'u1', displayName: 'Daniel' },
    sourceSubmissionId: null,
    submissions: [],
    senses: [
      sense,
      {
        ...sense,
        id: 's2',
        definition: 'Bebé\nde pocos meses',
        extraInfo: '',
        example: '',
        partOfSpeechId: null,
        genderId: null,
        numberId: null,
        languageId: null,
        foreignForm: '',
        topicIds: [],
      },
    ],
  },
] as EntryView[];

describe('toCsv', () => {
  it('writes one RFC 4180 row per sense', () => {
    const csv = toCsv(entries, vocab);
    const lines = csv.split('\r\n');
    expect(lines[0]).toBe(
      'Entrada,Acepción,Categoría gramatical,Género,Número,Definición,Más datos,Ejemplo de uso,Lengua,Palabra en otra lengua,Temáticas',
    );
    expect(lines[1]).toBe(
      'guagua,1,Sustantivo,Femenino,Singular,"Autobús, ""de línea"", público.",Uso coloquial,Cogí la guagua.,Inglés,bus,Dialecto canario',
    );
    expect(csv).toContain('guagua,2,,,,"Bebé\nde pocos meses",,,,,\r\n');
    expect(csv.endsWith('\r\n')).toBe(true);
  });

  it('neutralizes spreadsheet formulas (CSV injection)', () => {
    const evil = ['=HYPERLINK("http://x")', '+1+1', '-2', '@SUM(A1)', '\tcmd', '\rcmd'];
    const csv = toCsv(
      evil.map((headword, i) => ({
        ...entries[0]!,
        id: `e${i}`,
        headword,
        senses: [entries[0]!.senses[1]!],
      })),
      vocab,
    );
    const firstCells = csv
      .split('\r\n')
      .slice(1, -1)
      .map((l) => l.split(',')[0]);
    expect(firstCells.slice(0, 4)).toEqual([
      '"\'=HYPERLINK(""http://x"")"',
      "'+1+1",
      "'-2",
      "'@SUM(A1)",
    ]);
    expect(csv).toContain("'\tcmd,1,");
    expect(csv).toContain('"\'\rcmd",1,');
    expect(toCsv(entries, vocab)).not.toContain("'guagua");
  });
});

describe('toJson', () => {
  it('resolves vocabulary to codes and labels', () => {
    const j = toJson(dictionary, entries, vocab);
    expect(j.format).toBe('lexican-export');
    expect(j.dictionary).toEqual({
      title: 'Canarismos',
      description: 'Aula',
      kind: 'classroom',
      schoolYear: 2026,
    });
    expect(j.entries[0]!.senses[0]!.partOfSpeech).toEqual({
      code: 'sustantivo',
      label: 'Sustantivo',
    });
    expect(j.entries[0]!.senses[0]!.topics).toEqual([
      { code: 'dialecto_canario', label: 'Dialecto canario' },
    ]);
  });
});

describe('toDmlex', () => {
  const d = toDmlex(dictionary, entries, vocab);

  it('maps entries, senses, definitions, examples and tags', () => {
    expect(d.langCode).toBe('es');
    expect(d.entries[0]).toEqual({
      id: 'e1',
      headword: 'guagua',
      partsOfSpeech: ['sustantivo'],
      senses: [
        {
          id: 's1',
          labels: ['gender.femenino', 'number.singular', 'topic.dialecto_canario'],
          definitions: [
            { text: 'Autobús, "de línea", público.' },
            { text: 'Uso coloquial', definitionType: 'mas-datos' },
          ],
          examples: [{ text: 'Cogí la guagua.' }],
        },
        { id: 's2', definitions: [{ text: 'Bebé\nde pocos meses' }] },
      ],
    });
    expect(d.partOfSpeechTags).toEqual([{ tag: 'sustantivo', description: 'Sustantivo' }]);
    expect(d.labelTags?.map((t) => t.tag)).toEqual([
      'gender.femenino',
      'number.singular',
      'topic.dialecto_canario',
    ]);
    expect(d.labelTypeTags?.map((t) => t.tag)).toEqual(['gender', 'number', 'topic']);
  });

  it('validates against the official OASIS DMLex 1.0 JSON schema', () => {
    const validate = new Ajv2020({ strict: false, validateFormats: false }).compile(schema);
    expect(validate(d), JSON.stringify(validate.errors)).toBe(true);
  });
});
