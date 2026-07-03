import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import {
  completeOnboardingAsShooter,
  createAdditionalParticipant,
  registerForQualityTestCompetition,
  registerParticipantUser,
  QUALITY_TEST_COMPETITION_NAME,
  uniqueTestEmail,
  uniqueTestPerson,
} from '../../support/staging-helpers';

test.describe('Staging / stevne-påmelding @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(app.kind !== 'cup', 'Kun for public cup-UI');
    skipIfAppNotReady(app);
  });

  test('meld på stevne og se påmelding på min side', async ({ page }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Ola');
    const user = {
      ...person,
      email: uniqueTestEmail('signup'),
    };

    await registerParticipantUser(page, { ...user, password });
    await completeOnboardingAsShooter(page);

    const selfName = `${user.firstName} ${user.lastName}`;
    await registerForQualityTestCompetition(page, selfName);

    await page.goto('/min-side/pameldinger');
    await expect(page.getByRole('heading', { name: 'Mine påmeldinger' })).toBeVisible();
    await expect(page.locator('table.data-table tbody')).toContainText(
      QUALITY_TEST_COMPETITION_NAME,
    );
    await expect(page.locator('table.data-table tbody')).toContainText(selfName);
  });

  test('meld på med valgt deltaker (barn)', async ({ page }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Anne');
    const user = {
      ...person,
      email: uniqueTestEmail('parent'),
    };
    const child = uniqueTestPerson('Lise');

    await registerParticipantUser(page, { ...user, password });
    await completeOnboardingAsShooter(page);
    await createAdditionalParticipant(page, child.firstName, child.lastName);

    const childName = `${child.firstName} ${child.lastName}`;
    await registerForQualityTestCompetition(page, childName);

    await page.goto('/min-side/pameldinger');
    await expect(page.locator('table.data-table tbody')).toContainText(childName);
    await expect(page.locator('table.data-table tbody')).toContainText(
      QUALITY_TEST_COMPETITION_NAME,
    );
  });
});
