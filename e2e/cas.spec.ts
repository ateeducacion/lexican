import type { Page } from '@playwright/test';
import { expect, test } from './fixtures.ts';

/**
 * The Pages demo against a FAKE CAS server on another origin (scripts/fake-cas-server.mjs): deterministic tests of
 * the real protocol flow in the browser (state, exact service URL, single validation, real CORS). The public test
 * CAS itself is checked by hand (docs/AUTHENTICATION.md), never from CI.
 */
const CAS = 'http://localhost:4318';
test.use({ allowedOrigins: [CAS] });

async function casMode(page: Page, mode: 'cors' | 'nocors', user = 'alice') {
  await page.context().addCookies([
    { name: 'fakecas_mode', value: mode, url: CAS },
    { name: 'fakecas_user', value: user, url: CAS },
  ]);
}

/** Validation requests the fake CAS received, read from the worker's network activity. */
function validations(page: Page) {
  const seen: URL[] = [];
  page.on('request', (r) => {
    if (r.url().startsWith(`${CAS}/p3/serviceValidate`)) seen.push(new URL(r.url()));
  });
  return seen;
}

test.describe('Pages: CAS de pruebas @fakecas', () => {
  test.beforeEach(({ app }) => test.skip(!app.demo, 'the Pages demo flow'));

  test('the login page names the test CAS and warns about institutional credentials', async ({
    page,
    app,
  }) => {
    await app.goto('/entrar');
    await expect(page.getByRole('button', { name: 'Entrar con CAS de pruebas' })).toBeVisible();
    await expect(
      page.getByText('No escribas nunca tu usuario ni tu contraseña educativos'),
    ).toBeVisible();
  });

  test('with CORS allowed, a validated ticket signs in under the mapped persona and leaves no ticket in the URL', async ({
    page,
    app,
  }) => {
    await casMode(page, 'cors', 'alice');
    const seen = validations(page);
    await app.goto('/entrar');
    await page.getByRole('button', { name: 'Entrar con CAS de pruebas' }).click();
    await expect(page.getByRole('button', { name: 'Desconectar' })).toBeVisible({
      timeout: 90_000,
    });
    await expect(page.getByText('Yaiza Tutoriales').first()).toBeVisible();
    expect(page.url()).not.toMatch(/ticket|state|cas=/);
    // One validation, with the exact service URL served by Pages (base path, marker and state).
    expect(seen).toHaveLength(1);
    expect(seen[0]!.searchParams.get('service')).toMatch(
      /^http:\/\/localhost:4317\/lexican\/\?cas=callback&state=[\w-]{32}$/,
    );
    // Reloading does not replay the callback.
    await page.reload();
    await expect(page.getByRole('button', { name: 'Desconectar' })).toBeVisible({
      timeout: 90_000,
    });
    expect(seen).toHaveLength(1);
  });

  test('when the CAS server does not allow CORS, the limitation is shown and nobody is signed in', async ({
    page,
    app,
  }) => {
    // The browser itself reports the blocked cross-origin read: that console error is the scenario under test.
    page.removeAllListeners('console');
    await casMode(page, 'nocors');
    await app.goto('/entrar');
    await page.getByRole('button', { name: 'Entrar con CAS de pruebas' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'CORS' })).toBeVisible({
      timeout: 90_000,
    });
    await expect(page.getByRole('button', { name: 'Desconectar' })).toHaveCount(0);
    expect(page.url()).not.toMatch(/ticket/);
  });

  test('a callback without the pending login state is refused without asking CAS', async ({
    page,
  }) => {
    const seen = validations(page);
    await page.goto('./?cas=callback&state=forged&ticket=ST-forged');
    await expect(
      page.getByRole('alert').filter({ hasText: 'No se ha podido completar el acceso con CAS' }),
    ).toBeVisible({ timeout: 90_000 });
    expect(seen).toHaveLength(0);
    expect(page.url()).not.toMatch(/ticket/);
    await expect(page.getByRole('button', { name: 'Desconectar' })).toHaveCount(0);
  });

  test('an unknown CAS account is refused (no persona) and the student persona maps from bob', async ({
    page,
    app,
  }) => {
    await casMode(page, 'cors', 'mallory');
    await app.goto('/entrar');
    await page.getByRole('button', { name: 'Entrar con CAS de pruebas' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'no está autorizado' })).toBeVisible({
      timeout: 90_000,
    });
    await casMode(page, 'cors', 'bob');
    await page.getByRole('button', { name: 'Entrar con CAS de pruebas' }).click();
    await expect(page.getByRole('button', { name: 'Desconectar' })).toBeVisible({
      timeout: 90_000,
    });
    await expect(page.getByText('Alumno Padrón Armas').first()).toBeVisible();
  });
});
