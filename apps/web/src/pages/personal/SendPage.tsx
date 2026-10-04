import type { DictionaryView, EntrySummary, SubmitResult, WindowState } from '@lexican/core';
import { useState } from 'react';
import { Link, useLoaderData, useRevalidator } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EmptyState, ErrorMessage, Field, PageTitle, StatusBadge, useAction } from '../../components/ui.tsx';
import styles from './personal.module.css';
import { CLOSED_WINDOW } from './windowLabels.ts';

interface Data {
  entries: EntrySummary[];
  classrooms: DictionaryView[];
}

export async function loader(): Promise<Data> {
  const api = await getApi();
  const [dict, classrooms] = await Promise.all([api.myDictionary({}), api.myClassrooms({})]);
  // Hidden entries are not offered (brief §5.13); the API maximum per call is 500 entries.
  const page = await api.listEntries({ dictionaryId: dict.id, includeHidden: false, limit: 200 });
  const rest = page.total > 200 ? await api.listEntries({ dictionaryId: dict.id, includeHidden: false, offset: 200, limit: 200 }) : null;
  return { entries: [...page.items, ...(rest?.items ?? [])], classrooms };
}


export function Component() {
  const { entries, classrooms } = useLoaderData<Data>();
  const open = classrooms.filter((c) => c.classroom?.windowState === 'open');
  const closed = classrooms.filter((c) => c.classroom && c.classroom.windowState !== 'open');
  const [classroomId, setClassroomId] = useState(open.length === 1 ? open[0]!.id : '');
  const [selected, setSelected] = useState<string[]>([]);
  const [check, setCheck] = useState<SubmitResult | null>(null);
  const [result, setResult] = useState<SubmitResult | null>(null);
  const { revalidate } = useRevalidator();

  const reset = () => {
    setCheck(null);
    setResult(null);
  };
  const toggle = (id: string, on: boolean) => {
    reset();
    setSelected(on ? [...selected, id] : selected.filter((x) => x !== id));
  };
  const all = entries.length > 0 && selected.length === entries.length;

  const verify = useAction(async () => {
    const api = await getApi();
    const dry = await api.submitEntries({ entryIds: selected, classroomIds: [classroomId], dryRun: true });
    if (dry.problems.length) return setCheck(dry);
    setResult(await api.submitEntries({ entryIds: selected, classroomIds: [classroomId] }));
    await revalidate();
  });
  const okIds = selected.filter((id) => !check?.problems.some((p) => p.entryId === id));
  const sendRest = useAction(async () => {
    setResult(await (await getApi()).submitEntries({ entryIds: okIds, classroomIds: [classroomId] }));
    setCheck(null);
    await revalidate();
  });

  return (
    <div className="stack">
      <p className="small">
        <Link to="/mi-diccionario">← Mi diccionario</Link>
      </p>
      <PageTitle>Enviar palabras al aula</PageTitle>

      {open.length === 0 ? (
        <EmptyState title="Ningún aula acepta envíos ahora">
          <p>
            Únete a un diccionario de aula con el código de tu profesor o profesora en <Link to="/aulas">Mis aulas</Link>,
            o espera a que se abra el plazo de envíos.
          </p>
        </EmptyState>
      ) : entries.length === 0 ? (
        <EmptyState title="No tienes palabras para enviar">
          <p>
            <Link to="/mi-diccionario/nueva">Crea una palabra</Link>. Las entradas ocultas no se pueden enviar desde aquí.
          </p>
        </EmptyState>
      ) : (
        <form
          onSubmit={(e) => {
            e.preventDefault();
            void verify.run();
          }}
          className="stack"
        >
          <Field label="Diccionario de aula">
            {(p) => (
              <select
                {...p}
                value={classroomId}
                onChange={(e) => {
                  reset();
                  setClassroomId(e.target.value);
                }}
              >
                <option value="">Elige un aula…</option>
                {open.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.title}
                  </option>
                ))}
              </select>
            )}
          </Field>

          <fieldset>
            <legend>Palabras que quieres enviar</legend>
            <p className="small muted">Las entradas ocultas no aparecen en esta lista.</p>
            <label className="check">
              <input
                type="checkbox"
                checked={all}
                onChange={(e) => {
                  reset();
                  setSelected(e.target.checked ? entries.map((x) => x.id) : []);
                }}
              />
              <strong>Seleccionar todas ({entries.length})</strong>
            </label>
            <ul className={styles.list}>
              {entries.map((x) => {
                const problems = check?.problems.filter((p) => p.entryId === x.id) ?? [];
                return (
                  <li key={x.id}>
                    <label className="check">
                      <input type="checkbox" checked={selected.includes(x.id)} onChange={(e) => toggle(x.id, e.target.checked)} />
                      <span lang="es">{x.headword}</span>
                    </label>
                    <span className="row small">
                      {x.submissions.map((s) => (
                        <span key={s.classroomTitle}>
                          {s.classroomTitle}: <StatusBadge status={s.status} />
                        </span>
                      ))}
                    </span>
                    {problems.length > 0 && (
                      <p className="error small" style={{ flexBasis: '100%', margin: 0 }}>
                        {problems.map((p) => p.message).join(' ')}{' '}
                        <Link to={`/mi-diccionario/entradas/${x.id}/editar`}>Corregir</Link>
                      </p>
                    )}
                  </li>
                );
              })}
            </ul>
          </fieldset>

          {check && (
            <div className="alert alert-warning" role="alert">
              {check.problems.length === 1 ? 'Una palabra no se puede enviar' : `${check.problems.length} palabras no se pueden enviar`}{' '}
              tal como están. Corrígelas o envía solo las demás.
            </div>
          )}
          {result && (
            <div className="alert alert-success" role="status">
              {result.created.length === 1 ? 'Se ha enviado 1 palabra.' : `Se han enviado ${result.created.length} palabras.`} Quedan
              pendientes de revisión por el profesorado.
            </div>
          )}
          <ErrorMessage error={verify.error ?? sendRest.error} />

          <div className="row">
            {check && okIds.length > 0 ? (
              <button type="button" className="btn btn-primary" disabled={sendRest.busy} onClick={() => void sendRest.run()}>
                Enviar las {okIds.length} sin problemas
              </button>
            ) : (
              <button type="submit" className="btn btn-primary" disabled={!classroomId || selected.length === 0 || verify.busy || !!check}>
                {verify.busy ? 'Comprobando…' : `Enviar ${selected.length || ''} ${selected.length === 1 ? 'palabra' : 'palabras'}`}
              </button>
            )}
          </div>
        </form>
      )}

      {closed.length > 0 && (
        <section aria-labelledby="closed-title" className="small muted">
          <h2 id="closed-title" className="small">
            Aulas que no admiten envíos ahora
          </h2>
          <ul>
            {closed.map((c) => (
              <li key={c.id}>
                {c.title}: {CLOSED_WINDOW[c.classroom!.windowState as Exclude<WindowState, 'open'>]}
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}
