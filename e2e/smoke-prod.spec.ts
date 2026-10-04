import { expect, test } from './fixtures.ts';

// Production stack only (web + Fastify API + PostgreSQL, dev password provider).
test.skip(({ app }) => app.demo, 'production project only');

test('teacher sees the classroom from the seeded demo data', async ({ app, page }) => {
  await app.login('teacher');
  await app.goto('/aulas');
  await expect(page.getByRole('heading', { name: 'Mis aulas' })).toBeVisible();
  await expect(page.getByText('Canarismos de 2º ESO B').first()).toBeVisible();
});

test('student lands on the personal dictionary', async ({ app, page }) => {
  await app.login('student1');
  await expect(page.getByText('Mi diccionario personal').first()).toBeVisible();
});
