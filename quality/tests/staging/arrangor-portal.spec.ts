import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import { loadAppByKey } from '../../support/app-config';
import {
  CUP_SEASON_YEAR,
  JAKTFELTCUP_ROUND_COUNT,
  approveOrganizerApplication,
  createArrangorOrganization,
  createArrangorSeason,
  cupSeasonName,
  loginArrangorUser,
  logoutArrangorUser,
  openSeasonForOrganizerApplications,
  portalCupForArrangorApp,
  publicAppKeyForArrangor,
  registerParticipantUser,
  setupJaktfeltcupRounds,
  setupKarusellDirectEvents,
  submitOrganizerApplication,
  uniqueTestEmail,
  uniqueTestPerson,
} from '../../support/staging-helpers';

/**
 * Staging mot bifrost-arrangor-ui (Portal V3).
 * Testplan: quality/docs/test-plans/arrangor-portal.md (AP-01 … AP-03)
 *
 * Forutsetter admin-core AC-03 (apper, org, eier, event spaces) — uten sesonger.
 */
test.describe('Staging / arrangør-portal V3 @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(
      app.key !== 'arrangor-jaktfeltcup' && app.key !== 'arrangor-namdal',
      'Kun relevant for arrangør-portaler',
    );
    skipIfAppNotReady(app);
  });

  test('cup-eier kan opprette sesong', async ({ page, app }) => {
    test.setTimeout(60_000);

    const cup = portalCupForArrangorApp(app.key);
    expect(cup, `Ingen PORTAL_CUPS-mapping for ${app.key}`).toBeTruthy();

    await loginArrangorUser(page, cup!.owner.email, cup!.owner.password);

    const seasonName = cupSeasonName(cup!);
    const seasonId = await createArrangorSeason(page, {
      name: seasonName,
      shortName: CUP_SEASON_YEAR,
      seasonLabel: CUP_SEASON_YEAR,
      status: 'active',
      visibility: 'public',
    });

    await expect(page).toHaveURL(new RegExp(`/sesonger/${seasonId}/struktur`));
    await expect(page.locator('.flash.success')).toContainText(/Serie opprettet/i);
    await expect(page.getByRole('heading', { name: 'Sesongstruktur' })).toBeVisible();

    await page.goto('/cup');
    const seasonCard = page.locator('.card', {
      has: page.locator(`a[href="/sesonger/${seasonId}/struktur"]`),
    });
    await expect(seasonCard).toContainText(seasonName);
    await expect(
      seasonCard.getByRole('link', { name: /^Sett struktur$/i }),
    ).toBeVisible();
  });

  test('cup-eier setter opp sesongstruktur', async ({ page, app }) => {
    test.setTimeout(120_000);

    const cup = portalCupForArrangorApp(app.key);
    expect(cup, `Ingen PORTAL_CUPS-mapping for ${app.key}`).toBeTruthy();

    await loginArrangorUser(page, cup!.owner.email, cup!.owner.password);

    const seasonName = cupSeasonName(cup!);
    const seasonId = await createArrangorSeason(page, {
      name: seasonName,
      shortName: CUP_SEASON_YEAR,
      seasonLabel: CUP_SEASON_YEAR,
      status: 'active',
      visibility: 'public',
    });

    if (app.key === 'arrangor-jaktfeltcup') {
      await setupJaktfeltcupRounds(page, seasonId, JAKTFELTCUP_ROUND_COUNT);

      await page.goto('/cup');
      const seasonCard = page.locator('.card', {
        has: page.locator(`a[href="/sesonger/${seasonId}/struktur"]`),
      });
      await expect(seasonCard).toContainText(seasonName);
      await expect(seasonCard).toContainText(/gruppert i runder/i);
      for (let i = 1; i <= JAKTFELTCUP_ROUND_COUNT; i += 1) {
        await expect(seasonCard).toContainText(`Runde ${i}`);
      }
      await expect(
        seasonCard.getByRole('link', { name: /^Ny runde$/i }),
      ).toBeVisible();
      return;
    }

    await setupKarusellDirectEvents(page, seasonId);

    await page.goto('/cup');
    const seasonCard = page.locator('.card', {
      has: page.locator(`a[href="/sesonger/${seasonId}/struktur"]`),
    });
    await expect(seasonCard).toContainText(seasonName);
    await expect(seasonCard).toContainText(/Stevner direkte i sesong/i);
    await expect(
      seasonCard.getByRole('link', { name: /^Nytt stevne$/i }),
    ).toBeVisible();
    await expect(
      seasonCard.getByRole('link', { name: /^Ny runde$/i }),
    ).toHaveCount(0);
  });

  test('bruker kan registrere seg, søke og få søknad godkjent', async ({
    page,
    app,
  }) => {
    test.setTimeout(180_000);

    const cup = portalCupForArrangorApp(app.key);
    expect(cup, `Ingen PORTAL_CUPS-mapping for ${app.key}`).toBeTruthy();

    const publicApp = loadAppByKey(publicAppKeyForArrangor(app.key));
    const password = 'QualityArr123!';
    const person = uniqueTestPerson('Sok');
    const email = uniqueTestEmail('arr-soknad');
    const orgName = `Quality Arrangør ${Date.now().toString(36)}`;
    const eventName = `Quality stevne ${Date.now().toString(36)}`;
    const seasonName = cupSeasonName(cup!);

    await loginArrangorUser(page, cup!.owner.email, cup!.owner.password);
    const seasonId = await createArrangorSeason(page, {
      name: seasonName,
      shortName: CUP_SEASON_YEAR,
      seasonLabel: CUP_SEASON_YEAR,
      status: 'active',
      visibility: 'public',
    });
    if (app.key === 'arrangor-jaktfeltcup') {
      await setupJaktfeltcupRounds(page, seasonId, JAKTFELTCUP_ROUND_COUNT);
    } else {
      await setupKarusellDirectEvents(page, seasonId);
    }
    await openSeasonForOrganizerApplications(page, seasonId);
    await logoutArrangorUser(page);

    await registerParticipantUser(
      page,
      {
        firstName: person.firstName,
        lastName: person.lastName,
        phone: person.phone,
        email,
        password,
      },
      { baseUrl: publicApp.baseUrl },
    );

    await loginArrangorUser(page, email, password);
    await createArrangorOrganization(page, orgName);
    const applicationId = await submitOrganizerApplication(page, {
      seasonId,
      eventName,
      message: 'Quality staging søknad',
    });
    await logoutArrangorUser(page);

    await loginArrangorUser(page, cup!.owner.email, cup!.owner.password);
    await approveOrganizerApplication(page, seasonId, applicationId);
  });
});
