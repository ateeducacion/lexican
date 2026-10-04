import { Link } from 'react-router';
import { PageTitle } from '../components/ui.tsx';

export function Component() {
  return (
    <div className="card">
      <PageTitle>Página no encontrada</PageTitle>
      <p>
        La dirección no existe. <Link to="/">Ir al inicio</Link>
      </p>
    </div>
  );
}
