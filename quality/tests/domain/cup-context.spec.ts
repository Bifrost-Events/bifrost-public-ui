import {
  test,
  expect,
  visitRoute,
  maybeCaptureScreenshot,
} from '../../support/fixtures';

test.describe('Domain / cup context @domain', () => {
  test('correct cup loads for domain', async ({
    page,
    app,
    envManifest,
    consoleCollector,
  }, testInfo) => {
    const response = await visitRoute(page, '/');
    expect(response?.status() ?? 0).toBeLessThan(500);

    const expectedCupKey = app.expected.cupKey ?? app.cupId;
    await expect(page.locator('body')).toHaveAttribute(
      'data-cup-key',
      expectedCupKey,
    );
    await expect(page.locator('meta[name="bifrost:cup"]')).toHaveAttribute(
      'content',
      expectedCupKey,
    );

    if (app.expected.titleContains) {
      await expect(page).toHaveTitle(
        new RegExp(app.expected.titleContains, 'i'),
      );
    }

    const brandTitle = page.locator('.brand-title');
    if (await brandTitle.count()) {
      await expect(brandTitle.first()).toContainText(
        new RegExp(app.expected.titleContains ?? app.name, 'i'),
      );
    }

    for (const text of app.expected.visibleText ?? []) {
      await expect(page.locator('body')).toContainText(text);
    }

    await maybeCaptureScreenshot(
      page,
      app,
      envManifest.environment,
      'domain-forside',
      testInfo,
    );

    consoleCollector.assertClean();
  });

  test('cup key is not default on managed domains', async ({
    page,
    app,
    envManifest,
    consoleCollector,
  }, testInfo) => {
    await visitRoute(page, '/');
    const cupKey = await page.locator('body').getAttribute('data-cup-key');
    expect(cupKey).toBeTruthy();
    expect(cupKey).not.toBe('default');
    expect(cupKey).toBe(app.expected.cupKey ?? app.cupId);

    await maybeCaptureScreenshot(
      page,
      app,
      envManifest.environment,
      'domain-cup-key',
      testInfo,
    );

    consoleCollector.assertClean();
  });
});
