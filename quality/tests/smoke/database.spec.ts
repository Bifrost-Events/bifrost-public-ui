import { test, expect, visitRoute, skipIfAppNotReady } from '../../support/fixtures';
import { expectsSeededDatabase } from '../../support/app-config';

test.describe('Database @database', () => {
  test.beforeEach(({ app }) => {
    skipIfAppNotReady(app);
  });

  test('backend database is healthy after seed', async ({
    page,
    app,
    consoleCollector,
  }) => {
    test.skip(
      !expectsSeededDatabase(),
      'Kun for miljøer med database prepare (local-quality, staging)',
    );

    const response = await visitRoute(page, '/health');
    expect(response?.status(), 'Health skal være 200 etter seed').toBe(200);

    const body = await page.locator('body').textContent();
    const marker =
      app.expected.healthMarker ??
      (app.kind === 'cup' ? 'public_ui' : `${app.key}_ui`);
    expect(body ?? '').toContain(marker);
    expect(body ?? '').toMatch(/database["\s:]+ok|"status"\s*:\s*"ok"/i);

    consoleCollector.assertClean();
  });
});
