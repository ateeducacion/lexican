import {
  type CommentView,
  type DictionaryView,
  type EntryView,
  SENSE_FIELD_CODES,
  type SenseField,
  type SubmissionBrief,
  type SubmitResult,
  type WindowState,
} from '@lexican/core';
import { useState } from 'react';
import {
  Link,
  type LoaderFunctionArgs,
  useLoaderData,
  useLocation,
  useNavigate,
  useParams,
  useRevalidator,
  useSearchParams,
} from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryContent } from '../../components/EntryContent.tsx';
import { EntryList } from '../../components/EntryList.tsx';
import {
  Dialog,
  ErrorMessage,
  formatDate,
  PageTitle,
  STATUS_LABEL,
  useAction,
  useNotify,
} from '../../components/ui.tsx';
import { useVocab } from '../../components/vocab.ts';
import styles from './PersonalWorkspace.module.css';
import { CLOSED_WINDOW } from './windowLabels.ts';

interface Data {
  dict: DictionaryView;
  /** Entry in the reading pane: the one in the URL, else the first one matching the list filters. */
  entry: EntryView | null;
  classrooms: DictionaryView[];
  comments: CommentView[];
}

export async function loader({ params, request }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const [dict, classrooms, comments] = await Promise.all([
    api.myDictionary({}),
    api.myClassrooms({}),
    api.listComments({}),
  ]);
  let entryId = params.entryId;
  if (!entryId) {
    const q = new URL(request.url).searchParams;
    const first = await api.listEntries({
      dictionaryId: dict.id,
      q: q.get('q') || undefined,
      initial: q.get('letra') || undefined,
      topicId: q.get('tematica') || undefined,
      includeHidden: true,
      offset: 0,
      limit: 1,
    });
    entryId = first.items[0]?.id;
  }
  const entry = entryId ? await api.getEntry({ entryId }) : null;
  const ids = new Set(entry?.submissions.map((s) => s.id));
  return {
    dict,
    entry,
    classrooms,
    comments: comments.filter((c) => c.submissionId && ids.has(c.submissionId)),
  };
}

/**
 * Personal dictionary workspace: entry rail, reading pane and status panel side by side on wide screens;
 * on phones `/mi-diccionario` is the list and `/mi-diccionario/entradas/:id` the entry.
 */
export function Component() {
  const { dict, entry, classrooms, comments } = useLoaderData<Data>();
  const { entryId } = useParams();
  const { search } = useLocation();
  const [params] = useSearchParams();
  const { revalidate } = useRevalidator();
  // Bumped after a mutation so the rail reloads its statuses too.
  const [listVersion, setListVersion] = useState(0);
  const refresh = async () => {
    setListVersion((n) => n + 1);
    await revalidate();
  };
  const count = `${dict.entryCount} ${dict.entryCount === 1 ? 'palabra' : 'palabras'}`;
  const railTitle = (
    <>
      <span className="eyebrow">Mi diccionario</span>
      <span className={styles.count}>{count}</span>
    </>
  );

  return (
    <div className={styles.workspace} data-view={entryId ? 'entry' : 'list'}>
      <section className={styles.rail} aria-labelledby="ws-rail-title">
        <div className={styles.railTop}>
          <div id="ws-rail-title" className={styles.railTitle}>
            {entryId ? (
              <h2>{railTitle}</h2>
            ) : (
              <PageTitle title="Mi diccionario">{railTitle}</PageTitle>
            )}
            <Link
              to="/mi-diccionario/ajustes"
              className={styles.settings}
              aria-label="Ajustes de mi diccionario"
            >
              <span aria-hidden="true">⚙</span>
            </Link>
          </div>
          <Link to="/mi-diccionario/nueva" className={`btn btn-primary ${styles.add}`}>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
              <path d="M12 5v14M5 12h14" />
            </svg>
            Añadir<span className={styles.addMore}> entrada</span>
          </Link>
        </div>
        <EntryList
          dictionaryId={dict.id}
          entryHref={(id) => `/mi-diccionario/entradas/${id}${search}`}
          canEdit
          showSubmissions
          variant="rail"
          selectedId={entry?.id}
          refreshKey={listVersion}
        />
        <details
          className={styles.help}
          open={dict.entryCount === 0 || params.has('ayuda') || undefined}
        >
          <summary>¿Qué puedo hacer?</summary>
          <HelpSteps />
        </details>
      </section>

      {entry ? (
        <EntryPane
          key={entry.id}
          entry={entry}
          isPage={!!entryId}
          backHref={`/mi-diccionario${search}`}
          classrooms={classrooms}
          onChange={refresh}
        />
      ) : (
        <section className={styles.reader} aria-labelledby="ws-empty">
          {dict.entryCount === 0 ? (
            <div className={styles.empty}>
              <h2 id="ws-empty">Tu diccionario está vacío</h2>
              <p>
                Añade la primera palabra que quieras aprender: su definición, un ejemplo y, si
                quieres, una imagen o un audio.
              </p>
              <Link to="/mi-diccionario/nueva" className="btn btn-primary">
                Escribir mi primera palabra
              </Link>
            </div>
          ) : (
            <div className={styles.empty}>
              <h2 id="ws-empty">Ninguna palabra coincide</h2>
              <p>Prueba con otra palabra, otra letra o quita los filtros.</p>
            </div>
          )}
        </section>
      )}

      <div className={styles.panel}>
        {entry && (
          <div className={styles.entryPanel}>
            <SubmissionsCard entry={entry} onChange={refresh} />
            {comments.length > 0 && (
              <section className={styles.card} aria-labelledby="ws-comments">
                <h2 id="ws-comments">Comentarios del profesorado</h2>
                <ul className={styles.comments}>
                  {comments.map((c) => (
                    <li key={c.id}>
                      <span className={styles.initials} aria-hidden="true">
                        {initials(c.author.displayName)}
                      </span>
                      <div className={styles.bubble}>
                        <p className="prose">{c.body}</p>
                        <p className={styles.bubbleMeta}>
                          {c.author.displayName} · {formatDate(c.createdAt)}
                        </p>
                      </div>
                    </li>
                  ))}
                </ul>
              </section>
            )}
          </div>
        )}
        <section className={styles.export} aria-labelledby="ws-export">
          <h2 id="ws-export">Exportar</h2>
          <p>Tu diccionario en PDF, CSV o DMLex.</p>
          <Link to={`/diccionarios/${dict.id}/imprimir`} className="btn btn-accent">
            Imprimir o exportar
          </Link>
        </section>
        <nav aria-label="Más opciones del diccionario">
          <ul className={styles.more}>
            <li>
              <Link to="/mi-diccionario/enviar">Enviar varias palabras al aula</Link>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  );
}

function HelpSteps() {
  return (
    <ol className={styles.steps}>
      <li>
        Pulsa <strong>Añadir</strong>, escribe la palabra y su definición. Puedes añadir un ejemplo,
        temáticas, una imagen, un audio o un vídeo.
      </li>
      <li>
        Pulsa <strong>Guardar</strong>. La palabra queda en tu diccionario; solo tú la ves.
      </li>
      <li>
        Ábrela y pulsa <strong>Enviar al aula</strong> para mandarla al diccionario de tu clase
        (antes tienes que unirte con el código que te dé tu profesor o profesora en{' '}
        <Link to="/aulas">Mis aulas</Link>).
      </li>
      <li>
        Mira el estado del envío: <em>Pendiente de revisión</em>, <em>Publicada</em> o{' '}
        <em>Devuelta</em>. Si te la devuelven, corrígela y vuelve a enviarla.
      </li>
      <li>
        Lee lo que te escribe el profesorado en <Link to="/comentarios">Comentarios</Link>.
      </li>
    </ol>
  );
}

function EntryPane({
  entry,
  isPage,
  backHref,
  classrooms,
  onChange,
}: {
  entry: EntryView;
  /** The URL names this entry: its headword is the page's h1. */
  isPage: boolean;
  backHref: string;
  classrooms: DictionaryView[];
  onChange: () => Promise<void>;
}) {
  const v = useVocab();
  const navigate = useNavigate();
  const notify = useNotify();
  const [sending, setSending] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const hide = useAction(async (hidden: boolean) => {
    await (await getApi()).setEntryHidden({ entryId: entry.id, hidden });
    notify(hidden ? 'Entrada oculta' : 'Entrada visible de nuevo');
    await onChange();
  });
  const hideSense = useAction(async (senseId: string, hidden: boolean) => {
    await (await getApi()).setSenseHidden({ senseId, hidden });
    notify(hidden ? 'Acepción oculta' : 'Acepción visible de nuevo');
    await onChange();
  });
  const remove = useAction(async () => {
    await (await getApi()).deleteEntry({ entryId: entry.id });
    notify(`Entrada «${entry.headword}» borrada`);
    await navigate('/mi-diccionario', { replace: true });
    await onChange();
  });

  // Header line: topics as chips, grammar when every sense shares it, translations. Shown once there, so the
  // sense rows leave them out.
  const topics = [...new Set(entry.senses.flatMap((s) => s.topicIds))];
  const grammarOf = (s: EntryView['senses'][number]) =>
    [v.abbr(s.partOfSpeechId), v.abbr(s.genderId), v.abbr(s.numberId)].filter(Boolean).join(' ');
  const grammars = [...new Set(entry.senses.map(grammarOf))];
  const sharedGrammar = grammars.length === 1 ? grammars[0]! : '';
  const foreign = [
    ...new Map(
      entry.senses
        .filter((s) => s.languageId && s.foreignForm)
        .map((s) => [`${s.languageId}|${s.foreignForm}`, s] as const),
    ).values(),
  ];
  const headerFields: SenseField[] = ['topics', 'language'];
  if (grammars.length === 1) headerFields.push('part_of_speech', 'gender', 'number');
  const visibleFields = SENSE_FIELD_CODES.filter((f) => !headerFields.includes(f));
  const hasPending = entry.submissions.some((s) => s.status === 'pending');
  const editHref = `/mi-diccionario/entradas/${entry.id}/editar`;

  return (
    <section className={styles.reader} aria-labelledby="ws-headword">
      {isPage && (
        <p className={styles.back}>
          <Link to={backHref}>← Mi diccionario</Link>
        </p>
      )}
      {entry.hidden && (
        <p className="alert alert-warning">
          Esta entrada está <strong>oculta</strong>: no aparece al enviar varias palabras ni al
          exportar.
        </p>
      )}
      <header className={styles.entryHead}>
        <div className={styles.entryTitle}>
          <ul className={styles.chips} aria-label="Datos de la entrada">
            {topics.map((t) => (
              <li key={t} className="chip">
                {v.label(t)}
              </li>
            ))}
            <li className={`chip ${styles.chipNeutral}`}>
              {entry.senses.length} {entry.senses.length === 1 ? 'acepción' : 'acepciones'}
            </li>
          </ul>
          <div id="ws-headword" className={styles.headword} lang="es">
            {isPage ? (
              <PageTitle title={entry.headword}>{entry.headword}</PageTitle>
            ) : (
              <h2>{entry.headword}</h2>
            )}
          </div>
          {(sharedGrammar || foreign.length > 0) && (
            <p className={styles.meta}>
              {sharedGrammar && <em>{sharedGrammar}</em>}
              {foreign.map((s, i) => (
                <span key={`${s.languageId}${s.foreignForm}`}>
                  {(sharedGrammar || i > 0) && ' · '}
                  {v.label(s.languageId)}: <strong lang="und">{s.foreignForm}</strong>
                </span>
              ))}
            </p>
          )}
        </div>
        <div className={styles.actions}>
          <Link to={editHref} className="btn">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
              <path d="M4 20h4L19 9l-4-4L4 16z" />
            </svg>
            Editar
          </Link>
          <button type="button" className="btn btn-primary" onClick={() => setSending(true)}>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
              <path d="M3 11 21 3l-8 18-2-8z" />
            </svg>
            Enviar al aula
          </button>
        </div>
      </header>

      <EntryContent
        headword={entry.headword}
        senses={entry.senses}
        visibleFields={visibleFields}
        showHidden
        hideHeadword
      />

      <div className={styles.secondary}>
        <button
          type="button"
          className="btn"
          disabled={hide.busy}
          onClick={() => void hide.run(!entry.hidden)}
        >
          {entry.hidden ? 'Mostrar entrada' : 'Ocultar entrada'}
        </button>
        {entry.senses.length > 1 &&
          entry.senses.map((s, i) => (
            <button
              key={s.id}
              type="button"
              className="btn"
              disabled={hideSense.busy}
              onClick={() => void hideSense.run(s.id, !s.hidden)}
            >
              {s.hidden ? 'Mostrar' : 'Ocultar'} acepción {i + 1}
            </button>
          ))}
        <button type="button" className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
          Borrar
        </button>
        {entry.senses.length > 1 && (
          <p className="small muted">Las acepciones ocultas no se envían al aula.</p>
        )}
      </div>
      <ErrorMessage error={hide.error ?? hideSense.error} />

      <SendDialog
        open={sending}
        onClose={() => setSending(false)}
        entry={entry}
        editHref={editHref}
        classrooms={classrooms}
        onDone={() => void onChange()}
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
    </section>
  );
}

const STATUS_ICON: Record<SubmissionBrief['status'], string> = {
  pending: 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
  published: 'm5 12 5 5L20 7',
  rejected: 'M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3',
  withdrawn: 'M5 12h14',
};

function SubmissionsCard({ entry, onChange }: { entry: EntryView; onChange: () => Promise<void> }) {
  const notify = useNotify();
  const withdraw = useAction(async (submissionId: string) => {
    await (await getApi()).withdrawSubmission({ submissionId });
    notify('Envío retirado');
    await onChange();
  });
  return (
    <section className={styles.card} aria-labelledby="ws-subs">
      <h2 id="ws-subs">En el aula</h2>
      {entry.submissions.length === 0 ? (
        <p className="small muted">Todavía no has enviado esta palabra a ningún aula.</p>
      ) : (
        <ul className={styles.subs}>
          {entry.submissions.map((s) => (
            <li key={s.id}>
              <div className={styles.subHead}>
                <span className={`${styles.subIcon} ${styles[s.status]}`} aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                    <path d={STATUS_ICON[s.status]} />
                  </svg>
                </span>
                <div>
                  <p className={styles.subTitle}>{s.classroomTitle}</p>
                  <p className={`${styles.subStatus} ${styles[s.status]}`}>
                    {STATUS_LABEL[s.status]} · {formatDate(s.reviewedAt ?? s.submittedAt)}
                  </p>
                </div>
              </div>
              <ol
                className={styles.timeline}
                aria-label={`Historial del envío a ${s.classroomTitle}`}
              >
                <li>Enviada el {formatDate(s.submittedAt, true)}</li>
                {s.reviewedAt && <li>Revisada el {formatDate(s.reviewedAt)}</li>}
              </ol>
              {s.status === 'rejected' && s.reviewNote && (
                <p className={styles.note}>
                  <strong>Nota del profesorado:</strong> {s.reviewNote}
                </p>
              )}
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
      <ErrorMessage error={withdraw.error} />
    </section>
  );
}

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toLocaleUpperCase('es'))
    .join('');

function SendDialog({
  open,
  onClose,
  entry,
  editHref,
  classrooms,
  onDone,
}: {
  open: boolean;
  onClose: () => void;
  entry: EntryView;
  editHref: string;
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
    const dry = await (await getApi()).submitEntries({
      entryIds: [entry.id],
      classroomIds: chosen,
      dryRun: true,
    });
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
          <Link to={editHref}>Editar la entrada</Link>
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
