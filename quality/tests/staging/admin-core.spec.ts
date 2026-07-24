import { test, expect, skipIfAppNotReady } from '../../support/fixtures';
import {
  loginAdminUser,
  bootstrapPortalCups,
  readAdminDashboardStats,
  PORTAL_CUPS,
} from '../../support/staging-helpers';

/**
 * Staging mot bifrost-admin-core (V3).
 * Testplan: quality/docs/test-plans/admin-core.md (AC-01 … AC-05)
 */
test.describe('Staging / admin-core @staging', () => {
  test.beforeEach(({ app }) => {
    test.skip(app.key !== 'admin', 'Kun for admin-portalen (admin-core)');
    skipIfAppNotReady(app);
  });

  test('login leads to dashboard', async ({ page }) => {
    await loginAdminUser(page);

    await expect(page).toHaveURL(/\/$/);
    await expect(page.locator('body')).toContainText('Bifrost Admin');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    // Seed-baseline (stabil på tvers av parallelle write-tester)
    const stats = await readAdminDashboardStats(page);
    expect(stats['Roller'], 'Dashboard skal vise seedede roller').toBeGreaterThanOrEqual(7);
    expect(stats['Personer'], 'Dashboard skal vise seedet admin-person').toBeGreaterThanOrEqual(1);
    expect(stats['Brukere'], 'Dashboard skal vise seedet admin-bruker').toBeGreaterThanOrEqual(1);
  });

  test('core navigation pages load', async ({ page }) => {
    await loginAdminUser(page);

    const pages: Array<{ path: string; heading: string }> = [
      { path: '/core/organizations', heading: 'Organisasjoner' },
      { path: '/core/persons', heading: 'Personer' },
      { path: '/core/users', heading: 'Brukere' },
      { path: '/core/memberships', heading: 'Medlemskap' },
      { path: '/core/applications', heading: 'Applikasjoner' },
      { path: '/core/domains', heading: 'Domener' },
      { path: '/core/roles', heading: 'Roller' },
    ];

    for (const item of pages) {
      await test.step(item.path, async () => {
        const response = await page.goto(item.path, {
          waitUntil: 'domcontentloaded',
        });
        expect(response, `No response for ${item.path}`).not.toBeNull();
        expect(response!.status(), `HTTP for ${item.path}`).toBeLessThan(500);
        await expect(
          page.getByRole('heading', { name: item.heading }),
        ).toBeVisible();
      });
    }

    await expect(page.locator('body')).toContainText(/org_owner|Organisasjonseier/);
  });

  test('admin can bootstrap cups with orgs and owners', async ({ page }) => {
    test.setTimeout(180_000);

    await loginAdminUser(page);
    await bootstrapPortalCups(page);

    await page.goto('/core/applications');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.applicationKey);
    }

    await page.goto('/core/domains');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.hostname);
      await expect(page.locator('body')).toContainText(cup.arrangorHostname);
    }

    await page.goto('/core/organizations');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.organizationName);
    }

    await page.goto('/core/users');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.owner.email);
      await expect(page.locator('body')).toContainText(cup.admin.email);
    }

    await page.goto('/core/memberships');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.organizationName);
    }

    await page.goto('/events/spaces');
    for (const cup of PORTAL_CUPS) {
      await expect(page.locator('body')).toContainText(cup.spaceName);
    }

    const cupCount = PORTAL_CUPS.length;
    const cupUsers = cupCount * 2; // eier + admin per cup
    const domainCount = cupCount * 2; // public + arrangør per cup
    // Medlemskap: 2 cup-brukere + plattform-admin per cup
    const membershipCount = cupUsers + cupCount;
    const stats = await readAdminDashboardStats(page);
    expect(stats['Applikasjoner']).toBe(cupCount);
    expect(stats['Domener']).toBe(domainCount);
    // Kan være > cupCount hvis andre orgs opprettes underveis (f.eks. søker-org senere i suiten).
    expect(stats['Organisasjoner']).toBeGreaterThanOrEqual(cupCount);
    expect(stats['Medlemskap']).toBeGreaterThanOrEqual(membershipCount);
    expect(stats['Brukere']).toBeGreaterThanOrEqual(1 + cupUsers);
    expect(stats['Personer']).toBeGreaterThanOrEqual(1 + cupUsers);
  });
});
