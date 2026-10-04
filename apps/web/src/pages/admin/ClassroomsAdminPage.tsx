import { formatSchoolYear, lastCurrentSchoolYear, type AdminClassroomView } from '@lexican/core';
import { useLoaderData, useRevalidator, useSearchParams, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../../api/index.ts';
import { ErrorMessage, PageTitle, useAction, useNotify } from '../../components/ui.tsx';
import { AdminNav } from './AdminNav.tsx';
import { requireStaff } from './guard.ts';

const FILTERS = { all: 'Todos', current: 'Vigentes', expired: 'No vigentes', timeless: 'Atemporales' } as const;
type Filter = keyof typeof FILTERS;

export async function loader({ request }: LoaderFunctionArgs): Promise<AdminClassroomView[]> {
  await requireStaff();
  const f = new URL(request.url).searchParams.get('filtro') as Filter | null;
  return (await getApi()).adminListClassrooms({ filter: f && f in FILTERS ? f : 'all' });
}

/** Replaces Voyager "vigencia": validity is computed, admins only toggle timeless or reactivate (RULE-007, capped). */
export function Component() {
  const rows = useLoaderData() as AdminClassroomView[];
  const [params, setParams] = useSearchParams();
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const update = useAction(async (classroomId: string, patch: { timeless?: boolean; reactivate?: boolean }) => {
    await (await getApi()).adminUpdateValidity({ classroomId, ...patch });
    notify('Vigencia actualizada');
    await revalidate();
  });
  const filter = (params.get('filtro') ?? 'all') as Filter;
  return (
    <>
      <PageTitle>Vigencia de diccionarios de aula</PageTitle>
      <AdminNav />
      <p className="muted prose">
        Un diccionario de aula está vigente mientras no hayan pasado tantos cursos como su vigencia, o siempre si es
        atemporal. Los no vigentes quedan en solo lectura.
      </p>
      <div className="row" role="group" aria-label="Filtrar">
        {(Object.keys(FILTERS) as Filter[]).map((f) => (
          <button key={f} type="button" className={`btn btn-sm${f === filter ? ' btn-primary' : ''}`} aria-pressed={f === filter} onClick={() => setParams({ filtro: f })}>
            {FILTERS[f]}
          </button>
        ))}
      </div>
      <ErrorMessage error={update.error} />
      <div className="table-wrap" style={{ marginTop: 'var(--space-4)' }}>
        <table>
          <caption className="visually-hidden">Diccionarios de aula</caption>
          <thead>
            <tr>
              <th scope="col">Diccionario</th>
              <th scope="col">Curso</th>
              <th scope="col">Vigencia</th>
              <th scope="col">Estado</th>
              <th scope="col">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((c) => {
              const last = lastCurrentSchoolYear(c);
              return (
                <tr key={c.id}>
                  <th scope="row">
                    {c.title}
                    <br />
                    <span className="small muted">
                      {c.owner} · {c.memberCount} participantes · {c.entryCount} entradas
                    </span>
                  </th>
                  <td>{formatSchoolYear(c.schoolYear)}</td>
                  <td>{c.timeless ? 'Atemporal' : `${c.validityYears} curso(s), hasta ${formatSchoolYear(last!)}`}</td>
                  <td>{c.current ? 'Vigente' : 'No vigente'}</td>
                  <td>
                    <div className="row">
                      <button type="button" className="btn btn-sm" disabled={update.busy} onClick={() => update.run(c.id, { timeless: !c.timeless })}>
                        {c.timeless ? 'Quitar atemporal' : 'Hacer atemporal'}
                      </button>
                      {!c.current && (
                        <button type="button" className="btn btn-sm" disabled={update.busy} onClick={() => update.run(c.id, { reactivate: true })}>
                          Reactivar este curso
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </>
  );
}
