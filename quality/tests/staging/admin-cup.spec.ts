import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import { loginAdminUser } from '../../support/staging-helpers';

test.describe('Staging / CupAdmin @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(app.key !== 'admin', 'Kun for admin-portalen');
    skipIfAppNotReady(app);
  });

  test('SystemAdmin oppretter ny cup i admin-portalen', async ({ page }) => {
    const slug = `quality-${Date.now().toString(36)}`;
    const cupName = `Quality Cup ${slug}`;

    await loginAdminUser(page);
    await page.goto('/platform/cuper/new');

    await expect(page.getByRole('heading', { name: /Opprett/i })).toBeVisible();
    await page.locator('#slug').fill(slug);
    await page.locator('#name').fill(cupName);
    await page.getByRole('button', { name: 'Lagre' }).click();

    await page.waitForURL(/\/platform\/cuper/, { timeout: 30_000 });
    await expect(page.locator('table')).toContainText(cupName);
    await expect(page.locator('table')).toContainText(slug);
  });
});
