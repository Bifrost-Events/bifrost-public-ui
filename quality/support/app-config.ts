import {
  AppManifest,
  EnvironmentManifest,
  loadAllAppManifests,
  loadAppManifest,
  loadEnvironmentManifest,
  parseHostFromUrl,
} from './manifest-loader';

export interface ResolvedApp {
  key: string;
  name: string;
  cupId: string;
  baseUrl: string;
  hostHeader?: string;
  expected: AppManifest['expected'];
  routes: AppManifest['routes'];
  manifestPath: string;
}

export interface ResolvedQualityConfig {
  environment: EnvironmentManifest;
  apps: ResolvedApp[];
}

function localHostFromManifest(manifest: AppManifest): string | undefined {
  const localUrl =
    manifest.hosts['local-quality'] ??
    manifest.hosts['local-dev'] ??
    manifest.hosts.local;
  return localUrl ? parseHostFromUrl(localUrl) : undefined;
}

function resolveHostHeader(
  baseUrl: string,
  manifest: AppManifest,
): string | undefined {
  const urlHost = parseHostFromUrl(baseUrl);
  if (!urlHost || urlHost === 'localhost' || urlHost === '127.0.0.1') {
    if (manifest.hostHeader) {
      return manifest.hostHeader;
    }
    return localHostFromManifest(manifest);
  }
  return undefined;
}

function resolveApp(
  manifest: AppManifest,
  envName: string,
  manifestPath: string,
): ResolvedApp | null {
  const baseUrl = manifest.hosts[envName];
  if (!baseUrl) {
    return null;
  }

  const normalizedBaseUrl = baseUrl.replace(/\/$/, '');

  return {
    key: manifest.key,
    name: manifest.name,
    cupId: manifest.cupId,
    baseUrl: normalizedBaseUrl,
    hostHeader: resolveHostHeader(normalizedBaseUrl, manifest),
    expected: manifest.expected ?? {},
    routes: manifest.routes ?? [],
    manifestPath,
  };
}

export function getEnvironmentManifest(): EnvironmentManifest {
  return loadEnvironmentManifest();
}

export function getSelectedApps(): ResolvedApp[] {
  const envName = (process.env.QUALITY_ENV ?? 'local-quality').toLowerCase();
  const appFilter = (process.env.QUALITY_APP ?? 'all').toLowerCase();
  const envManifest = loadEnvironmentManifest(envName);
  const manifestFiles = loadAllAppManifests();

  const resolved = manifestFiles
    .map((manifest) => {
      const filePath = manifest.key;
      return resolveApp(manifest, envName, filePath);
    })
    .filter((app): app is ResolvedApp => app !== null);

  if (appFilter === 'all') {
    const envApps = envManifest.apps;
    const filtered =
      envApps && envApps.length > 0
        ? resolved.filter((app) => envApps.includes(app.key))
        : resolved;
    if (filtered.length === 0) {
      throw new Error(
        `No apps have hosts configured for QUALITY_ENV="${envName}"` +
          (envApps?.length ? ` (manifest apps: ${envApps.join(', ')})` : ''),
      );
    }
    return filtered;
  }

  const selected = resolved.filter((app) => app.key === appFilter);
  if (selected.length === 0) {
    throw new Error(
      `No app "${appFilter}" found for QUALITY_ENV="${envName}". ` +
        `Available: ${resolved.map((a) => a.key).join(', ') || '(none)'}`,
    );
  }

  return selected;
}

export function getAppForProject(projectName: string): ResolvedApp {
  const apps = getSelectedApps();
  const app = apps.find((candidate) => candidate.key === projectName);
  if (!app) {
    throw new Error(`No resolved app for Playwright project "${projectName}"`);
  }
  return app;
}

export function loadAppByKey(key: string): ResolvedApp {
  const envName = (process.env.QUALITY_ENV ?? 'local-quality').toLowerCase();
  const manifests = loadAllAppManifests();
  const manifest = manifests.find((item) => item.key === key);
  if (!manifest) {
    throw new Error(`Unknown app key "${key}"`);
  }
  const resolved = resolveApp(manifest, envName, key);
  if (!resolved) {
    throw new Error(`App "${key}" has no host for QUALITY_ENV="${envName}"`);
  }
  return resolved;
}

export function shouldCaptureSuccessScreenshots(): boolean {
  if (process.env.QUALITY_SCREENSHOTS === 'true') {
    return true;
  }
  const env = getEnvironmentManifest();
  return env.screenshots?.onSuccess === true;
}

export function shouldFailOnConsoleError(): boolean {
  const env = getEnvironmentManifest();
  return env.console?.failOnError !== false;
}

export function shouldFailOnPageError(): boolean {
  const env = getEnvironmentManifest();
  return env.console?.failOnPageError !== false;
}

export function bifrostDotenvForQualityEnv(): string {
  const env = (process.env.QUALITY_ENV ?? 'local-quality').toLowerCase();
  return `.env.${env}`;
}

export function shouldPrepareDatabaseBeforeRun(): boolean {
  if (process.env.QUALITY_SKIP_DB_PREPARE === 'true') {
    return false;
  }
  const manifest = getEnvironmentManifest();
  return manifest.database?.prepareBeforeRun === true;
}

export function expectsSeededDatabase(): boolean {
  return shouldPrepareDatabaseBeforeRun();
}

// Re-export for tests that import a single entry point.
export { loadAppManifest, loadEnvironmentManifest };
