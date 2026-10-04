import { isRouteErrorResponse, Link, useRouteError } from 'react-router';
import { toApiError } from '../api/index.ts';
import { PageTitle } from '../components/ui.tsx';

/** Distinguishes not found / forbidden / session / network / internal (§64). No stack traces. */
export function RouteError() {
  const error = useRouteError();
  if (isRouteErrorResponse(error) && error.status === 404)
    return <Message title="Página no encontrada" text="La dirección no existe." />;
  const e = toApiError(error);
  const titles: Record<string, string> = {
    not_found: 'No encontrado',
    forbidden: 'Sin permiso',
    unauthenticated: 'Sesión caducada',
    network: 'Sin conexión',
  };
  return (
    <Message title={titles[e.code] ?? 'Algo ha fallado'} text={e.message}>
      {e.code === 'unauthenticated' && <Link to="/entrar">Volver a entrar</Link>}
    </Message>
  );
}

function Message({
  title,
  text,
  children,
}: {
  title: string;
  text: string;
  children?: React.ReactNode;
}) {
  return (
    <div className="card" role="alert">
      <PageTitle>{title}</PageTitle>
      <p>{text}</p>
      <p className="row">
        {children}
        <Link to="/">Ir al inicio</Link>
      </p>
    </div>
  );
}
