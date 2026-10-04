import {
  formatSchoolYear,
  toCsv,
  toDmlex,
  toJson,
  type DictionaryView,
  type EntryView,
} from '@lexican/core';
import { useMemo, useState } from 'react';
import { Link, useLoaderData, useSearchParams, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../api/index.ts';
import { EntryContent } from '../components/EntryContent.tsx';
import { EmptyState, PageTitle, formatDate } from '../components/ui.tsx';
import { useVocab } from '../components/vocab.ts';
import { useSession } from '../session.ts';
import styles from './PrintPage.module.css';

interface Data {
  dictionary: DictionaryView;
  entries: EntryView[];
}

export async function loader({ params, request }: LoaderFunctionArgs): Promise<Data> {
  const includeHidden = new URL(request.url).searchParams.get('ocultas') === '1';
  return (await getApi()).exportDictionary({ dictionaryId: params.dictionaryId!, includeHidden });
}

const slug = (s: string) =>
  s
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || 'diccionario';

function download(name: string, content: string, type: string) {
  const url = URL.createObjectURL(new Blob([content], { type }));
  const a = document.createElement('a');
  a.href = url;
  a.download = name;
  a.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export function Component() {
  const { dictionary: d, entries } = useLoaderData<Data>();
  const { vocab } = useSession();
  const v = useVocab();
  const [params, setParams] = useSearchParams();
  const includeHidden = params.get('ocultas') === '1';
  const canEdit = d.kind === 'personal' || d.myRole === 'teacher';
  const [byTopic, setByTopic] = useState(false);
  const [topics, setTopics] = useState<Set<string>>(new Set());

  const usedTopics = useMemo(() => {
    const ids = new Set(entries.flatMap((e) => e.senses.flatMap((s) => s.topicIds)));
    return [...ids]
      .map((id) => ({ id, label: v.label(id) }))
      .sort((a, b) => a.label.localeCompare(b.label, 'es'));
  }, [entries, v]);

  const shown = useMemo(() => {
    if (!byTopic) return entries;
    return entries
      .map((e) => ({ ...e, senses: e.senses.filter((s) => s.topicIds.some((t) => topics.has(t))) }))
      .filter((e) => e.senses.length > 0);
  }, [entries, byTopic, topics]);

  const groups = useMemo(() => {
    const g = new Map<string, EntryView[]>();
    for (const e of shown) g.set(e.initial, [...(g.get(e.initial) ?? []), e]);
    return [...g.entries()];
  }, [shown]);

  const authors =
    d.kind === 'classroom'
      ? [...new Set(shown.map((e) => e.author?.displayName).filter((x): x is string => !!x))].sort(
          (a, b) => a.localeCompare(b, 'es'),
        )
      : [];
  const file = slug(d.title);
  const back = d.kind === 'personal' ? '/mi-diccionario' : `/aulas/${d.id}`;

  return (
    <>
      <div className="no-print">
        <div className={styles.options}>
          <p className="small">
            <Link to={back}>← {d.title}</Link>
          </p>
        </div>
        <section className={`card ${styles.options}`} aria-labelledby="options-title">
          <p className="eyebrow" style={{ margin: 0 }}>
            Exportar
          </p>
          <h2 id="options-title">Imprimir o exportar</h2>
          {canEdit && (
            <label className="check">
              <input
                type="checkbox"
                checked={includeHidden}
                onChange={(e) =>
                  setParams(
                    (p) => {
                      if (e.target.checked) p.set('ocultas', '1');
                      else p.delete('ocultas');
                      return p;
                    },
                    { replace: true },
                  )
                }
              />
              <span>Incluir entradas y acepciones ocultas</span>
            </label>
          )}
          {usedTopics.length > 0 && (
            <>
              <label className="check">
                <input
                  type="checkbox"
                  checked={byTopic}
                  onChange={(e) => setByTopic(e.target.checked)}
                />
                <span>Solo las temáticas seleccionadas</span>
              </label>
              {byTopic && (
                <fieldset>
                  <legend>Temáticas</legend>
                  <div className={styles.topics}>
                    {usedTopics.map((t) => (
                      <label key={t.id} className="check">
                        <input
                          type="checkbox"
                          checked={topics.has(t.id)}
                          onChange={(e) =>
                            setTopics((s) => {
                              const n = new Set(s);
                              if (e.target.checked) n.add(t.id);
                              else n.delete(t.id);
                              return n;
                            })
                          }
                        />
                        <span>{t.label}</span>
                      </label>
                    ))}
                  </div>
                </fieldset>
              )}
            </>
          )}
          <p className="small muted" role="status">
            {shown.length} {shown.length === 1 ? 'entrada' : 'entradas'}{' '}
            {shown.length !== entries.length && `de ${entries.length}`}
          </p>
          <div className="row">
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => window.print()}
              disabled={shown.length === 0}
            >
              Imprimir / Guardar como PDF
            </button>
            <button
              type="button"
              className="btn"
              onClick={() =>
                download(`${file}.csv`, '﻿' + toCsv(shown, vocab), 'text/csv;charset=utf-8')
              }
            >
              CSV
            </button>
            <button
              type="button"
              className="btn"
              onClick={() =>
                download(
                  `${file}.json`,
                  JSON.stringify(toJson(d, shown, vocab), null, 2),
                  'application/json',
                )
              }
            >
              JSON
            </button>
            <button
              type="button"
              className="btn"
              onClick={() =>
                download(
                  `${file}.dmlex.json`,
                  JSON.stringify(toDmlex(d, shown, vocab), null, 2),
                  'application/json',
                )
              }
            >
              DMLex JSON
            </button>
          </div>
          <p className="small muted">
            En el cuadro de impresión elige «Guardar como PDF» para obtener un fichero.
          </p>
        </section>
        <h2 className={styles.previewTitle}>Vista previa</h2>
      </div>

      <div className={styles.sheet}>
        <header className={styles.cover}>
          <PageTitle title={`Imprimir: ${d.title}`}>{d.title}</PageTitle>
          {d.description && <p className={styles.lead}>{d.description}</p>}
          <p className="muted">
            {d.classroom
              ? `Diccionario de aula · Curso ${formatSchoolYear(d.classroom.schoolYear)}`
              : `Diccionario personal de ${d.owner.displayName}`}
          </p>
          <p className="small muted">
            {shown.length} {shown.length === 1 ? 'entrada' : 'entradas'} ·{' '}
            {formatDate(new Date().toISOString())} · LexiCán
          </p>
          {authors.length > 0 && (
            <p className="small">
              <strong>Han participado:</strong> {authors.join(', ')}
            </p>
          )}
        </header>

        {shown.length === 0 ? (
          <EmptyState title="No hay entradas que imprimir">
            {byTopic && <p>Selecciona alguna temática.</p>}
          </EmptyState>
        ) : (
          groups.map(([letter, list]) => (
            <section key={letter} className={styles.letter} aria-labelledby={`letra-${letter}`}>
              <h2 id={`letra-${letter}`} className={styles.letterHeading}>
                {letter}
              </h2>
              {list.map((e) => (
                <div key={e.id} className={styles.entry}>
                  <EntryContent
                    headword={e.headword}
                    senses={e.senses}
                    visibleFields={d.classroom?.visibleFields}
                    showHidden={includeHidden}
                    headingLevel={3}
                  />
                </div>
              ))}
            </section>
          ))
        )}
      </div>
    </>
  );
}
