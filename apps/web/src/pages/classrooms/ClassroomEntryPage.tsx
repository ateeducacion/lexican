import type { DictionaryView, EntryView } from '@lexican/core';
import { useState } from 'react';
import { Link, useLoaderData, useNavigate, useRevalidator, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryContent } from '../../components/EntryContent.tsx';
import { EntryEditor } from '../../components/EntryEditor.tsx';
import { Dialog, ErrorMessage, PageTitle, formatDate, useAction, useNotify } from '../../components/ui.tsx';
import styles from './Classrooms.module.css';
import { BackToClassroom } from './shared.tsx';

export async function loader({ params }: LoaderFunctionArgs) {
  const api = await getApi();
  const [dictionary, entry] = await Promise.all([
    api.getDictionary({ dictionaryId: params.classroomId! }),
    api.getEntry({ entryId: params.entryId! }),
  ]);
  return { dictionary, entry };
}

export function Component() {
  const { dictionary: d, entry } = useLoaderData<{ dictionary: DictionaryView; entry: EntryView }>();
  const teacher = d.myRole === 'teacher';
  const { revalidate } = useRevalidator();
  const navigate = useNavigate();
  const notify = useNotify();
  const [editing, setEditing] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const act = useAction(async (fn: () => Promise<unknown>, message: string) => {
    await fn();
    await revalidate();
    notify(message);
  });
  const remove = useAction(async () => {
    await (await getApi()).deleteEntry({ entryId: entry.id });
    notify(`«${entry.headword}» ya no está publicada.`);
    navigate(`/aulas/${d.id}`, { replace: true });
  });

  const by = entry.author?.displayName;
  const provenance = by ? (entry.sourceSubmissionId ? `Enviada por ${by}` : `Añadida por ${by}`) : null;

  if (editing)
    return (
      <>
        <BackToClassroom dictionary={d} />
        <PageTitle title={`Editar ${entry.headword}`}>Editar «{entry.headword}»</PageTitle>
        <EntryEditor
          entry={entry}
          classroom={d}
          submitLabel="Guardar cambios"
          onSave={async (input) => {
            const saved = await (await getApi()).updateEntry({ entryId: entry.id, version: entry.version, entry: input });
            await revalidate();
            setEditing(false);
            notify('Entrada guardada.');
            return saved;
          }}
          onCancel={() => setEditing(false)}
        />
      </>
    );

  return (
    <>
      <BackToClassroom dictionary={d} />
      {/* The entry card shows the headword large; the h1 names the page for assistive tech. */}
      <div className="visually-hidden">
        <PageTitle title={entry.headword}>Entrada: {entry.headword}</PageTitle>
      </div>
      <div className="card">
        <EntryContent headword={entry.headword} senses={entry.senses} visibleFields={d.classroom?.visibleFields} showHidden={teacher} headingLevel={2} />
      </div>
      <p className="small muted" style={{ marginTop: 'var(--space-3)' }}>
        {[provenance, `actualizada el ${formatDate(entry.updatedAt)}`].filter(Boolean).join(' · ')}
        {entry.hidden && (
          <>
            {' '}
            <span className="badge badge-hidden">Oculta para el alumnado</span>
          </>
        )}
      </p>

      {teacher && (
        <div className={`no-print ${styles.toolbar}`} role="group" aria-label="Acciones de la entrada">
          <button type="button" className="btn btn-primary" onClick={() => setEditing(true)}>
            Editar
          </button>
          <button
            type="button"
            className="btn"
            disabled={act.busy}
            onClick={async () =>
              void act.run(
                async () => (await getApi()).setEntryHidden({ entryId: entry.id, hidden: !entry.hidden }),
                entry.hidden ? 'La entrada vuelve a estar visible.' : 'Entrada oculta para el alumnado.',
              )
            }
          >
            {entry.hidden ? 'Mostrar entrada' : 'Ocultar entrada'}
          </button>
          <button type="button" className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
            Despublicar
          </button>
        </div>
      )}
      <ErrorMessage error={act.error} />


      {teacher && entry.senses.length > 0 && (
        <section className={`card no-print ${styles.section}`} aria-labelledby="senses-title">
          <h2 id="senses-title">Visibilidad de las acepciones</h2>
          <ul className={styles.senseRows}>
            {entry.senses.map((s, i) => (
              <li key={s.id}>
                <span>
                  {i + 1}. {s.definition.length > 80 ? `${s.definition.slice(0, 80)}…` : s.definition}{' '}
                  {s.hidden && <span className="badge badge-hidden">Oculta</span>}
                </span>
                <button
                  type="button"
                  className="btn btn-sm"
                  disabled={act.busy}
                  onClick={async () =>
                    void act.run(
                      async () => (await getApi()).setSenseHidden({ senseId: s.id, hidden: !s.hidden }),
                      s.hidden ? `Acepción ${i + 1} visible.` : `Acepción ${i + 1} oculta para el alumnado.`,
                    )
                  }
                >
                  {s.hidden ? 'Mostrar' : 'Ocultar'}
                  <span className="visually-hidden"> acepción {i + 1}</span>
                </button>
              </li>
            ))}
          </ul>
        </section>
      )}

      <Dialog open={confirmDelete} onClose={() => setConfirmDelete(false)} title={`¿Despublicar «${entry.headword}»?`}>
        <p>La entrada desaparecerá del diccionario de aula. La entrada original sigue en el diccionario personal de quien la envió, que podrá volver a enviarla.</p>
        <ErrorMessage error={remove.error} />
        <div className="row">
          <button type="button" className="btn btn-danger" onClick={() => void remove.run()} disabled={remove.busy}>
            Despublicar
          </button>
          <button type="button" className="btn" onClick={() => setConfirmDelete(false)}>
            Cancelar
          </button>
        </div>
      </Dialog>
      <p className="no-print" style={{ marginTop: 'var(--space-5)' }}>
        <Link to={`/aulas/${d.id}`}>Volver al diccionario</Link>
      </p>
    </>
  );
}
