import type { DictionaryView, MemberView, PublishResult, SubmissionStatus, SubmissionView } from '@lexican/core';
import { useState } from 'react';
import { Form, Link, useLoaderData, useRevalidator, useSearchParams, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EmptyState, ErrorMessage, Field, PageTitle, StatusBadge, formatDate, useAction, useNotify } from '../../components/ui.tsx';
import styles from './Classrooms.module.css';
import { BackToClassroom } from './shared.tsx';

/** URL slug ↔ status, so filters are linkable (?estado=devueltas). */
const STATUS_TABS: { slug: string; status: SubmissionStatus; label: string }[] = [
  { slug: 'pendientes', status: 'pending', label: 'Pendientes' },
  { slug: 'publicadas', status: 'published', label: 'Publicadas' },
  { slug: 'devueltas', status: 'rejected', label: 'Devueltas' },
  { slug: 'retiradas', status: 'withdrawn', label: 'Retiradas' },
];
const tabOf = (slug: string | null) => STATUS_TABS.find((t) => t.slug === slug) ?? STATUS_TABS[0]!;

interface Data {
  dictionary: DictionaryView;
  submissions: SubmissionView[];
  members: MemberView[];
}

export async function loader({ params, request }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const sp = new URL(request.url).searchParams;
  const classroomId = params.classroomId!;
  const [dictionary, submissions, members] = await Promise.all([
    api.getDictionary({ dictionaryId: classroomId }),
    api.listSubmissions({
      classroomId,
      status: tabOf(sp.get('estado')).status,
      studentId: sp.get('alumno') || undefined,
      q: sp.get('q') || undefined,
    }),
    api.listMembers({ classroomId }),
  ]);
  return { dictionary, submissions, members };
}

export function Component() {
  const { dictionary: d, submissions, members } = useLoaderData<Data>();
  const [params] = useSearchParams();
  const tab = tabOf(params.get('estado'));
  const students = members.filter((m) => m.role === 'student');
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [result, setResult] = useState<PublishResult | null>(null);
  const selectable = submissions.filter((s) => s.status === 'pending' && !s.conflict);
  const chosen = selectable.filter((s) => selected.has(s.id));

  const publish = useAction(async (ids: string[]) => {
    const r = await (await getApi()).publishSubmissions({ submissionIds: ids });
    setResult(r);
    setSelected(new Set());
    await revalidate();
    if (r.published.length) notify(`${r.published.length} ${r.published.length === 1 ? 'entrada publicada' : 'entradas publicadas'}.`);
  });

  const toggle = (id: string, on: boolean) =>
    setSelected((s) => {
      const n = new Set(s);
      if (on) n.add(id);
      else n.delete(id);
      return n;
    });
  const linkFor = (slug: string) => {
    const p = new URLSearchParams(params);
    p.set('estado', slug);
    return `?${p}`;
  };

  return (
    <>
      <BackToClassroom dictionary={d} />
      <PageTitle title={`Envíos · ${d.title}`}>Revisar envíos</PageTitle>
      <p className="muted">
        {d.pendingCount === 0 ? 'No hay envíos pendientes.' : `${d.pendingCount} ${d.pendingCount === 1 ? 'envío pendiente' : 'envíos pendientes'} de revisión.`}
      </p>

      <nav aria-label="Estado de los envíos">
        <ul className={styles.tabs}>
          {STATUS_TABS.map((t) => (
            <li key={t.slug}>
              <Link to={linkFor(t.slug)} aria-current={t.slug === tab.slug ? 'page' : undefined} onClick={() => setSelected(new Set())}>
                {t.label}
                {t.status === 'pending' && d.pendingCount > 0 && ` (${d.pendingCount})`}
              </Link>
            </li>
          ))}
        </ul>
      </nav>

      <Form key={params.toString()} method="get" role="search" aria-label="Filtrar envíos" className={styles.filters}>
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
          {(p) => <input {...p} type="search" name="q" defaultValue={params.get('q') ?? ''} />}
        </Field>
        <button type="submit" className="btn">
          Filtrar
        </button>
      </Form>

      {result && (result.conflicts.length > 0 || result.published.length > 0) && (
        <div className={`alert ${result.conflicts.length ? 'alert-warning' : 'alert-success'}`} role="status" style={{ marginTop: 'var(--space-4)' }}>
          <p>
            {result.published.length} {result.published.length === 1 ? 'entrada publicada' : 'entradas publicadas'}.
            {result.conflicts.length > 0 && ` ${result.conflicts.length} no se ${result.conflicts.length === 1 ? 'ha' : 'han'} podido publicar:`}
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
      <ErrorMessage error={publish.error} />

      <div style={{ marginTop: 'var(--space-4)' }}>
        {submissions.length === 0 ? (
          <EmptyState title={`No hay envíos ${tab.label.toLowerCase()}`}>
            {(params.get('q') || params.get('alumno')) && <p>Prueba a quitar los filtros.</p>}
          </EmptyState>
        ) : (
          <>
            {tab.status === 'pending' && (
              <div className="spread" style={{ marginBottom: 'var(--space-3)' }}>
                <label className="check">
                  <input
                    type="checkbox"
                    checked={selectable.length > 0 && chosen.length === selectable.length}
                    disabled={selectable.length === 0}
                    onChange={(e) => setSelected(e.target.checked ? new Set(selectable.map((s) => s.id)) : new Set())}
                  />
                  <span>Seleccionar todas</span>
                </label>
                <button type="button" className="btn btn-primary" disabled={chosen.length === 0 || publish.busy} onClick={() => void publish.run(chosen.map((s) => s.id))}>
                  {publish.busy ? 'Publicando…' : `Publicar seleccionadas (${chosen.length})`}
                </button>
              </div>
            )}
            <div className="table-wrap">
              <table>
                <caption className="visually-hidden">Envíos {tab.label.toLowerCase()}</caption>
                <thead>
                  <tr>
                    {tab.status === 'pending' && (
                      <th scope="col">
                        <span className="visually-hidden">Seleccionar</span>
                      </th>
                    )}
                    <th scope="col">Palabra</th>
                    <th scope="col">Alumno/a</th>
                    <th scope="col">Enviada</th>
                    <th scope="col">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {submissions.map((s) => (
                    <tr key={s.id}>
                      {tab.status === 'pending' && (
                        <td>
                          <input
                            type="checkbox"
                            aria-label={`Seleccionar ${s.snapshot.headword}`}
                            checked={selected.has(s.id)}
                            disabled={!!s.conflict}
                            onChange={(e) => toggle(s.id, e.target.checked)}
                          />
                        </td>
                      )}
                      <th scope="row">
                        <Link to={`/aulas/${d.id}/envios/${s.id}`}>{s.snapshot.headword}</Link>
                        {s.conflict && (
                          <div className="small" style={{ color: 'var(--c-warning)', fontWeight: 600 }}>
                            <span aria-hidden="true">⚠</span> Ya existe «{s.conflict.headword}» publicada
                          </div>
                        )}
                      </th>
                      <td>{s.submittedBy.displayName}</td>
                      <td>{formatDate(s.submittedAt, true)}</td>
                      <td>
                        <StatusBadge status={s.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}
      </div>
    </>
  );
}
