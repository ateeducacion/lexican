import { createBrowserRouter, createHashRouter, redirect, type RouteObject } from 'react-router';
import { Layout } from './components/Layout.tsx';
import { RouteError } from './pages/RouteError.tsx';
import { requireUserLoader, rootLoader } from './session.ts';

/** Every page module exports `Component` and optionally `loader`; pages load on demand. */
const page = (load: () => Promise<Record<string, unknown>>) => async () => {
  const m = await load();
  return {
    Component: m.Component as React.ComponentType,
    loader: m.loader as RouteObject['loader'],
  };
};

const routes: RouteObject[] = [
  {
    id: 'root',
    path: '/',
    loader: rootLoader,
    // The session user lives in the root loader: refresh it whenever we enter or leave the login page.
    shouldRevalidate: ({ currentUrl, nextUrl, defaultShouldRevalidate }) =>
      defaultShouldRevalidate ||
      currentUrl.pathname === '/entrar' ||
      nextUrl.pathname === '/entrar',
    Component: Layout,
    errorElement: <RouteError />,
    HydrateFallback: () => null,
    children: [
      { path: 'entrar', lazy: page(() => import('./pages/Login.tsx')) },
      {
        loader: ({ request }) => requireUserLoader(request).then(() => null),
        errorElement: <RouteError />,
        children: [
          {
            index: true,
            loader: async ({ request }) => {
              const user = await requireUserLoader(request);
              return redirect(user.globalRole === 'student' ? '/mi-diccionario' : '/aulas');
            },
          },
          {
            // List and reading pane share one workspace: switching entries keeps the list mounted.
            path: 'mi-diccionario',
            lazy: page(() => import('./pages/personal/PersonalWorkspace.tsx')),
            children: [{ index: true }, { path: 'entradas/:entryId' }],
            // The loader picks the entry for the reading pane, so it reruns when the child :entryId changes.
            shouldRevalidate: ({ currentUrl, nextUrl, defaultShouldRevalidate }) =>
              defaultShouldRevalidate || currentUrl.pathname !== nextUrl.pathname,
          },
          {
            path: 'mi-diccionario/nueva',
            lazy: page(() => import('./pages/personal/EntryEditorPage.tsx')),
          },
          {
            path: 'mi-diccionario/ajustes',
            lazy: page(() => import('./pages/personal/DictionarySettings.tsx')),
          },
          {
            path: 'mi-diccionario/enviar',
            lazy: page(() => import('./pages/personal/SendPage.tsx')),
          },
          {
            path: 'mi-diccionario/entradas/:entryId/editar',
            lazy: page(() => import('./pages/personal/EntryEditorPage.tsx')),
          },
          { path: 'comentarios', lazy: page(() => import('./pages/CommentsPage.tsx')) },
          { path: 'aulas', lazy: page(() => import('./pages/classrooms/ClassroomsPage.tsx')) },
          {
            path: 'aulas/nueva',
            lazy: page(() => import('./pages/classrooms/ClassroomFormPage.tsx')),
          },
          {
            path: 'aulas/:classroomId',
            lazy: page(() => import('./pages/classrooms/ClassroomPage.tsx')),
          },
          {
            path: 'aulas/:classroomId/editar',
            lazy: page(() => import('./pages/classrooms/ClassroomFormPage.tsx')),
          },
          {
            path: 'aulas/:classroomId/participantes',
            lazy: page(() => import('./pages/classrooms/MembersPage.tsx')),
          },
          {
            path: 'aulas/:classroomId/envios',
            lazy: page(() => import('./pages/classrooms/ReviewPage.tsx')),
          },
          {
            path: 'aulas/:classroomId/envios/:submissionId',
            lazy: page(() => import('./pages/classrooms/SubmissionPage.tsx')),
          },
          {
            path: 'aulas/:classroomId/entradas/:entryId',
            lazy: page(() => import('./pages/classrooms/ClassroomEntryPage.tsx')),
          },
          {
            path: 'diccionarios/:dictionaryId/imprimir',
            lazy: page(() => import('./pages/PrintPage.tsx')),
          },
          { path: 'admin', lazy: page(() => import('./pages/admin/AdminHome.tsx')) },
          { path: 'admin/usuarios', lazy: page(() => import('./pages/admin/UsersPage.tsx')) },
          {
            path: 'admin/aulas',
            lazy: page(() => import('./pages/admin/ClassroomsAdminPage.tsx')),
          },
          { path: 'admin/listas', lazy: page(() => import('./pages/admin/VocabulariesPage.tsx')) },
          { path: 'admin/auditoria', lazy: page(() => import('./pages/admin/AuditPage.tsx')) },
          { path: '*', lazy: page(() => import('./pages/NotFound.tsx')) },
        ],
      },
    ],
  },
];

// Hash routing on GitHub Pages (no server rewrites, no 404 hack); real paths in production (§60).
export const router = (__DEMO__ ? createHashRouter : createBrowserRouter)(routes);
