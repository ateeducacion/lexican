import type { WindowState } from '@lexican/core';

/** Why a classroom does not accept submissions right now (student wording). */
export const CLOSED_WINDOW: Record<Exclude<WindowState, 'open'>, string> = {
  expired: 'ya no está vigente',
  disabled: 'el profesorado no admite envíos ahora',
  not_started: 'el plazo de envíos aún no ha empezado',
  ended: 'el plazo de envíos ha terminado',
};
