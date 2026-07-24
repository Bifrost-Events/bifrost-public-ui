import {
  test,
  expect,
  visitRoute,
  assertPageBasics,
  skipIfAppNotReady,
} from '../../support/fixtures';
import { expectsSeededDatabase } from '../../support/app-config';
import type { ResolvedApp } from '../../support/app-config';

/**
 * Verifiserer at plattformens tre UI-moduler er oppe:
 * - public (cup-UI)
 * - admin
 * - arrangør
 *
 * Kjører per Playwright-project (app i manifest). Marker i /health
 * skiller modulene: public_ui | admin_ui | arrangor_ui.
 */
function moduleLabel(app: ResolvedApp): 'public' | 'admin' | 'arrangør' {
  if (app.kind === 'cup') {
    return 'public';
  }
  if (app.key === 'admin' || app.key.startsWith('admin')) {
    return 'admin';
  }
  return 'arrangør';
}

function healthMarkerFor(app: ResolvedApp): string {
  if (app.expected.healthMarker) {
    return app.expected.healthMarker;
  }
  if (app.kind === 'cup') {
    return 'public_ui';
  }
  if (moduleLabel(app) === 'admin') {
    return 'admin_ui';
  }
  return 'arrangor_ui';
}

/** Godkjente markører i /health (legacy admin-ui eller admin-core). */
function healthMarkersFor(app: ResolvedApp): string[] {
  const primary = healthMarkerFor(app);
  if (moduleLabel(app) === 'admin') {
    return [primary, 'Bifrost Admin Core', '"status":"ok"'];
  }
  return [primary];
}

function entryPath(app: ResolvedApp): string {
  return app.routes[0]?.path ?? (app.kind === 'cup' ? '/' : '/login');
}

test.describe('Modules up – public / admin / arrangør @smoke @modules-up', () => {
  test.beforeEach(({ app }) => {
    skipIfAppNotReady(app);
  });

  test('module health endpoint is up', async ({ page, app }) => {
    const module = moduleLabel(app);
    const markers = healthMarkersFor(app);

    const response = await visitRoute(page, '/health');
    expect(response, `${module} (${app.key}): /health should respond`).not.toBeNull();

    const status = response?.status() ?? 0;
    if (expectsSeededDatabase()) {
      expect(status, `${module} (${app.key}): /health skal være 200`).toBe(200);
    } else {
      expect(
        [200, 503],
        `${module} (${app.key}): /health skal svare (200 eller 503)`,
      ).toContain(status);
    }

    const body = (await page.locator('body').textContent()) ?? '';
    const matched = markers.some((marker) => body.includes(marker));
    expect(
      matched,
      `${module} (${app.key}): /health skal inneholde én av [${markers.join(', ')}], fikk: ${body.slice(0, 200)}`,
    ).toBe(true);
  });

  test('module entry page loads', async ({ page, app }) => {
    const module = moduleLabel(app);
    const path = entryPath(app);
    const routeName = app.routes[0]?.name ?? 'Entry';

    const response = await visitRoute(page, path);
    expect(response, `${module} (${app.key}): ${path} should respond`).not.toBeNull();
    expect(
      response?.status() ?? 0,
      `${module} (${app.key}): HTTP status for ${path}`,
    ).toBeLessThan(500);

    await assertPageBasics(page, app, routeName);
  });
});
