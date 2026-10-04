import { NavLink } from 'react-router';
import { useSession } from '../../session.ts';

export function AdminNav() {
  const { user } = useSession();
  const admin = user.globalRole === 'admin';
  return (
    <nav aria-label="Administración" className="row" style={{ marginBottom: 'var(--space-4)' }}>
      <NavLink className="btn btn-sm" to="/admin" end>
        Resumen
      </NavLink>
      <NavLink className="btn btn-sm" to="/admin/aulas">
        Vigencia de aulas
      </NavLink>
      {admin && (
        <>
          <NavLink className="btn btn-sm" to="/admin/usuarios">
            Usuarios y roles
          </NavLink>
          <NavLink className="btn btn-sm" to="/admin/listas">
            Listas de valores
          </NavLink>
          <NavLink className="btn btn-sm" to="/admin/auditoria">
            Auditoría
          </NavLink>
        </>
      )}
    </nav>
  );
}
