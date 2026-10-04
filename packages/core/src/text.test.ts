import { expect, it } from 'vitest';
import { headwordKey, initialOf, normalizeText, sortKeyOf } from './text.ts';

it('RULE-094 keys are case-insensitive and accent-sensitive', () => {
  expect(headwordKey('  Árbol ')).toBe(headwordKey('árbol'));
  expect(headwordKey('arbol')).not.toBe(headwordKey('árbol'));
});

it('normalizes inner whitespace', () =>
  expect(normalizeText(' guagua   azul ')).toBe('guagua azul'));

it.each([
  ['árbol', 'A'],
  ['ñame', 'Ñ'],
  ['Ébano', 'E'],
  ['3D', '#'],
])('initial of %s is %s', (w, i) => expect(initialOf(w)).toBe(i));

it('sorts in Spanish order', () => {
  const words = ['ñame', 'nube', 'oso', 'árbol', 'Abeja', 'nudo'];
  const sorted = [...words].sort((a, b) => (sortKeyOf(a) < sortKeyOf(b) ? -1 : 1));
  expect(sorted).toEqual(['Abeja', 'árbol', 'nube', 'nudo', 'ñame', 'oso']);
});
