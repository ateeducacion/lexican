import { ALPHABET, type EntrySummary } from '@lexican/core';
import { type FormEvent, useEffect, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router';
import { getApi } from '../api/index.ts';
import styles from './EntryList.module.css';
import { EmptyState, ErrorMessage, Loading, StatusBadge } from './ui.tsx';
import { useVocab } from './vocab.ts';

// CONTRACT (implemented by the personal-dictionary agent; used by classroom pages too).
// Searchable, alphabet-indexed, topic-filterable list of entries of one dictionary. Reads/writes the
// URL search params `q`, `letra`, `tematica` so results are linkable and survive reload.
export interface EntryListProps {
  dictionaryId: string;
  /** Link target for an entry, e.g. (id) => `/mi-diccionario/entradas/${id}`. */
  entryHref: (entryId: string) => string;
  /** Owner/teacher: show hidden entries with a badge and submission status chips. */
  canEdit: boolean;
  /** Personal dictionary only: show submission status chips per classroom. */
  showSubmissions?: boolean;
  /** `rail`: compact side list of the personal workspace (status dot + text, definition only on phones). */
  variant?: 'page' | 'rail';
  /** Entry shown next to the list: highlighted and marked with aria-current. */
  selectedId?: string;
  /** Change it to reload the list after a mutation elsewhere on the page. */
  refreshKey?: unknown;
}

type RailStatus = 'pending' | 'rejected' | 'published' | 'none' | 'hidden';
const RAIL_STATUS: Record<RailStatus, string> = {
  pending: 'Pendiente',
  rejected: 'Devuelta',
  published: 'Publicada',
  none: 'Sin enviar',
  hidden: 'Oculta',
};

/** One status per entry: hidden first, then the submission that needs attention most. */
function railStatus(e: EntrySummary): RailStatus {
  if (e.hidden) return 'hidden';
  const has = (s: string) => e.submissions.some((x) => x.status === s);
  return has('pending')
    ? 'pending'
    : has('rejected')
      ? 'rejected'
      : has('published')
        ? 'published'
        : 'none';
}

const PAGE = 50;

interface Loaded {
  key: string;
  offset: number;
  items: EntrySummary[];
  total: number;
  error: unknown;
}

export function EntryList({
  dictionaryId,
  entryHref,
  canEdit,
  showSubmissions = false,
  variant = 'page',
  selectedId,
  refreshKey,
}: EntryListProps) {
  const v = useVocab();
  const { pathname } = useLocation();
  const rail = variant === 'rail';
  const [params, setParams] = useSearchParams();
  const q = params.get('q') ?? '';
  const letter = params.get('letra') ?? '';
  const topic = params.get('tematica') ?? '';
  const [draft, setDraft] = useState(q);
  const [seenQ, setSeenQ] = useState(q);
  if (seenQ !== q) {
    // The URL changed (back button, link): show its query in the box.
    setSeenQ(q);
    setDraft(q);
  }

  // Results are tagged with the filters they belong to, so a filter change restarts paging at 0.
  const key = [dictionaryId, q, letter, topic, canEdit, String(refreshKey)].join('|');
  const [more, setMore] = useState({ key, offset: 0 });
  const offset = more.key === key ? more.offset : 0;
  const [data, setData] = useState<Loaded | null>(null);
  const current = data?.key === key ? data : null;
  const loading = !current || current.offset !== offset;
  const items = current?.items ?? [];
  const total = current?.total ?? 0;

  useEffect(() => {
    let live = true;
    getApi()
      .then((api) =>
        api.listEntries({
          dictionaryId,
          q: q || undefined,
          initial: letter || undefined,
          topicId: topic || undefined,
          includeHidden: canEdit,
          offset,
          limit: PAGE,
        }),
      )
      .then((page) => {
        if (!live) return;
        setData((prev) => ({
          key,
          offset,
          items: offset > 0 && prev?.key === key ? [...prev.items, ...page.items] : page.items,
          total: page.total,
          error: null,
        }));
      })
      .catch((e: unknown) => {
        if (live)
          setData((prev) => ({
            key,
            offset,
            items: prev?.key === key ? prev.items : [],
            total: prev?.total ?? 0,
            error: e,
          }));
      });
    return () => {
      live = false;
    };
  }, [key, dictionaryId, q, letter, topic, canEdit, offset]);

  const update = (key: string, value: string) =>
    setParams(
      (p) => {
        const next = new URLSearchParams(p);
        if (value) next.set(key, value);
        else next.delete(key);
        return next;
      },
      { replace: true },
    );

  const search = (e: FormEvent) => {
    e.preventDefault();
    update('q', draft.trim());
  };

  const letterHref = (l: string) => {
    const next = new URLSearchParams(params);
    if (l) next.set('letra', l);
    else next.delete('letra');
    return `?${next}`;
  };

  const topics = v.list('topic');
  const filtered = Boolean(q || letter || topic);

  return (
    <section
      aria-labelledby="entry-list-title"
      className={`${styles.wrap} ${rail ? styles.rail : ''}`}
    >
      <h2 id="entry-list-title" className="visually-hidden">
        Entradas
      </h2>
      <div className={styles.filters}>
        {/* biome-ignore lint/a11y/useSemanticElements: <search> is not yet safe on every browser LexiCán targets. */}
        <form role="search" onSubmit={search} className={styles.search}>
          <label htmlFor="entry-search" className="visually-hidden">
            Buscar una palabra
          </label>
          <input
            id="entry-search"
            type="search"
            placeholder="Buscar una palabra"
            maxLength={150}
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
          />
          <button type="submit" className={styles.searchButton}>
            <svg viewBox="0 0 24 24" aria-hidden="true" width="22" height="22">
              <circle cx="10.5" cy="10.5" r="6.5" />
              <path d="m15.5 15.5 5 5" />
            </svg>
            <span className="visually-hidden">Buscar</span>
          </button>
        </form>
        {topics.length > 0 && (
          <div className={styles.topic}>
            <label htmlFor="entry-topic">Temática</label>
            <select
              id="entry-topic"
              value={topic}
              onChange={(e) => update('tematica', e.target.value)}
            >
              <option value="">Todas</option>
              {topics.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.label}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      <nav aria-label="Buscar por letra inicial">
        <ul className={rail ? styles.letters : 'alphabet'}>
          <li>
            <Link to={letterHref('')} replace aria-current={letter ? undefined : 'page'}>
              Todas
            </Link>
          </li>
          {ALPHABET.map((l) => (
            <li key={l}>
              <Link to={letterHref(l)} replace aria-current={letter === l ? 'page' : undefined}>
                {l}
              </Link>
            </li>
          ))}
        </ul>
      </nav>

      <ErrorMessage error={current?.error} />
      <p className="small muted" role="status">
        {loading && offset === 0
          ? 'Cargando…'
          : `${total} ${total === 1 ? 'entrada' : 'entradas'}${filtered ? ' encontradas' : ''}`}
      </p>

      {!loading &&
        !current.error &&
        items.length === 0 &&
        (filtered ? (
          rail ? (
            <div className={styles.empty}>
              <p>No hay resultados. Prueba con otra palabra u otra letra.</p>
              <button
                type="button"
                className="btn btn-sm"
                onClick={() => setParams({}, { replace: true })}
              >
                Quitar filtros
              </button>
            </div>
          ) : (
            <EmptyState title="No hay resultados">
              <p>Prueba con otra palabra, otra letra o quita los filtros.</p>
              <button
                type="button"
                className="btn"
                onClick={() => setParams({}, { replace: true })}
              >
                Quitar filtros
              </button>
            </EmptyState>
          )
        ) : rail ? (
          <p className={styles.empty}>Cuando añadas palabras aparecerán aquí.</p>
        ) : (
          <EmptyState title="Todavía no hay entradas">
            <p className="muted">
              {canEdit
                ? 'Cuando añadas palabras aparecerán aquí.'
                : 'Aún no se ha publicado ninguna palabra.'}
            </p>
          </EmptyState>
        ))}

      {items.length > 0 && rail && (
        <ul className={styles.railList}>
          {items.map((e) => {
            const st = railStatus(e);
            const href = entryHref(e.id);
            const selected = e.id === selectedId;
            return (
              <li key={e.id} className={styles.railItem}>
                <span className={styles.railHead}>
                  {/* Only the headword names the link; the whole card is clickable through ::after. */}
                  <Link
                    to={href}
                    className={styles.railLink}
                    lang="es"
                    aria-current={
                      selected ? (href.split('?')[0] === pathname ? 'page' : 'true') : undefined
                    }
                  >
                    {e.headword}
                  </Link>
                  <span className={`${styles.status} ${styles[st]}`}>{RAIL_STATUS[st]}</span>
                </span>
                {e.firstDefinition && <p className={styles.railDef}>{e.firstDefinition}</p>}
              </li>
            );
          })}
        </ul>
      )}

      {items.length > 0 && !rail && (
        <ul className={styles.list}>
          {items.map((e) => (
            <li key={e.id} className={styles.item}>
              <div className={styles.head}>
                <Link to={entryHref(e.id)} className={styles.headword} lang="es">
                  {e.headword}
                </Link>
                {e.hidden && canEdit && <span className="badge badge-hidden">Oculta</span>}
                <span className="small muted">
                  {e.senseCount} {e.senseCount === 1 ? 'acepción' : 'acepciones'}
                </span>
              </div>
              {e.firstDefinition && <p className={styles.def}>{e.firstDefinition}</p>}
              {showSubmissions && e.submissions.length > 0 && (
                <ul className={styles.chips} aria-label="Envíos">
                  {e.submissions.map((s) => (
                    <li key={s.classroomTitle} className="small">
                      {s.classroomTitle}: <StatusBadge status={s.status} />
                    </li>
                  ))}
                </ul>
              )}
            </li>
          ))}
        </ul>
      )}

      {loading && offset > 0 && <Loading />}
      {!loading && items.length < total && (
        <button
          type="button"
          className="btn"
          onClick={() => setMore({ key, offset: items.length })}
        >
          Cargar más ({total - items.length} restantes)
        </button>
      )}
    </section>
  );
}
