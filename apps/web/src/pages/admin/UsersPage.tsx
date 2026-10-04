import { GLOBAL_ROLES, type GlobalRole, type UserView } from '@lexican/core';
import { Form, useLoaderData, useRevalidator, useSearchParams, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../../api/index.ts';
import { ErrorMessage, PageTitle, useAction, useNotify } from '../../components/ui.tsx';
import { ROLE_LABEL, useSession } from '../../session.ts';
import { AdminNav } from './AdminNav.tsx';
import { requireStaff } from './guard.ts';

export async function loader({ request }: LoaderFunctionArgs): Promise<UserView[]> {
  await requireStaff(true);
  const q = new URL(request.url).searchParams.get('q') ?? undefined;
  return (await getApi()).adminListUsers(q ? { q } : {});
}

export function Component() {
  const users = useLoaderData() as UserView[];
  const { user: me } = useSession();
  const [params] = useSearchParams();
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const setRole = useAction(async (userId: string, globalRole: GlobalRole) => {
    await (await getApi()).adminSetUserRole({ userId, globalRole });
    notify('Rol actualizado');
    await revalidate();
  });
  return (
    <>
      <PageTitle>Usuarios y roles</PageTitle>
      <AdminNav />
      <p className="muted prose">
        Profesorado y alumnado se asignan automáticamente desde el directorio educativo en cada acceso. Aquí solo se
        conceden los roles de administración y oficina técnica.
      </p>
      <Form role="search" className="row" style={{ marginBottom: 'var(--space-4)' }}>
        <label htmlFor="q" className="visually-hidden">
          Buscar por nombre o correo
        </label>
        <input id="q" type="search" name="q" defaultValue={params.get('q') ?? ''} placeholder="Nombre o correo" style={{ maxWidth: '20rem' }} />
        <button className="btn" type="submit">
          Buscar
        </button>
      </Form>
      <ErrorMessage error={setRole.error} />
      <div className="table-wrap">
        <table>
          <caption className="visually-hidden">Usuarios</caption>
          <thead>
            <tr>
              <th scope="col">Nombre</th>
              <th scope="col">Correo</th>
              <th scope="col">Rol</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id}>
                <th scope="row">{u.displayName}</th>
                <td>{u.email ?? '—'}</td>
                <td>
                  <label className="visually-hidden" htmlFor={`role-${u.id}`}>
                    Rol de {u.displayName}
                  </label>
                  <select
                    id={`role-${u.id}`}
                    value={u.globalRole}
                    disabled={u.id === me.id || setRole.busy}
                    onChange={(e) => setRole.run(u.id, e.target.value as GlobalRole)}
                    style={{ maxWidth: '14rem' }}
                  >
                    {GLOBAL_ROLES.map((r) => (
                      <option key={r} value={r}>
                        {ROLE_LABEL[r]}
                      </option>
                    ))}
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
