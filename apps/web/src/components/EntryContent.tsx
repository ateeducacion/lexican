import type { EntrySnapshot, EntryView, SenseField } from '@lexican/core';
import { Media } from './Media.tsx';
import { useVocab } from './vocab.ts';

type Sense = EntryView['senses'][number] | EntrySnapshot['senses'][number];

/**
 * Read-only rendering of an entry (viewer, review, print). `visibleFields` applies a classroom's field policy;
 * hidden senses are shown dimmed only when `showHidden` (teachers).
 */
export function EntryContent({
  headword,
  senses,
  visibleFields,
  showHidden = false,
  headingLevel = 2,
}: {
  headword: string;
  senses: Sense[];
  visibleFields?: SenseField[];
  showHidden?: boolean;
  headingLevel?: 1 | 2 | 3;
}) {
  const v = useVocab();
  const show = (f: SenseField) => !visibleFields || visibleFields.includes(f);
  const H = `h${headingLevel}` as 'h1' | 'h2' | 'h3';
  const list = senses.filter((s) => showHidden || !('hidden' in s && s.hidden));
  return (
    <article>
      <H className="headword" lang="es">
        {headword}
      </H>
      <ol className="sense-list">
        {list.map((s, i) => {
          const hidden = 'hidden' in s && s.hidden;
          const grammar = [
            show('part_of_speech') ? v.abbr(s.partOfSpeechId) : '',
            show('gender') ? v.abbr(s.genderId) : '',
            show('number') ? v.abbr(s.numberId) : '',
          ].filter(Boolean);
          return (
            <li key={'id' in s ? s.id : i} className={`sense${hidden ? ' sense-hidden' : ''}`}>
              <div>
                {hidden && <span className="badge badge-hidden">Oculta para el alumnado</span>}{' '}
                {grammar.length > 0 && <span className="sense-abbr">{grammar.join(' ')}</span>}
                <span>{s.definition}</span>
                {show('extra_info') && s.extraInfo && (
                  <p className="small">
                    <strong>Más datos.</strong> {s.extraInfo}
                  </p>
                )}
                {show('example') && s.example && (
                  <p className="small">
                    <em>«{s.example}»</em>
                  </p>
                )}
                {show('language') && s.languageId && s.foreignForm && (
                  <p className="small">
                    <strong>{v.label(s.languageId)}:</strong> <span lang="und">{s.foreignForm}</span>
                  </p>
                )}
                {show('topics') && s.topicIds.length > 0 && (
                  <p className="small muted">Temáticas: {s.topicIds.map((t) => v.label(t)).join(', ')}</p>
                )}
                {s.media.some((m) => show(m.kind)) && (
                  <div className="sense-media">
                    {s.media
                      .filter((m) => show(m.kind))
                      .map((m) => (
                        <Media key={m.id} media={m} alt={`${headword}: ${m.kind === 'image' ? 'imagen' : m.kind}`} />
                      ))}
                  </div>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </article>
  );
}
