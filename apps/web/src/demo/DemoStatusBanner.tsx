import { useSyncExternalStore } from 'react';
import { getApi } from '../api/index.ts';
import { getDemoStatus, subscribeDemoStatus } from './status.ts';

/** Start-up progress and recoverable errors of the in-browser demo engine (never a blank page). */
export function DemoStatusBanner() {
  const s = useSyncExternalStore(subscribeDemoStatus, getDemoStatus);
  if (s.state === 'starting')
    return (
      <p className="small muted" role="status">
        Preparando la demo en este navegador…
      </p>
    );
  if (s.state === 'resetting')
    return (
      <p className="small muted" role="status">
        Restableciendo los datos de demostración…
      </p>
    );
  if (s.state !== 'error') return null;
  return (
    <div className="alert alert-error" role="alert">
      <p>
        <strong>{s.title}.</strong> {s.message}
      </p>
      <p className="row">
        <button type="button" className="btn btn-sm" onClick={() => location.reload()}>
          Reintentar
        </button>
        {s.canReset && (
          <button
            type="button"
            className="btn btn-sm"
            onClick={async () => (await getApi()).resetDemo?.()}
          >
            Restablecer datos de demostración
          </button>
        )}
      </p>
    </div>
  );
}
