import { test, visitRoute, skipIfAppNotReady } from '../../support/fixtures';

test.describe('Console checks @console', () => {
  test.beforeEach(({ app }) => {
    skipIfAppNotReady(app);
  });

  test('no unexpected console errors on key pages', async ({
    page,
    app,
    consoleCollector,
  }) => {
    const paths = app.routes
      .slice(0, 4)
      .map((route) => route.path);

    for (const routePath of paths) {
      await test.step(routePath, async () => {
        await visitRoute(page, routePath);
      });
    }

    consoleCollector.assertClean();
  });
});
