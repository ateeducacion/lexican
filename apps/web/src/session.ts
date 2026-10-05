import type { UserView, VocabularyValue } from '@lexican/core';
import { redirect, useRouteLoaderData } from 'react-router';
import { getApi } from './api/index.ts';
import { readDemoSession } from './demo/session.ts';

export interface RootData {
  user: UserView | null;
  vocab: VocabularyValue[];
}

let vocabCache: VocabularyValue[] | null = null;

/** Root loader: who is signed in, plus the controlled vocabularies (cached for the session). */
export async function rootLoader(): Promise<RootData> {
  if (__DEMO__) {
    // Demo without a session: render immediately; PGlite starts loading from the login page.
    if (!readDemoSession()) return { user: null, vocab: [] };
  }
  const api = await getApi();
  const { user } = await api.me({});
  if (user && !vocabCache) vocabCache = await api.vocabularies({});
  return { user, vocab: vocabCache ?? [] };
}

export const invalidateVocabulary = () => {
  vocabCache = null;
};

export function useSession(): RootData & { user: UserView } {
  const data = useRouteLoaderData('root') as RootData | undefined;
  if (!data?.user) throw new Error('useSession used outside an authenticated route');
  return data as RootData & { user: UserView };
}

/** Loader guard for authenticated routes; keeps the requested path to come back after login. */
export async function requireUserLoader(request: Request): Promise<UserView> {
  const { user } = __DEMO__ && !readDemoSession() ? { user: null } : await (await getApi()).me({});
  if (!user) {
    const url = new URL(request.url);
    throw redirect(`/entrar?volver=${encodeURIComponent(url.pathname + url.search)}`);
  }
  return user;
}

export const ROLE_LABEL: Record<UserView['globalRole'], string> = {
  student: 'Alumnado',
  teacher: 'Profesorado',
  admin: 'Administración',
  support: 'Oficina técnica',
};
