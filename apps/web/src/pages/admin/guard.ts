import type { UserView } from '@lexican/core';
import { getApi } from '../../api/index.ts';
import { ApiError } from '../../api/types.ts';

/** Admin pages: friendly 403 before any call; the API enforces the same rule. */
export async function requireStaff(adminOnly = false): Promise<UserView> {
  const { user } = await (await getApi()).me({});
  const ok = user && (user.globalRole === 'admin' || (!adminOnly && user.globalRole === 'support'));
  if (!ok) throw new ApiError('forbidden', 'Esta sección es solo para administración.');
  return user;
}
