import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import {
  completeOnboardingAsShooter,
  createAdditionalParticipant,
  loginParticipantUser,
  logoutParticipantUser,
  registerParticipantUser,
  uniqueTestEmail,
  uniqueTestPerson,
} from '../../support/staging-helpers';

test.describe('Staging / bruker og deltaker @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(app.kind !== 'cup', 'Kun for public cup-UI');
    skipIfAppNotReady(app);
  });

  test('registrering oppretter bruker og deltakerprofil', async ({ page }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Kari');
    const user = {
      ...person,
      email: uniqueTestEmail('bruker'),
    };

    await registerParticipantUser(page, { ...user, password });
    await expect(page.getByRole('heading', { name: /Velkommen|Vi opprettet|Vi fant/i })).toBeVisible();

    await completeOnboardingAsShooter(page);
    const fullName = `${user.firstName} ${user.lastName}`;
    await expect(page.locator('table.data-table tbody')).toContainText(fullName);
    await expect(page.locator('table.data-table tbody code')).toContainText(/JC-/);
    await expect(page.locator('.user-menu')).toBeVisible();
  });

  test('bruker oppretter flere deltakere', async ({ page }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Per');
    const user = {
      ...person,
      email: uniqueTestEmail('forelder'),
    };
    const child = uniqueTestPerson('Lise');

    await registerParticipantUser(page, { ...user, password });
    await completeOnboardingAsShooter(page);
    await createAdditionalParticipant(page, child.firstName, child.lastName);

    await expect(page.locator('table.data-table tbody')).toContainText(
      `${user.firstName} ${user.lastName}`,
    );
    await expect(page.locator('table.data-table tbody')).toContainText(
      `${child.firstName} ${child.lastName}`,
    );
  });

  test('utlogget bruker kan logge inn og beholde tilganger', async ({ page }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Ola');
    const user = {
      ...person,
      email: uniqueTestEmail('login'),
    };

    await registerParticipantUser(page, { ...user, password });
    await completeOnboardingAsShooter(page);

    await page.goto('/min-side/profil');
    await expect(page.getByRole('heading', { name: /Min profil/i })).toBeVisible();

    await logoutParticipantUser(page);
    await loginParticipantUser(page, user.email, password);

    await expect(page.getByRole('heading', { name: 'Mine deltakere' })).toBeVisible();
    await expect(page.locator('table.data-table tbody')).toContainText(
      `${user.firstName} ${user.lastName}`,
    );
  });
});
