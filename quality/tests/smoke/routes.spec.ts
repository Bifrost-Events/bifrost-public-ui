import {
  test,
  expect,
  visitRoute,
  assertPageBasics,
  maybeCaptureScreenshot,
} from '../../support/fixtures';
import { expectsSeededDatabase } from '../../support/app-config';

test.describe('Smoke – routes @smoke', () => {
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
      expect(status, 'Health skal være 200 når database er seedet').toBe(200);
    } else {
      expect([200, 503]).toContain(status);
    }

    const body = await page.locator('body').textContent();
    expect(body ?? '').toContain('public_ui');

    await maybeCaptureScreenshot(page, app, envManifest.environment, 'health', testInfo);

    consoleCollector.assertClean();
  });

  test('all manifest routes load', async ({
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

        const saved = await maybeCaptureScreenshot(
          page,
          app,
          envManifest.environment,
          route.name,
          testInfo,
        );
        if (saved) {
          testInfo.annotations.push({
            type: 'screenshot',
            description: saved,
          });
        }
      });
    }

    consoleCollector.assertClean();
  });
});
