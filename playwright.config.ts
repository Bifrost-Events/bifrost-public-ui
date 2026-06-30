import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { getSelectedApps, getEnvironmentManifest } from './quality/support/app-config';

const envManifest = getEnvironmentManifest();
const apps = getSelectedApps();
const isProduction = envManifest.environment === 'production';

export default defineConfig({
  testDir: './quality/tests',
  globalSetup: path.join(__dirname, 'quality/support/global-setup.ts'),
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? (isProduction ? 1 : 2) : 0,
  workers: process.env.CI ? 2 : undefined,
  timeout: envManifest.timeouts?.test ?? 60_000,
  expect: {
    timeout: envManifest.timeouts?.expect ?? 10_000,
  },
  reporter: [
    ['list'],
    ['html', { outputFolder: 'quality/reports/html', open: 'never' }],
    ['json', { outputFile: 'quality/reports/results.json' }],
    ['junit', { outputFile: 'quality/reports/junit.xml' }],
  ],
  outputDir: 'quality/reports/test-results',
  use: {
    ...devices['Desktop Chrome'],
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    actionTimeout: envManifest.timeouts?.action ?? 15_000,
    navigationTimeout: envManifest.timeouts?.navigation ?? 30_000,
  },
  projects: apps.map((app) => ({
    name: app.key,
    use: {
      baseURL: app.baseUrl,
      extraHTTPHeaders: app.hostHeader
        ? { Host: app.hostHeader }
        : undefined,
    },
  })),
});
