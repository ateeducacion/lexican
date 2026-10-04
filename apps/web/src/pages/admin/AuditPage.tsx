import type { AuditEventView } from '@lexican/core';
import { useLoaderData } from 'react-router';
import { getApi } from '../../api/index.ts';
import { formatDate, PageTitle } from '../../components/ui.tsx';
import { AdminNav } from './AdminNav.tsx';
import { requireStaff } from './guard.ts';

export async function loader(): Promise<AuditEventView[]> {
  await requireStaff(true);
  return (await getApi()).adminAudit({ limit: 200 });
}

const ACTIONS: Record<string, string> = {
  'auth.login': 'Inicio de sesión',
  'auth.logout': 'Cierre de sesión',
  'classroom.create': 'Crea diccionario de aula',
  'classroom.delete': 'Borra diccionario de aula',
  'classroom.validity': 'Cambia la vigencia',
  'membership.update': 'Cambia una participación',
  'submission.publish': 'Publica un envío',
  'submission.reject': 'Devuelve un envío',
  'entry.teacher_edit': 'Edita una entrada publicada',
  'entry.unpublish': 'Despublica una entrada',
  'comment.create': 'Escribe un comentario',
  'user.role': 'Cambia un rol',
  'vocabulary.save': 'Edita una lista de valores',
};

export function Component() {
  const events = useLoaderData() as AuditEventView[];
  return (
    <>
      <PageTitle>Auditoría</PageTitle>
      <AdminNav />
      <p className="muted prose">Últimas acciones sensibles. No se guardan contenidos ni datos personales.</p>
      <div className="table-wrap">
        <table>
          <caption className="visually-hidden">Eventos de auditoría</caption>
          <thead>
            <tr>
              <th scope="col">Fecha</th>
              <th scope="col">Persona</th>
              <th scope="col">Acción</th>
            </tr>
          </thead>
          <tbody>
            {events.map((e) => (
              <tr key={e.id}>
                <td>{formatDate(e.createdAt, true)}</td>
                <td>{e.actor ?? '—'}</td>
                <td>{ACTIONS[e.action] ?? e.action}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
