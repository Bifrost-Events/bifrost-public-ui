import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import {
  approveLatestOrganizerApplication,
  completeOrganizerOnboarding,
  loginArrangorUser,
  registerParticipantViaApi,
  uniqueTestEmail,
  uniqueTestPerson,
} from '../../support/staging-helpers';

test.describe('Staging / arrangør @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(
      app.key !== 'arrangor-jaktfeltcup' && app.key !== 'arrangor-namdal',
      'Kun relevant for arrangør-portaler',
    );
    skipIfAppNotReady(app);
  });

  test('arrangør kan registrere organisasjon, få godkjenning og opprette stevne', async ({
    page,
    request,
  }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Arr');
    const user = {
      ...person,
      email: uniqueTestEmail('arrangor'),
    };
    const orgName = `Quality Arrangør ${Date.now().toString(36)}`;

    await registerParticipantViaApi(request, { ...user, password });
    await loginArrangorUser(page, user.email, password);
    await completeOrganizerOnboarding(page, orgName);

    const pendingBadge = page.getByText('Venter på godkjenning');
    if (await pendingBadge.isVisible().catch(() => false)) {
      await approveLatestOrganizerApplication(request);
      await page.reload();
    }

    await expect(page.locator('span.badge-ok', { hasText: 'Godkjent' })).toBeVisible({
      timeout: 15_000,
    });

    await page.goto('/stevner/ny');
    const competitionName = `Quality stevne ${Date.now().toString(36)}`;
    const roundSelect = page.locator('#round_id');
    await expect(roundSelect).toBeVisible({ timeout: 15_000 });
    await roundSelect.selectOption({ index: 1 });
    const eventDate = await roundSelect
      .locator('option:checked')
      .getAttribute('data-start-date');
    expect(eventDate, 'Valgt runde må ha startdato i seed').toBeTruthy();
    await page.locator('#name').fill(competitionName);
    await page.locator('#event_date').fill(eventDate!);
    await page.getByRole('button', { name: 'Opprett' }).click();
    await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === '/stevner', {
      timeout: 30_000,
    });
    await expect(page.locator('body')).toContainText(competitionName);
  });

  test('arrangør kan invitere medlem', async ({ page, request }) => {
    const password = 'QualityTest1!';
    const person = uniqueTestPerson('Inv');
    const user = {
      ...person,
      email: uniqueTestEmail('arr-inviter'),
    };
    const orgName = `Quality Invite Org ${Date.now().toString(36)}`;
    const inviteEmail = uniqueTestEmail('arr-invitee');

    await registerParticipantViaApi(request, { ...user, password });
    await loginArrangorUser(page, user.email, password);
    await completeOrganizerOnboarding(page, orgName);

    if (await page.getByText('Venter på godkjenning').isVisible().catch(() => false)) {
      await approveLatestOrganizerApplication(request);
      await page.reload();
    }

    await page.goto('/organisasjon/medlemmer');
    await page.locator('#email').fill(inviteEmail);
    await page.getByRole('button', { name: 'Send invitasjon' }).click();
    await page.waitForURL(/\/organisasjon\/medlemmer/, { timeout: 15_000 });
    await expect(page.locator('body')).toContainText('Invitasjon sendt');
  });
});
