import {
  CommentInput,
  type CommentView,
  type DictionaryView,
  formatSchoolYear,
  type MemberView,
  type PublishResult,
  type SubmissionStatus,
  type SubmissionView,
} from '@lexican/core';
import { type FormEvent, useState } from 'react';
import {
  Form,
  Link,
  type LoaderFunctionArgs,
  useLoaderData,
  useNavigate,
  useParams,
  useRevalidator,
  useSearchParams,
} from 'react-router';
import { ApiError, getApi } from '../../api/index.ts';
import { EntryContent } from '../../components/EntryContent.tsx';
import {
  Dialog,
  ErrorMessage,
  Field,
  formatDate,
  StatusBadge,
  useAction,
  useNotify,
} from '../../components/ui.tsx';
import { useSession } from '../../session.ts';
import styles from './Review.module.css';
import { PageHeader } from './shared.tsx';

/** URL slug ↔ status, so filters are linkable (?estado=devueltas). */
const STATUS_TABS: { slug: string; status: SubmissionStatus; label: string; count: boolean }[] = [
  { slug: 'pendientes', status: 'pending', label: 'Pendientes', count: true },
  { slug: 'publicadas', status: 'published', label: 'Publicadas', count: true },
  { slug: 'devueltas', status: 'rejected', label: 'Devueltas', count: true },
  { slug: 'retiradas', status: 'withdrawn', label: 'Retiradas', count: false },
];
const tabOf = (slug: string | null) => STATUS_TABS.find((t) => t.slug === slug) ?? STATUS_TABS[0]!;

interface Data {
  dictionary: DictionaryView;
  submissions: SubmissionView[];
  counts: Record<SubmissionStatus, number>;
  members: MemberView[];
  selected: SubmissionView | null;
  revision: number;
  comments: CommentView[];
}

/**
 * Review workspace shared by /aulas/:id/envios and /aulas/:id/envios/:submissionId: queue, snapshot of the
 * selected submission and the decision panel. Without a submission in the URL the first one of the tab is shown.
 */
export async function loader({ params, request }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const sp = new URL(request.url).searchParams;
  const classroomId = params.classroomId!;
  const tab = tabOf(sp.get('estado'));
  const [dictionary, all, members] = await Promise.all([
    api.getDictionary({ dictionaryId: classroomId }),
    api.listSubmissions({
      classroomId,
      studentId: sp.get('alumno') || undefined,
      q: sp.get('q') || undefined,
    }),
    api.listMembers({ classroomId }),
  ]);
  const submissions = all.filter((s) => s.status === tab.status);
  const counts = { pending: 0, published: 0, rejected: 0, withdrawn: 0 };
  for (const s of all) counts[s.status]++;
  const id = params.submissionId;
  const selected = id
    ? (all.find((s) => s.id === id) ?? (await api.getSubmission({ submissionId: id })))
    : (submissions[0] ?? null);
  const revision = selected
    ? all.filter(
        (s) => s.sourceEntryId === selected.sourceEntryId && s.submittedAt <= selected.submittedAt,
      ).length || 1
    : 0;
  const comments = selected
    ? await api.listComments({ classroomId, studentId: selected.submittedBy.id })
    : [];
  return { dictionary, submissions, counts, members, selected, revision, comments };
}

export function Component() {
  const { dictionary: d, submissions, counts, members, selected: s } = useLoaderData<Data>();
  const { submissionId } = useParams();
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const tab = tabOf(params.get('estado'));
  const base = `/aulas/${d.id}/envios`;
  const query = params.size ? `?${params}` : '';
  const students = members.filter((m) => m.role === 'student');
  const [checked, setChecked] = useState<Set<string>>(new Set());
  const [result, setResult] = useState<PublishResult | null>(null);
  // Id of the submission shown when another card was clicked: its detail is hidden until the new one
  // loads, so nobody comments on or publishes the previous submission by mistake.
  const [leaving, setLeaving] = useState<string | null>(null);
  if (leaving && leaving !== s?.id) setLeaving(null); // the new submission has loaded
  const selectable = submissions.filter((x) => x.status === 'pending' && !x.conflict);
  const chosen = selectable.filter((x) => checked.has(x.id));

  const publishMany = useAction(async (ids: string[]) => {
    const r = await (await getApi()).publishSubmissions({ submissionIds: ids });
    setResult(r);
    setChecked(new Set());
    await revalidate();
    if (r.published.length)
      notify(
        `${r.published.length} ${r.published.length === 1 ? 'entrada publicada' : 'entradas publicadas'}.`,
      );
  });

  /** After a decision, move on to the next pending submission of the queue (or the empty queue). */
  const goNext = (doneId: string) => {
    const pending = submissions.filter((x) => x.status === 'pending');
    const idx = pending.findIndex((x) => x.id === doneId);
    const next = pending[idx + 1] ?? pending.find((x) => x.id !== doneId);
    void navigate(next ? `${base}/${next.id}${query}` : `${base}${query}`);
  };

  const toggle = (id: string, on: boolean) =>
    setChecked((prev) => {
      const n = new Set(prev);
      if (on) n.add(id);
      else n.delete(id);
      return n;
    });
  const tabLink = (slug: string) => {
    const p = new URLSearchParams(params);
    p.set('estado', slug);
    return `${base}?${p}`;
  };
  const year = d.classroom ? ` · Curso ${formatSchoolYear(d.classroom.schoolYear)}` : '';

  return (
    <>
      <PageHeader
        back={<Link to={`/aulas/${d.id}`}>← {d.title}</Link>}
        eyebrow={`${d.title}${year}`}
        title="Revisar envíos"
        docTitle={`Envíos · ${d.title}`}
      >
        <nav aria-label="Estado de los envíos">
          <ul className={styles.tabs}>
            {STATUS_TABS.map((t) => (
              <li key={t.slug}>
                <Link
                  to={tabLink(t.slug)}
                  aria-current={t.slug === tab.slug ? 'page' : undefined}
                  onClick={() => setChecked(new Set())}
                >
                  {t.label}
                  {t.count && ` · ${counts[t.status]}`}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </PageHeader>

      <div className={styles.workspace} data-detail={submissionId ? '' : undefined}>
        <section className={styles.queue} aria-labelledby="queue-title">
          <h2 id="queue-title" className="visually-hidden">
            Envíos {tab.label.toLowerCase()}
          </h2>
          <details className={styles.filters} open={params.has('q') || params.has('alumno')}>
            <summary>Filtrar por alumno o palabra</summary>
            <Form
              key={params.toString()}
              method="get"
              action={base}
              role="search"
              aria-label="Filtrar envíos"
            >
              <input type="hidden" name="estado" value={tab.slug} />
              <Field label="Alumno o alumna">
                {(p) => (
                  <select {...p} name="alumno" defaultValue={params.get('alumno') ?? ''}>
                    <option value="">Todo el alumnado</option>
                    {students.map((m) => (
                      <option key={m.user.id} value={m.user.id}>
                        {m.user.displayName}
                      </option>
                    ))}
                  </select>
                )}
              </Field>
              <Field label="Buscar palabra">
                {(p) => (
                  <input {...p} type="search" name="q" defaultValue={params.get('q') ?? ''} />
                )}
              </Field>
              <button type="submit" className="btn btn-sm">
                Filtrar
              </button>
            </Form>
          </details>

          {result && (result.conflicts.length > 0 || result.published.length > 0) && (
            <div
              className={`alert ${result.conflicts.length ? 'alert-warning' : 'alert-success'}`}
              role="status"
            >
              <p>
                {result.published.length}{' '}
                {result.published.length === 1 ? 'entrada publicada' : 'entradas publicadas'}.
                {result.conflicts.length > 0 &&
                  ` ${result.conflicts.length} no se ${result.conflicts.length === 1 ? 'ha' : 'han'} podido publicar:`}
              </p>
              {result.conflicts.length > 0 && (
                <ul>
                  {result.conflicts.map((c) => (
                    <li key={c.submissionId}>
                      <strong>{c.headword}</strong>: {c.message}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          )}
          <ErrorMessage error={publishMany.error} />

          {submissions.length === 0 ? (
            <p className={styles.emptyQueue}>
              No hay envíos {tab.label.toLowerCase()}.
              {(params.get('q') || params.get('alumno')) && ' Prueba a quitar los filtros.'}
            </p>
          ) : (
            <>
              {tab.status === 'pending' && (
                <label className={`check ${styles.selectAll}`}>
                  <input
                    type="checkbox"
                    checked={selectable.length > 0 && chosen.length === selectable.length}
                    disabled={selectable.length === 0}
                    onChange={(e) =>
                      setChecked(
                        e.target.checked ? new Set(selectable.map((x) => x.id)) : new Set(),
                      )
                    }
                  />
                  <span>Seleccionar todas</span>
                </label>
              )}
              <ul className={styles.cards}>
                {submissions.map((x) => (
                  <li
                    key={x.id}
                    className={styles.qcard}
                    data-current={x.id === s?.id ? '' : undefined}
                  >
                    {tab.status === 'pending' && (
                      <input
                        type="checkbox"
                        aria-label={`Seleccionar ${x.snapshot.headword}`}
                        checked={checked.has(x.id)}
                        disabled={!!x.conflict}
                        onChange={(e) => toggle(x.id, e.target.checked)}
                      />
                    )}
                    <div className={styles.qbody}>
                      <div className={styles.qhead}>
                        <Link
                          to={`${base}/${x.id}${query}`}
                          lang="es"
                          aria-current={x.id === s?.id ? 'true' : undefined}
                          className={styles.qlink}
                          onClick={() => x.id !== s?.id && setLeaving(s?.id ?? null)}
                        >
                          {x.snapshot.headword}
                        </Link>
                        <time dateTime={x.submittedAt}>{formatDate(x.submittedAt, true)}</time>
                      </div>
                      <p className={styles.qmeta}>
                        {x.submittedBy.displayName} · {senseCount(x)}
                      </p>
                      {x.conflict ? (
                        <p className={styles.qwarn}>
                          <span aria-hidden="true">⚠ </span>Ya existe «{x.conflict.headword}»
                          publicada
                        </p>
                      ) : x.status === 'pending' ? (
                        <p className={styles.qok}>
                          <span aria-hidden="true">✓ </span>Cumple las pautas
                        </p>
                      ) : null}
                    </div>
                  </li>
                ))}
              </ul>
            </>
          )}

          {chosen.length > 0 && (
            <div className={styles.bulk}>
              <span>
                {chosen.length} {chosen.length === 1 ? 'seleccionada' : 'seleccionadas'}
              </span>
              <button
                type="button"
                className="btn btn-accent"
                disabled={publishMany.busy}
                onClick={() => void publishMany.run(chosen.map((x) => x.id))}
              >
                {publishMany.busy ? 'Publicando…' : 'Publicar seleccionadas'}
              </button>
            </div>
          )}
        </section>

        {s && leaving === s.id ? (
          <p className={styles.snapshot} role="status">
            Cargando el envío…
          </p>
        ) : s ? (
          <Detail key={s.id} base={`${base}${query}`} onDone={goNext} />
        ) : (
          <div className={styles.snapshot}>
            <div className={styles.empty}>
              <h2>
                {tab.status === 'pending'
                  ? 'No hay envíos pendientes'
                  : `No hay envíos ${tab.label.toLowerCase()}`}
              </h2>
              <p className="muted">
                {tab.status === 'pending'
                  ? 'Cuando el alumnado envíe palabras al diccionario aparecerán aquí.'
                  : 'Elige otro estado para ver más envíos.'}
              </p>
            </div>
          </div>
        )}
      </div>
    </>
  );
}

const senseCount = (x: SubmissionView) => {
  const n = x.snapshot.senses.length;
  return `${n} ${n === 1 ? 'acepción' : 'acepciones'}`;
};

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toLocaleUpperCase('es'))
    .join('');

/** Snapshot (center) and decision panel (right) of the selected submission. */
function Detail({ base, onDone }: { base: string; onDone: (id: string) => void }) {
  const { dictionary: d, selected, revision, comments } = useLoaderData<Data>();
  const s = selected!;
  const notify = useNotify();
  const { revalidate } = useRevalidator();
  const [rejecting, setRejecting] = useState(false);
  const [note, setNote] = useState('');
  const teacher = d.myRole === 'teacher';
  const word = s.snapshot.headword;

  const publish = useAction(async () => {
    const r = await (await getApi()).publishSubmissions({ submissionIds: [s.id] });
    if (r.conflicts.length) {
      await revalidate();
      throw new ApiError('conflict', r.conflicts.map((c) => c.message).join(' '));
    }
    notify(`«${word}» publicada.`);
    onDone(s.id);
  });
  const reject = useAction(async () => {
    await (await getApi()).rejectSubmission({ submissionId: s.id, note });
    setRejecting(false);
    notify(`«${word}» devuelta a ${s.submittedBy.displayName}.`);
    onDone(s.id);
  });

  return (
    <>
      <article className={styles.snapshot} aria-label={`Envío de ${word}`}>
        <p className={`no-print ${styles.back}`}>
          <Link to={base}>← Todos los envíos</Link>
        </p>
        <div className={styles.who}>
          <span className={styles.avatar} aria-hidden="true">
            {initials(s.submittedBy.displayName)}
          </span>
          <div>
            <p className={styles.whoName}>{s.submittedBy.displayName}</p>
            <p className={styles.whoMeta}>
              {revision > 1 ? 'Reenvío' : 'Envío nuevo'} · revisión {revision} · copia fija de lo
              que envió · {formatDate(s.submittedAt, true)}
            </p>
          </div>
        </div>
        <div className={styles.entry}>
          <EntryContent
            headword={word}
            senses={s.snapshot.senses}
            visibleFields={d.classroom?.visibleFields}
          />
        </div>
        <div className={styles.info} data-tone={s.conflict ? 'warning' : undefined}>
          <span aria-hidden="true" className={styles.infoIcon}>
            {s.conflict
              ? '⚠'
              : s.status === 'published'
                ? '✔'
                : s.status === 'rejected'
                  ? '↩'
                  : 'ℹ'}
          </span>
          <p>
            {s.conflict ? (
              <>
                <strong>
                  Ya hay una entrada «{s.conflict.headword}» publicada en este diccionario.
                </strong>{' '}
                No se puede publicar este envío mientras exista.{' '}
                <Link to={`/aulas/${d.id}/entradas/${s.conflict.entryId}`}>
                  Ver la entrada publicada
                </Link>
              </>
            ) : s.status === 'published' && s.publishedEntryId ? (
              <>
                Publicada en el diccionario.{' '}
                <Link to={`/aulas/${d.id}/entradas/${s.publishedEntryId}`}>
                  Ver la entrada publicada
                </Link>
              </>
            ) : s.status === 'rejected' ? (
              <>
                <strong>Devuelta.</strong> {s.reviewNote || 'Sin nota.'}
              </>
            ) : s.status === 'withdrawn' ? (
              'El alumno o alumna retiró este envío.'
            ) : (
              `No hay otra entrada «${word}» en este diccionario.`
            )}
          </p>
        </div>
      </article>

      <aside className={styles.panel} aria-label="Decisión">
        {s.status === 'pending' && teacher ? (
          <div className={styles.decide}>
            <ErrorMessage error={publish.error} />
            <button
              type="button"
              className={`btn btn-primary ${styles.publish}`}
              disabled={publish.busy || !!s.conflict}
              onClick={() => void publish.run()}
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m5 12 5 5L20 7" />
              </svg>
              {publish.busy ? 'Publicando…' : 'Publicar en el aula'}
            </button>
            <button type="button" className="btn" onClick={() => setRejecting(true)}>
              Devolver con una nota
            </button>
          </div>
        ) : (
          <div className={styles.decided}>
            <StatusBadge status={s.status} />
            {s.reviewedBy && s.reviewedAt && (
              <p className="small muted">
                Revisada por {s.reviewedBy.displayName} el {formatDate(s.reviewedAt, true)}
              </p>
            )}
          </div>
        )}
        <Thread submission={s} comments={comments} onChange={revalidate} />
      </aside>

      <Dialog open={rejecting} onClose={() => setRejecting(false)} title={`Devolver «${word}»`}>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            void reject.run();
          }}
        >
          <p className="small">
            La entrada vuelve al alumno o alumna, que podrá corregirla y enviarla de nuevo.
          </p>
          <Field label="Nota para el alumno o alumna" hint="Opcional. Explica qué debe mejorar.">
            {(p) => (
              <textarea
                {...p}
                maxLength={1000}
                value={note}
                onChange={(e) => setNote(e.target.value)}
              />
            )}
          </Field>
          <ErrorMessage error={reject.error} />
          <div className="row">
            <button type="submit" className="btn btn-primary" disabled={reject.busy}>
              Devolver
            </button>
            <button type="button" className="btn" onClick={() => setRejecting(false)}>
              Cancelar
            </button>
          </div>
        </form>
      </Dialog>
    </>
  );
}

function Thread({
  submission,
  comments,
  onChange,
}: {
  submission: SubmissionView;
  comments: CommentView[];
  onChange: () => Promise<void>;
}) {
  const { user } = useSession();
  const notify = useNotify();
  const [body, setBody] = useState('');
  const [fieldError, setFieldError] = useState<string>();
  const add = useAction(async (text: string) => {
    const parsed = CommentInput.safeParse({
      classroomId: submission.classroomId,
      studentId: submission.submittedBy.id,
      submissionId: submission.id,
      body: text,
    });
    if (!parsed.success) {
      setFieldError(parsed.error.issues[0]?.message);
      return;
    }
    setFieldError(undefined);
    await (await getApi()).createComment(parsed.data);
    setBody('');
    await onChange();
    notify('Comentario enviado.');
  });
  const del = useAction(async (id: string) => {
    await (await getApi()).deleteComment({ commentId: id });
    await onChange();
    notify('Comentario borrado.');
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    void add.run(body);
  };
  // Oldest first, like a conversation.
  const thread = [...comments].sort((a, b) => a.createdAt.localeCompare(b.createdAt));

  return (
    <section className={styles.thread} aria-labelledby="thread-title">
      <h2 id="thread-title">Conversación con {submission.submittedBy.displayName}</h2>
      {thread.length === 0 ? (
        <p className="small muted">Todavía no hay comentarios para este alumno o alumna.</p>
      ) : (
        <ol className={styles.bubbles}>
          {thread.map((c) => (
            <li key={c.id}>
              <span className={styles.bubbleAvatar} aria-hidden="true">
                {initials(c.author.displayName)}
              </span>
              <div>
                <p className={styles.bubbleMeta}>
                  {c.author.displayName} · {formatDate(c.createdAt, true)} ·{' '}
                  {c.submissionId === submission.id
                    ? 'sobre esta entrada'
                    : c.headword
                      ? `sobre «${c.headword}»`
                      : 'comentario general'}
                </p>
                <p className={styles.bubble}>{c.body}</p>
                {c.author.id === user.id && (
                  <button
                    type="button"
                    className="btn btn-sm btn-link"
                    disabled={del.busy}
                    onClick={() => void del.run(c.id)}
                  >
                    Borrar
                    <span className="visually-hidden">
                      {' '}
                      comentario del {formatDate(c.createdAt, true)}
                    </span>
                  </button>
                )}
              </div>
            </li>
          ))}
        </ol>
      )}
      <ErrorMessage error={del.error} />
      <form onSubmit={submit} noValidate className={styles.newComment}>
        <Field label="Nuevo comentario sobre esta entrada" error={fieldError}>
          {(p) => (
            <textarea
              {...p}
              maxLength={2000}
              placeholder="Escribe un comentario para el alumno o alumna…"
              value={body}
              onChange={(e) => setBody(e.target.value)}
            />
          )}
        </Field>
        <ErrorMessage error={add.error} />
        <button type="submit" className={`btn ${styles.send}`} disabled={add.busy}>
          {add.busy ? 'Enviando…' : 'Enviar comentario'}
        </button>
      </form>
    </section>
  );
}
