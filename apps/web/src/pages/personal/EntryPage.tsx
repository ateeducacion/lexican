import type {
  CommentView,
  DictionaryView,
  EntryView,
  SubmitResult,
  WindowState,
} from '@lexican/core';
import { useState } from 'react';
import {
  Link,
  useLoaderData,
  useNavigate,
  useRevalidator,
  type LoaderFunctionArgs,
} from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryContent } from '../../components/EntryContent.tsx';
import {
  Dialog,
  ErrorMessage,
  PageTitle,
  StatusBadge,
  formatDate,
  useAction,
  useNotify,
} from '../../components/ui.tsx';
import styles from './personal.module.css';
import { CLOSED_WINDOW } from './windowLabels.ts';

interface Data {
  entry: EntryView;
  classrooms: DictionaryView[];
  comments: CommentView[];
}

export async function loader({ params }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const [entry, classrooms, comments] = await Promise.all([
    api.getEntry({ entryId: params.entryId! }),
    api.myClassrooms({}),
    api.listComments({}),
  ]);
  const ids = new Set(entry.submissions.map((s) => s.id));
  return {
    entry,
    classrooms,
    comments: comments.filter((c) => c.submissionId && ids.has(c.submissionId)),
  };
}

export function Component() {
  const { entry, classrooms, comments } = useLoaderData<Data>();
  const navigate = useNavigate();
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const [sending, setSending] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const hide = useAction(async (hidden: boolean) => {
    await (await getApi()).setEntryHidden({ entryId: entry.id, hidden });
    notify(hidden ? 'Entrada oculta' : 'Entrada visible de nuevo');
    await revalidate();
  });
  const hideSense = useAction(async (senseId: string, hidden: boolean) => {
    await (await getApi()).setSenseHidden({ senseId, hidden });
    notify(hidden ? 'Acepción oculta' : 'Acepción visible de nuevo');
    await revalidate();
  });
  const withdraw = useAction(async (submissionId: string) => {
    await (await getApi()).withdrawSubmission({ submissionId });
    notify('Envío retirado');
    await revalidate();
  });
  const remove = useAction(async () => {
    await (await getApi()).deleteEntry({ entryId: entry.id });
    notify(`Entrada «${entry.headword}» borrada`);
    navigate('/mi-diccionario', { replace: true });
  });

  const hasPending = entry.submissions.some((s) => s.status === 'pending');
  const commentsBySubmission = (id: string) => comments.filter((c) => c.submissionId === id);

  return (
    <div className="stack">
      <p className="small">
        <Link to="/mi-diccionario">← Mi diccionario</Link>
      </p>
      {/* The visible headword comes from EntryContent (h2); the h1 names the page for assistive tech. */}
      <div className="visually-hidden">
        <PageTitle
          title={entry.headword}
        >{`Entrada «${entry.headword}» de mi diccionario`}</PageTitle>
      </div>
      {entry.hidden && (
        <p className="alert alert-warning">
          Esta entrada está <strong>oculta</strong>: no aparece al enviar varias palabras ni al
          exportar.
        </p>
      )}

      <div className={styles.toolbar}>
        <Link to="editar" className="btn btn-primary">
          Editar
        </Link>
        <button type="button" className="btn" onClick={() => setSending(true)}>
          Enviar al aula
        </button>
        <button
          type="button"
          className="btn"
          disabled={hide.busy}
          onClick={() => void hide.run(!entry.hidden)}
        >
          {entry.hidden ? 'Mostrar entrada' : 'Ocultar entrada'}
        </button>
        <button type="button" className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
          Borrar
        </button>
      </div>
      <ErrorMessage error={hide.error ?? hideSense.error ?? withdraw.error} />

      <div className={styles.layout}>
        <div className="card">
          <EntryContent
            headword={entry.headword}
            senses={entry.senses}
            showHidden
            headingLevel={2}
          />
          {entry.senses.length > 1 && (
            <section aria-labelledby="sense-vis" className={styles.senseVis}>
              <h3 id="sense-vis">Acepciones visibles</h3>
              <p className="small muted">Las acepciones ocultas no se envían al aula.</p>
              <ul className={styles.list}>
                {entry.senses.map((s, i) => (
                  <li key={s.id}>
                    <span className="small">
                      {i + 1}.{' '}
                      {s.definition.length > 60 ? `${s.definition.slice(0, 60)}…` : s.definition}
                    </span>
                    <button
                      type="button"
                      className="btn btn-sm"
                      disabled={hideSense.busy}
                      onClick={() => void hideSense.run(s.id, !s.hidden)}
                    >
                      {s.hidden ? 'Mostrar' : 'Ocultar'}
                      <span className="visually-hidden"> acepción {i + 1}</span>
                    </button>
                  </li>
                ))}
              </ul>
            </section>
          )}
        </div>

        <aside className="stack">
          <section className="card" aria-labelledby="subs-title">
            <h2 id="subs-title">Estado de los envíos</h2>
            {entry.submissions.length === 0 ? (
              <p className="muted">Todavía no has enviado esta palabra a ningún aula.</p>
            ) : (
              <ul className={styles.subs}>
                {entry.submissions.map((s) => (
                  <li key={s.id}>
                    <div className={styles.toolbar}>
                      <strong>{s.classroomTitle}</strong>
                      <StatusBadge status={s.status} />
                    </div>
                    <p className="small muted">
                      Enviada el {formatDate(s.submittedAt, true)}
                      {s.reviewedAt && <> · revisada el {formatDate(s.reviewedAt)}</>}
                    </p>
                    {s.status === 'rejected' && s.reviewNote && (
                      <p className="small prose">
                        <strong>Nota del profesorado:</strong> {s.reviewNote}
                      </p>
                    )}
                    {commentsBySubmission(s.id).map((c) => (
                      <blockquote key={c.id} className={styles.comment}>
                        <p className="prose small">{c.body}</p>
                        <footer className="small muted">
                          {c.author.displayName}, {formatDate(c.createdAt)}
                        </footer>
                      </blockquote>
                    ))}
                    {s.status === 'pending' && (
                      <button
                        type="button"
                        className="btn btn-sm"
                        disabled={withdraw.busy}
                        onClick={() => void withdraw.run(s.id)}
                      >
                        Retirar
                        <span className="visually-hidden"> el envío a {s.classroomTitle}</span>
                      </button>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </section>
        </aside>
      </div>

      <SendDialog
        open={sending}
        onClose={() => setSending(false)}
        entry={entry}
        classrooms={classrooms}
        onDone={() => void revalidate()}
      />

      <Dialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        title={`¿Borrar «${entry.headword}»?`}
      >
        <p>La palabra desaparecerá de tu diccionario.</p>
        {hasPending && (
          <p>
            Los envíos pendientes de revisión se retirarán. Las palabras ya publicadas en un aula se
            mantienen.
          </p>
        )}
        <ErrorMessage error={remove.error} />
        <div className="row">
          <button
            type="button"
            className="btn btn-danger"
            disabled={remove.busy}
            onClick={() => void remove.run()}
          >
            Borrar entrada
          </button>
          <button type="button" className="btn" onClick={() => setConfirmDelete(false)}>
            Cancelar
          </button>
        </div>
      </Dialog>
    </div>
  );
}

function SendDialog({
  open,
  onClose,
  entry,
  classrooms,
  onDone,
}: {
  open: boolean;
  onClose: () => void;
  entry: EntryView;
  classrooms: DictionaryView[];
  onDone: () => void;
}) {
  const notify = useNotify();
  const openOnes = classrooms.filter((c) => c.classroom?.windowState === 'open');
  const closed = classrooms.filter((c) => c.classroom && c.classroom.windowState !== 'open');
  const [chosen, setChosen] = useState<string[]>(() =>
    openOnes.length === 1 ? [openOnes[0]!.id] : [],
  );
  const [check, setCheck] = useState<SubmitResult | null>(null);

  const close = () => {
    setCheck(null);
    onClose();
  };
  const submit = async (classroomIds: string[]) => {
    const r = await (await getApi()).submitEntries({ entryIds: [entry.id], classroomIds });
    notify(`Enviada a ${r.created.map((c) => c.classroomTitle).join(', ')}`);
    onDone();
    close();
  };
  const send = useAction(async () => {
    const dry = await (
      await getApi()
    ).submitEntries({ entryIds: [entry.id], classroomIds: chosen, dryRun: true });
    if (dry.problems.length === 0) return submit(chosen);
    setCheck(dry);
  });
  const okIds = chosen.filter((id) => !check?.problems.some((p) => p.classroomId === id));
  const sendRest = useAction(() => submit(okIds));

  return (
    <Dialog open={open} onClose={close} title={`Enviar «${entry.headword}» al aula`}>
      {openOnes.length === 0 ? (
        <p>Ahora mismo no tienes ningún diccionario de aula que acepte envíos.</p>
      ) : (
        <fieldset>
          <legend>Elige a qué diccionarios de aula enviarla</legend>
          {openOnes.map((c) => (
            <label key={c.id} className="check">
              <input
                type="checkbox"
                checked={chosen.includes(c.id)}
                onChange={(e) => {
                  setCheck(null);
                  setChosen(
                    e.target.checked ? [...chosen, c.id] : chosen.filter((x) => x !== c.id),
                  );
                }}
              />
              {c.title}
            </label>
          ))}
        </fieldset>
      )}
      {closed.length > 0 && (
        <div className="small muted">
          <p>No admiten envíos ahora:</p>
          <ul>
            {closed.map((c) => (
              <li key={c.id}>
                {c.title}: {CLOSED_WINDOW[c.classroom!.windowState as Exclude<WindowState, 'open'>]}
              </li>
            ))}
          </ul>
        </div>
      )}
      {entry.hidden && (
        <p className="alert alert-warning">
          La entrada está oculta, pero puedes enviarla igualmente.
        </p>
      )}

      {check && (
        <div className="alert alert-warning" role="alert">
          <p>
            <strong>Hay que corregir algo antes de enviar:</strong>
          </p>
          <ul>
            {check.problems.map((p) => (
              <li key={p.classroomId}>
                {p.classroomTitle}: {p.message}
              </li>
            ))}
          </ul>
          <Link to="editar">Editar la entrada</Link>
        </div>
      )}
      <ErrorMessage error={send.error ?? sendRest.error} />

      <div className="row">
        {check && okIds.length > 0 ? (
          <button
            type="button"
            className="btn btn-primary"
            disabled={sendRest.busy}
            onClick={() => void sendRest.run()}
          >
            Enviar solo a las aulas sin problemas
          </button>
        ) : (
          openOnes.length > 0 && (
            <button
              type="button"
              className="btn btn-primary"
              disabled={chosen.length === 0 || send.busy || !!check}
              onClick={() => void send.run()}
            >
              {send.busy ? 'Comprobando…' : 'Enviar'}
            </button>
          )
        )}
        <button type="button" className="btn" onClick={close}>
          {check ? 'Cerrar' : 'Cancelar'}
        </button>
      </div>
    </Dialog>
  );
}
