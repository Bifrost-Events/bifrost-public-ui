import {
  test,
  expect,
  visitRoute,
  assertPageBasics,
  maybeCaptureScreenshot,
  skipIfAppNotReady,
} from '../../support/fixtures';
import { expectsSeededDatabase } from '../../support/app-config';

test.describe('Smoke – portal @smoke', () => {
  test.beforeEach(({ app }) => {
    test.skip(app.kind !== 'portal', 'Kun for admin/arrangør-portaler');
    skipIfAppNotReady(app);
  });

  test('health endpoint responds', async ({
    page,
    app,
    envManifest,
    consoleCollector,
  }, testInfo) => {
    const response = await visitRoute(page, '/health');
    expect(response, 'Health endpoint should respond').not.toBeNull();

    const status = response?.status() ?? 0;
    if (expectsSeededDatabase()) {
      expect(status, 'Health skal være 200 når backend er tilgjengelig').toBe(200);
    } else {
      expect([200, 503]).toContain(status);
    }

    const marker = app.expected.healthMarker ?? app.key;
    const body = await page.locator('body').textContent();
    expect(body ?? '').toContain(marker);

    await maybeCaptureScreenshot(page, app, envManifest.environment, 'health', testInfo);
    consoleCollector.assertClean();
  });

  test('public routes load without server error', async ({
    page,
    app,
    envManifest,
    consoleCollector,
  }, testInfo) => {
    expect(app.routes.length, `No routes in manifest for ${app.key}`).toBeGreaterThan(0);

    for (const route of app.routes) {
      await test.step(`${route.name} (${route.path})`, async () => {
        const response = await visitRoute(page, route.path);

        expect(response, `No response for ${route.path}`).not.toBeNull();
        expect(
          response?.status() ?? 0,
          `HTTP status for ${route.path}`,
        ).toBeLessThan(500);

        await assertPageBasics(page, app, route.name);

        await maybeCaptureScreenshot(
          page,
          app,
          envManifest.environment,
          route.name,
          testInfo,
        );
      });
    }

    consoleCollector.assertClean();
  });
});
