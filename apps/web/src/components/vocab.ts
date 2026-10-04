import type { Vocabulary, VocabularyValue } from '@lexican/core';
import { useSession } from '../session.ts';

/** Lookup helpers over the controlled vocabularies loaded at the root. */
export function useVocab() {
  const { vocab } = useSession();
  const byId = new Map(vocab.map((v) => [v.id, v]));
  return {
    byId,
    label: (id: string | null | undefined) => (id ? (byId.get(id)?.label ?? '') : ''),
    /** Dictionary abbreviation ("sust.", "f."); "No tiene" is not printed (legacy behaviour). */
    abbr: (id: string | null | undefined) => {
      const val = id ? byId.get(id) : undefined;
      return !val || val.code === 'no_tiene' ? '' : (val.abbreviation ?? val.label);
    },
    /** Active values of a vocabulary, featured first (legacy leading-space trick, now explicit). */
    list: (vocabulary: Vocabulary): VocabularyValue[] =>
      vocab
        .filter((v) => v.vocabulary === vocabulary && v.active)
        .sort(
          (a, b) =>
            Number(b.featured) - Number(a.featured) ||
            a.position - b.position ||
            a.label.localeCompare(b.label, 'es'),
        ),
  };
}
