import fs from 'node:fs';
import path from 'node:path';
import { test as base, expect, type TestInfo } from '@playwright/test';
import {
  getAppForProject,
  getEnvironmentManifest,
  shouldCaptureSuccessScreenshots,
} from '../support/app-config';
import { createConsoleCollector } from '../support/console-checks';
import { screenshotPath, slugifyRouteName } from '../support/screenshot-paths';

type QualityFixtures = {
  app: ReturnType<typeof getAppForProject>;
  envManifest: ReturnType<typeof getEnvironmentManifest>;
  consoleCollector: ReturnType<typeof createConsoleCollector>;
};

export const test = base.extend<QualityFixtures>({
  app: async ({}, use, testInfo) => {
    const app = getAppForProject(testInfo.project.name);
    await use(app);
  },
  envManifest: async ({}, use) => {
    await use(getEnvironmentManifest());
  },
  consoleCollector: async ({ page }, use) => {
    const collector = createConsoleCollector();
    collector.attach(page);
    await use(collector);
  },
});

export { expect };

export async function visitRoute(
  page: import('@playwright/test').Page,
  routePath: string,
): Promise<import('@playwright/test').Response | null> {
  const response = await page.goto(routePath, { waitUntil: 'domcontentloaded' });
  await page.locator('body').waitFor({ state: 'visible' });
  return response;
}

export async function assertPageBasics(
  page: import('@playwright/test').Page,
  app: QualityFixtures['app'],
  _routeName: string,
): Promise<void> {
  await expect(page.locator('body')).toBeVisible();

  const titleContains = app.expected.titleContains;
  if (titleContains) {
    await expect(page).toHaveTitle(new RegExp(titleContains, 'i'));
  }

  for (const text of app.expected.visibleText ?? []) {
    await expect(page.locator('body')).toContainText(text, { timeout: 10_000 });
  }

  const cupKey = app.expected.cupKey ?? app.cupId;
  if (cupKey) {
    await expect(page.locator('body')).toHaveAttribute('data-cup-key', cupKey);
    await expect(page.locator('meta[name="bifrost:cup"]')).toHaveAttribute(
      'content',
      cupKey,
    );
  }
}

export function shouldTakeSuccessScreenshot(): boolean {
  return shouldCaptureSuccessScreenshots();
}

/**
 * Lagrer fullside-skjermbilde til quality/screenshots/ og vedlegger i HTML-rapport.
 */
export async function captureScreenshot(
  page: import('@playwright/test').Page,
  app: QualityFixtures['app'],
  envName: string,
  routeName: string,
  testInfo?: TestInfo,
  variant: 'capture' | 'failure' = 'capture',
): Promise<string | undefined> {
  const buffer = await page.screenshot({ fullPage: true });
  const target = screenshotPath(envName, app.key, routeName, variant);
  fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.writeFileSync(target, buffer);

  if (testInfo) {
    const attachName = `${app.key}-${slugifyRouteName(routeName)}.png`;
    await testInfo.attach(attachName, {
      body: buffer,
      contentType: 'image/png',
    });
  }

  return path.relative(process.cwd(), target);
}

/** Ta skjermbilde når miljø/manifest tillater det (local-quality, staging, QUALITY_SCREENSHOTS). */
export async function maybeCaptureScreenshot(
  page: import('@playwright/test').Page,
  app: QualityFixtures['app'],
  envName: string,
  routeName: string,
  testInfo?: TestInfo,
): Promise<string | undefined> {
  if (!shouldTakeSuccessScreenshot()) {
    return undefined;
  }

  return captureScreenshot(page, app, envName, routeName, testInfo, 'capture');
}
