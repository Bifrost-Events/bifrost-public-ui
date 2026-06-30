import { test, expect, visitRoute } from '../../support/fixtures';
import { expectsSeededDatabase } from '../../support/app-config';

test.describe('Database @database', () => {
  test('backend database is healthy after seed', async ({
    page,
    consoleCollector,
  }) => {
    test.skip(
      !expectsSeededDatabase(),
      'Kun for miljøer med database prepare (local-quality, staging)',
    );

    const response = await visitRoute(page, '/health');
    expect(response?.status(), 'Health skal være 200 etter seed').toBe(200);

    const body = await page.locator('body').textContent();
    expect(body ?? '').toContain('public_ui');
    expect(body ?? '').toMatch(/database["\s:]+ok/i);

    consoleCollector.assertClean();
  });
});
