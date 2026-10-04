import { formatSchoolYear, type StatsView } from '@lexican/core';
import { useLoaderData } from 'react-router';
import { getApi } from '../../api/index.ts';
import { PageTitle } from '../../components/ui.tsx';
import { AdminNav } from './AdminNav.tsx';
import { requireStaff } from './guard.ts';

export async function loader(): Promise<StatsView> {
  await requireStaff();
  return (await getApi()).adminStats({});
}

const csv = (s: StatsView) =>
  ['curso,diccionarios_aula,vigentes,participantes,entradas_publicadas']
    .concat(s.bySchoolYear.map((y) => [formatSchoolYear(y.schoolYear), y.classrooms, y.current, y.members, y.published].join(',')))
    .join('\n');

export function Component() {
  const s = useLoaderData() as StatsView;
  const download = () => {
    const url = URL.createObjectURL(new Blob([csv(s)], { type: 'text/csv;charset=utf-8' }));
    const a = Object.assign(document.createElement('a'), { href: url, download: 'lexican-estadisticas.csv' });
    a.click();
    URL.revokeObjectURL(url);
  };
  const t = s.totals;
  return (
    <>
      <PageTitle>Administración</PageTitle>
      <AdminNav />
      <ul className="grid" style={{ listStyle: 'none', padding: 0 }}>
        {[
          ['Usuarios', t.users],
          ['Diccionarios personales', t.personalDictionaries],
          ['Entradas personales', t.personalEntries],
          ['Diccionarios de aula', t.classrooms],
          ['Envíos', t.submissions],
        ].map(([label, n]) => (
          <li key={label} className="card">
            <span className="muted small">{label}</span>
            <br />
            <strong style={{ fontSize: '1.8rem' }}>{n}</strong>
          </li>
        ))}
      </ul>
      <div className="spread" style={{ marginTop: 'var(--space-5)' }}>
        <h2>Por curso escolar</h2>
        <button type="button" className="btn btn-sm" onClick={download}>
          Descargar CSV
        </button>
      </div>
      <div className="table-wrap">
        <table>
          <caption className="visually-hidden">Diccionarios de aula por curso escolar</caption>
          <thead>
            <tr>
              <th scope="col">Curso</th>
              <th scope="col">Diccionarios de aula</th>
              <th scope="col">Vigentes</th>
              <th scope="col">Participantes</th>
              <th scope="col">Entradas publicadas</th>
            </tr>
          </thead>
          <tbody>
            {s.bySchoolYear.map((y) => (
              <tr key={y.schoolYear}>
                <th scope="row">{formatSchoolYear(y.schoolYear)}</th>
                <td>{y.classrooms}</td>
                <td>{y.current}</td>
                <td>{y.members}</td>
                <td>{y.published}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
