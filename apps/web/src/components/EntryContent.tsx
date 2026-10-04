import type { EntrySnapshot, EntryView, SenseField } from '@lexican/core';
import styles from './EntryContent.module.css';
import { Media } from './Media.tsx';
import { useVocab } from './vocab.ts';

type Sense = EntryView['senses'][number] | EntrySnapshot['senses'][number];

/**
 * Read-only rendering of an entry (viewer, review, print). `visibleFields` applies a classroom's field policy;
 * hidden senses are shown dimmed only when `showHidden` (teachers). `hideHeadword` lets a page render its own
 * headword heading above the senses.
 */
export function EntryContent({
  headword,
  senses,
  visibleFields,
  showHidden = false,
  headingLevel = 2,
  hideHeadword = false,
}: {
  headword: string;
  senses: Sense[];
  visibleFields?: SenseField[];
  showHidden?: boolean;
  headingLevel?: 1 | 2 | 3;
  hideHeadword?: boolean;
}) {
  const v = useVocab();
  const show = (f: SenseField) => !visibleFields || visibleFields.includes(f);
  const H = `h${headingLevel}` as 'h1' | 'h2' | 'h3';
  const list = senses.filter((s) => showHidden || !('hidden' in s && s.hidden));
  return (
    <article>
      {!hideHeadword && (
        <H className="headword" lang="es">
          {headword}
        </H>
      )}
      <ol className={`sense-list ${styles.list}`}>
        {list.map((s, i) => {
          const hidden = 'hidden' in s && s.hidden;
          const grammar = [
            show('part_of_speech') ? v.abbr(s.partOfSpeechId) : '',
            show('gender') ? v.abbr(s.genderId) : '',
            show('number') ? v.abbr(s.numberId) : '',
          ].filter(Boolean);
          const media = s.media.filter((m) => show(m.kind));
          return (
            <li
              key={'id' in s ? s.id : i}
              className={`${styles.sense} ${media.length ? styles.withMedia : ''} ${hidden ? styles.hidden : ''}`}
            >
              <span className={styles.num} aria-hidden="true">
                {i + 1}
              </span>
              <div className={styles.body}>
                {hidden && <span className="badge badge-hidden">Oculta para el alumnado</span>}
                <p className={styles.definition}>
                  {grammar.length > 0 && <em className="sense-abbr">{grammar.join(' ')}</em>}
                  {s.definition}
                </p>
                {show('example') && s.example && (
                  <p className={styles.example} lang="es">
                    «{s.example}»
                  </p>
                )}
                {show('extra_info') && s.extraInfo && (
                  <p className="small">
                    <strong>Más datos.</strong> {s.extraInfo}
                  </p>
                )}
                {show('language') && s.languageId && s.foreignForm && (
                  <p className="small">
                    <strong>{v.label(s.languageId)}:</strong>{' '}
                    <span lang="und">{s.foreignForm}</span>
                  </p>
                )}
                {show('topics') && s.topicIds.length > 0 && (
                  <p className="small muted">
                    Temáticas: {s.topicIds.map((t) => v.label(t)).join(', ')}
                  </p>
                )}
              </div>
              {media.length > 0 && (
                <div className={`sense-media ${styles.media}`}>
                  {media.map((m) => (
                    <Media
                      key={m.id}
                      media={m}
                      alt={`${headword}: ${m.kind === 'image' ? 'imagen' : m.kind}`}
                    />
                  ))}
                </div>
              )}
            </li>
          );
        })}
      </ol>
    </article>
  );
}
