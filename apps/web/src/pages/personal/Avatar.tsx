/** Closed list of dictionary avatars: coloured initials, no images or emoji (contrast ≥ 4.5:1 with white). */
export const AVATARS = [
  { id: 'default', label: 'Verde laurisilva', color: '#14635a' },
  { id: 'mar', label: 'Azul atlántico', color: '#1f4e8c' },
  { id: 'volcan', label: 'Rojo volcán', color: '#a3271b' },
  { id: 'drago', label: 'Verde drago', color: '#3d6b1f' },
  { id: 'atardecer', label: 'Naranja atardecer', color: '#9a4a00' },
  { id: 'malpais', label: 'Gris malpaís', color: '#4a4f55' },
  { id: 'violeta', label: 'Violeta del Teide', color: '#6a3d9a' },
  { id: 'cardon', label: 'Turquesa cardón', color: '#0f6470' },
] as const;

export function Avatar({
  id,
  title,
  size = 56,
}: {
  id: string | null;
  title: string;
  size?: number;
}) {
  const color = (AVATARS.find((a) => a.id === id) ?? AVATARS[0]).color;
  return (
    <span
      aria-hidden="true"
      style={{
        display: 'inline-grid',
        placeItems: 'center',
        flex: 'none',
        width: size,
        height: size,
        borderRadius: '50%',
        background: color,
        color: '#fff',
        fontWeight: 700,
        fontSize: size * 0.42,
        fontFamily: 'var(--font-serif)',
      }}
    >
      {(title.trim().charAt(0) || 'L').toLocaleUpperCase('es')}
    </span>
  );
}
