import { CommentInput, type CommentView, type DictionaryView, type SubmissionView } from '@lexican/core';
import { useState, type FormEvent } from 'react';
import { Link, useLoaderData, useNavigate, useRevalidator, type LoaderFunctionArgs } from 'react-router';
import { ApiError, getApi } from '../../api/index.ts';
import { EntryContent } from '../../components/EntryContent.tsx';
import { Dialog, ErrorMessage, Field, PageTitle, StatusBadge, formatDate, useAction, useNotify } from '../../components/ui.tsx';
import styles from './Classrooms.module.css';
import { BackToClassroom } from './shared.tsx';

interface Data {
  dictionary: DictionaryView;
  submission: SubmissionView;
  comments: CommentView[];
  pendingIds: string[];
}

export async function loader({ params }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const classroomId = params.classroomId!;
  const [dictionary, submission, pending] = await Promise.all([
    api.getDictionary({ dictionaryId: classroomId }),
    api.getSubmission({ submissionId: params.submissionId! }),
    api.listSubmissions({ classroomId, status: 'pending' }),
  ]);
  const comments = await api.listComments({ classroomId, studentId: submission.submittedBy.id });
  return { dictionary, submission, comments, pendingIds: pending.map((s) => s.id) };
}

export function Component() {
  const { dictionary: d, submission: s, comments, pendingIds } = useLoaderData<Data>();
  const navigate = useNavigate();
  const notify = useNotify();
  const { revalidate } = useRevalidator();
  const [rejecting, setRejecting] = useState(false);
  const [note, setNote] = useState('');
  const base = `/aulas/${d.id}/envios`;

  // Previous/next pending submission (list is newest first).
  const idx = pendingIds.indexOf(s.id);
  const others = pendingIds.filter((id) => id !== s.id);
  const prev = idx > 0 ? pendingIds[idx - 1] : undefined;
  const next = idx >= 0 ? pendingIds[idx + 1] : undefined;
  const afterReview = () => {
    const target = next ?? others[0];
    navigate(target ? `${base}/${target}` : base);
  };

  const publish = useAction(async () => {
    const r = await (await getApi()).publishSubmissions({ submissionIds: [s.id] });
    if (r.conflicts.length) {
      await revalidate();
      throw new ApiError('conflict', r.conflicts.map((c) => c.message).join(' '));
    }
    notify(`«${s.snapshot.headword}» publicada.`);
    afterReview();
  });
  const reject = useAction(async () => {
    await (await getApi()).rejectSubmission({ submissionId: s.id, note });
    setRejecting(false);
    notify(`«${s.snapshot.headword}» devuelta a ${s.submittedBy.displayName}.`);
    afterReview();
  });

  return (
    <>
      <BackToClassroom dictionary={d} />
      <p className="no-print small">
        <Link to={base}>← Todos los envíos</Link>
      </p>
      <PageTitle title={`Envío: ${s.snapshot.headword}`}>Envío de {s.submittedBy.displayName}</PageTitle>
      <p className="row">
        <StatusBadge status={s.status} />
        <span className="small muted">Enviada el {formatDate(s.submittedAt, true)}</span>
        {s.reviewedBy && s.reviewedAt && (
          <span className="small muted">
            · Revisada por {s.reviewedBy.displayName} el {formatDate(s.reviewedAt, true)}
          </span>
        )}
      </p>

      <div className={styles.split}>
        <div className="stack">
          <div className="card">
            <EntryContent headword={s.snapshot.headword} senses={s.snapshot.senses} visibleFields={d.classroom?.visibleFields} />
          </div>

          {s.conflict && (
            <div className="alert alert-warning">
              <strong>
                <span aria-hidden="true">⚠ </span>Ya hay una entrada «{s.conflict.headword}» publicada en este diccionario.
              </strong>{' '}
              No se puede publicar este envío mientras exista. <Link to={`/aulas/${d.id}/entradas/${s.conflict.entryId}`}>Ver la entrada publicada</Link>
            </div>
          )}
          {s.status === 'published' && s.publishedEntryId && (
            <div className="alert alert-success">
              Publicada en el diccionario. <Link to={`/aulas/${d.id}/entradas/${s.publishedEntryId}`}>Ver la entrada publicada</Link>
            </div>
          )}
          {s.status === 'rejected' && (
            <div className="alert">
              <strong>Devuelta.</strong> {s.reviewNote || 'Sin nota.'}
            </div>
          )}

          {s.status === 'pending' && d.myRole === 'teacher' && (
            <section className="card" aria-labelledby="review-title">
              <h2 id="review-title">Revisión</h2>
              <ErrorMessage error={publish.error} />
              <div className="row">
                <button type="button" className="btn btn-primary" disabled={publish.busy || !!s.conflict} onClick={() => void publish.run()}>
                  {publish.busy ? 'Publicando…' : 'Publicar'}
                </button>
                <button type="button" className="btn" onClick={() => setRejecting(true)}>
                  Devolver con nota
                </button>
              </div>
            </section>
          )}

          <nav aria-label="Envíos pendientes" className="spread no-print">
            {prev ? <Link to={`${base}/${prev}`}>← Pendiente anterior</Link> : <span />}
            {next ? <Link to={`${base}/${next}`}>Siguiente pendiente →</Link> : <span />}
          </nav>
        </div>

        <Comments dictionary={d} submission={s} comments={comments} onChange={revalidate} />
      </div>

      <Dialog open={rejecting} onClose={() => setRejecting(false)} title={`Devolver «${s.snapshot.headword}»`}>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            void reject.run();
          }}
        >
          <p className="small">La entrada vuelve al alumno o alumna, que podrá corregirla y enviarla de nuevo.</p>
          <Field label="Nota para el alumno o alumna" hint="Opcional. Explica qué debe mejorar.">
            {(p) => <textarea {...p} maxLength={1000} value={note} onChange={(e) => setNote(e.target.value)} />}
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

function Comments({
  dictionary,
  submission,
  comments,
  onChange,
}: {
  dictionary: DictionaryView;
  submission: SubmissionView;
  comments: CommentView[];
  onChange: () => Promise<void>;
}) {
  const notify = useNotify();
  const [body, setBody] = useState('');
  const [fieldError, setFieldError] = useState<string>();
  const add = useAction(async (text: string) => {
    const parsed = CommentInput.safeParse({
      classroomId: dictionary.id,
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

  return (
    <section className="card" aria-labelledby="comments-title">
      <h2 id="comments-title">Comentarios a {submission.submittedBy.displayName}</h2>
      {comments.length === 0 ? (
        <p className="muted small">Todavía no hay comentarios para este alumno o alumna en este diccionario.</p>
      ) : (
        <ul className={styles.comments}>
          {comments.map((c) => (
            <li key={c.id}>
              <p className="small muted" style={{ marginBottom: 'var(--space-1)' }}>
                {c.submissionId === submission.id ? 'Sobre esta entrada' : c.headword ? `Sobre «${c.headword}»` : 'Comentario general'} · {c.author.displayName} ·{' '}
                {formatDate(c.createdAt, true)}
              </p>
              <p className="prose">{c.body}</p>
              <button type="button" className="btn btn-sm btn-link" disabled={del.busy} onClick={() => void del.run(c.id)}>
                Borrar<span className="visually-hidden"> comentario del {formatDate(c.createdAt, true)}</span>
              </button>
            </li>
          ))}
        </ul>
      )}
      <ErrorMessage error={del.error} />
      <form onSubmit={submit} noValidate>
        <Field label="Nuevo comentario sobre esta entrada" error={fieldError}>
          {(p) => <textarea {...p} maxLength={2000} value={body} onChange={(e) => setBody(e.target.value)} />}
        </Field>
        <ErrorMessage error={add.error} />
        <button type="submit" className="btn" disabled={add.busy}>
          {add.busy ? 'Enviando…' : 'Enviar comentario'}
        </button>
      </form>
    </section>
  );
}
