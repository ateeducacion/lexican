import type { SubmissionStatus } from '@lexican/core';
import {
  createContext,
  type ReactNode,
  useCallback,
  useContext,
  useEffect,
  useId,
  useRef,
  useState,
} from 'react';
import { type ApiError, toApiError } from '../api/index.ts';

/** Sets the document title and gives the page a single h1 (§61 headings, route announcements). */
export function PageTitle({ children, title }: { children: ReactNode; title?: string }) {
  const text = title ?? (typeof children === 'string' ? children : '');
  useEffect(() => {
    document.title = text ? `${text} · LexiCán` : 'LexiCán';
  }, [text]);
  return <h1>{children}</h1>;
}

export function ErrorMessage({ error }: { error: unknown }) {
  if (!error) return null;
  const e: ApiError = toApiError(error);
  return (
    <div className="alert alert-error" role="alert">
      {e.message}
    </div>
  );
}

export const STATUS_LABEL: Record<SubmissionStatus, string> = {
  pending: 'Pendiente de revisión',
  published: 'Publicada',
  rejected: 'Devuelta',
  withdrawn: 'Retirada',
};

/** Status is conveyed by text and icon, not only by colour (§61). */
export function StatusBadge({ status }: { status: SubmissionStatus }) {
  const icon = { pending: '⏳', published: '✔', rejected: '↩', withdrawn: '–' }[status];
  return (
    <span className={`badge badge-${status}`}>
      <span aria-hidden="true">{icon}</span>
      {STATUS_LABEL[status]}
    </span>
  );
}

export function EmptyState({ title, children }: { title: string; children?: ReactNode }) {
  return (
    <div className="card" style={{ textAlign: 'center', padding: 'var(--space-6) var(--space-4)' }}>
      <h2>{title}</h2>
      {children}
    </div>
  );
}

/** Label + control + hint + error, wired with aria-describedby / aria-invalid. */
export function Field({
  label,
  hint,
  error,
  children,
}: {
  label: ReactNode;
  hint?: ReactNode;
  error?: string[] | string | undefined;
  children: (props: {
    id: string;
    'aria-describedby'?: string;
    'aria-invalid'?: true;
  }) => ReactNode;
}) {
  const id = useId();
  const errors = typeof error === 'string' ? [error] : (error ?? []);
  const describedBy = [hint ? `${id}-hint` : null, errors.length ? `${id}-error` : null]
    .filter(Boolean)
    .join(' ');
  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      {hint && (
        <span id={`${id}-hint`} className="hint">
          {hint}
        </span>
      )}
      {children({
        id,
        ...(describedBy ? { 'aria-describedby': describedBy } : {}),
        ...(errors.length ? { 'aria-invalid': true as const } : {}),
      })}
      {errors.length > 0 && (
        <span id={`${id}-error`} className="error">
          {errors.join(' ')}
        </span>
      )}
    </div>
  );
}

/** Native modal dialog: focus trap, Escape and inert background come from the platform. */
export function Dialog({
  open,
  onClose,
  title,
  children,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
}) {
  const ref = useRef<HTMLDialogElement>(null);
  const titleId = useId();
  useEffect(() => {
    const d = ref.current;
    if (!d) return;
    if (open && !d.open) d.showModal();
    if (!open && d.open) d.close();
  }, [open]);
  return (
    <dialog ref={ref} aria-labelledby={titleId} onClose={onClose}>
      <h2 id={titleId}>{title}</h2>
      {open && children}
    </dialog>
  );
}

type Notice = { id: number; text: string; tone: 'success' | 'error' | 'info' };
const NotifyContext = createContext<(text: string, tone?: Notice['tone']) => void>(() => undefined);

/** Polite live region for confirmations ("Entrada guardada"); never alert(). */
export function NotifyProvider({ children }: { children: ReactNode }) {
  const [notices, setNotices] = useState<Notice[]>([]);
  const notify = useCallback((text: string, tone: Notice['tone'] = 'success') => {
    const id = Date.now() + Math.random();
    setNotices((n) => [...n, { id, text, tone }]);
    setTimeout(() => setNotices((n) => n.filter((x) => x.id !== id)), 5000);
  }, []);
  return (
    <NotifyContext.Provider value={notify}>
      {children}
      <div
        aria-live="polite"
        role="status"
        style={{
          position: 'fixed',
          bottom: '1rem',
          right: '1rem',
          left: '1rem',
          display: 'grid',
          justifyItems: 'end',
          gap: '0.5rem',
          zIndex: 20,
          pointerEvents: 'none',
        }}
      >
        {notices.map((n) => (
          <div
            key={n.id}
            className={`alert alert-${n.tone === 'info' ? 'info' : n.tone}`}
            style={{ margin: 0, boxShadow: 'var(--shadow)', maxWidth: '28rem' }}
          >
            {n.text}
          </div>
        ))}
      </div>
    </NotifyContext.Provider>
  );
}

export const useNotify = () => useContext(NotifyContext);

/** Run an async action with busy/error state, for buttons and small forms. */
export function useAction<A extends unknown[], R>(fn: (...args: A) => Promise<R>) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const run = useCallback(
    async (...args: A): Promise<R | undefined> => {
      setBusy(true);
      setError(null);
      try {
        return await fn(...args);
      } catch (e) {
        setError(toApiError(e));
        return undefined;
      } finally {
        setBusy(false);
      }
    },
    [fn],
  );
  return { run, busy, error, setError };
}

export function Loading({ label = 'Cargando…' }: { label?: string }) {
  return (
    <p className="muted" role="status">
      {label}
    </p>
  );
}

export const formatDate = (iso: string | null | undefined, withTime = false): string =>
  iso
    ? new Intl.DateTimeFormat(
        'es-ES',
        withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' },
      ).format(new Date(iso))
    : '';
