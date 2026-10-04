import AxeBuilder from '@axe-core/playwright';
import { expect, test as base, type Page } from '@playwright/test';

export const ACCOUNTS = {
  teacher: { email: 'profesor@ejemplo.com', password: 'profesor' },
  student1: { email: 'alumno1@ejemplo.com', password: 'alumno1' },
  student2: { email: 'alumno2@ejemplo.com', password: 'alumno2' },
  admin: { email: 'admin@ejemplo.com', password: 'admin' },
} as const;

export interface App {
  /** Navigate to an app path: hash routing in the demo, real paths in production. */
  goto(path: string): Promise<void>;
  login(who: keyof typeof ACCOUNTS): Promise<void>;
  logout(): Promise<void>;
  demo: boolean;
}

/**
 * Every test fails on an uncaught page error, a console error or a request leaving the app's origin
 * (no CDNs, fonts, analytics or institutional services — §22, §64).
 */
export const test = base.extend<{ guard: void; app: App; allowedOrigins: string[] }>({
  /** Extra origins a test may contact on purpose (a faked CAS server). */
  allowedOrigins: [[], { option: true }],
  guard: [
    async ({ page, baseURL, allowedOrigins }, use) => {
      const problems: string[] = [];
      const origin = new URL(baseURL ?? 'http://localhost').origin;
      page.on('pageerror', (e) => problems.push(`pageerror: ${e.message}`));
      page.on('console', (m) => {
        if (m.type() === 'error') problems.push(`console: ${m.text()}`);
      });
      page.on('request', (r) => {
        const u = r.url();
        if (
          !/^(data|blob):/.test(u) &&
          new URL(u).origin !== origin &&
          !allowedOrigins.includes(new URL(u).origin)
        )
          problems.push(`external request: ${u}`);
      });
      await use();
      expect(problems, 'unexpected browser problems').toEqual([]);
    },
    { auto: true },
  ],
  app: async ({ page }, use, testInfo) => {
    const demo = testInfo.project.name.startsWith('demo');
    const app: App = {
      demo,
      goto: async (path) => {
        await page.goto(demo ? `./#${path}` : `.${path}`);
      },
      login: async (who) => {
        const a = ACCOUNTS[who];
        await app.goto('/entrar');
        await page.getByLabel('Correo electrónico').fill(a.email);
        await page.getByLabel('Contraseña').fill(a.password);
        await page.getByRole('button', { name: 'Entrar', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Desconectar' })).toBeVisible({
          timeout: 90_000,
        });
      },
      logout: async () => {
        await page.getByRole('button', { name: 'Desconectar' }).click();
        await expect(page.getByRole('heading', { name: 'Acceso' })).toBeVisible();
      },
    };
    await use(app);
  },
});

export { expect };

/** WCAG 2.0/2.1/2.2 A and AA automatic checks. Zero violations is necessary, not sufficient (docs/ACCESSIBILITY.md). */
export async function expectAccessible(page: Page) {
  const r = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  expect(
    r.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`),
  ).toEqual([]);
}
