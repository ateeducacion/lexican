import { Link } from 'react-router';
import styles from './Tiles.module.css';

/** Line icons drawn for LexiCán (CC0), in the spirit of the original big action tiles. */
const ICONS = {
  add: (
    <>
      <path d="M10 46V14a4 4 0 0 1 4-4h22v40H14a4 4 0 0 0-4 4" />
      <path d="M10 54a4 4 0 0 1 4-4h22" />
      <path d="M18 18h12M18 25h12" />
      <path d="m43 40 11-11 4 4-11 11-6 2z" />
      <path d="M50 33l4 4" />
    </>
  ),
  send: (
    <>
      <path d="M8 34 56 12 44 54l-12-14-14 8z" />
      <path d="m32 40 24-28" />
      <path d="M12 12h14a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h-6l-5 4v-4h-3a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3z" />
    </>
  ),
  export: (
    <>
      <path d="M18 24V8h28v16" />
      <rect x="8" y="24" width="48" height="20" rx="4" />
      <path d="M18 38h28v18H18z" />
      <path d="M24 44h16M24 50h10" />
    </>
  ),
  manage: (
    <>
      <circle cx="40" cy="26" r="16" />
      <path d="M33 31l4-10 4 10M34.5 28h5M44 21v10h3a2.5 2.5 0 0 0 0-5h-3 2.5a2.5 2.5 0 0 0 0-5z" />
      <circle cx="18" cy="44" r="7" />
      <path d="M18 31v4M18 53v4M5 44h4M27 44h4M9 35l3 3M24 50l3 3M9 53l3-3M24 38l3-3" />
    </>
  ),
  classroom: (
    <>
      <rect x="8" y="10" width="48" height="30" rx="3" />
      <path d="M32 40v8M20 54h24" />
      <path d="M16 20h20M16 27h14" />
      <circle cx="46" cy="25" r="5" />
    </>
  ),
  review: (
    <>
      <path d="M14 8h26l10 10v38H14z" />
      <path d="M40 8v10h10" />
      <path d="m22 36 6 6 12-14" />
    </>
  ),
} as const;

export type TileIcon = keyof typeof ICONS;

export function Tile({
  to,
  icon,
  label,
  badge,
}: {
  to: string;
  icon: TileIcon;
  label: string;
  badge?: number;
}) {
  return (
    <Link to={to} className={styles.tile}>
      <svg viewBox="0 0 64 64" aria-hidden="true" className={styles.icon}>
        {ICONS[icon]}
      </svg>
      <span className={styles.label}>{label}</span>
      {badge ? <span className={styles.badge}>{badge}</span> : null}
    </Link>
  );
}

export function Tiles({ children, label }: { children: React.ReactNode; label: string }) {
  return (
    <nav aria-label={label}>
      <ul className={styles.tiles}>
        {(Array.isArray(children) ? children : [children]).map((c, i) => (
          <li key={i}>{c}</li>
        ))}
      </ul>
    </nav>
  );
}
