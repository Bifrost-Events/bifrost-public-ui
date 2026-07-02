import fs from 'node:fs';
import path from 'node:path';
import yaml from 'js-yaml';

const QUALITY_ROOT = path.resolve(__dirname, '..');

export interface RouteManifest {
  path: string;
  name: string;
}

export interface AppExpected {
  titleContains?: string;
  visibleText?: string[];
  cupKey?: string;
  /** For portal-apps: forventet streng i /health JSON (f.eks. admin_ui). */
  healthMarker?: string;
}

export interface AppManifest {
  name: string;
  key: string;
  /** cup = public cup-UI; portal = admin/arrangør (ingen cupKey). */
  kind?: 'cup' | 'portal';
  cupId?: string;
  hosts: Record<string, string>;
  /** HTTP Host-header ved testing via localhost (f.eks. composer serve). */
  hostHeader?: string;
  /** Hopp over tester til appen er deployet lokalt (f.eks. arrangor-ui under utvikling). */
  skipUntilReady?: boolean;
  expected: AppExpected;
  routes: RouteManifest[];
}

export interface EnvironmentManifest {
  environment: string;
  description?: string;
  timeouts?: {
    test?: number;
    expect?: number;
    action?: number;
    navigation?: number;
  };
  screenshots?: {
    onSuccess?: boolean;
    onFailure?: boolean;
  };
  console?: {
    failOnError?: boolean;
    failOnPageError?: boolean;
  };
  database?: {
    prepareBeforeRun?: boolean;
  };
  /** Når satt og QUALITY_APP=all: kun disse cup-nøklene kjøres */
  apps?: string[];
}

export function qualityRoot(): string {
  return QUALITY_ROOT;
}

export function loadYamlFile<T>(filePath: string): T {
  const raw = fs.readFileSync(filePath, 'utf8');
  const data = yaml.load(raw);
  if (!data || typeof data !== 'object') {
    throw new Error(`Invalid YAML manifest: ${filePath}`);
  }
  return data as T;
}

export function listAppManifestFiles(): string[] {
  const appsDir = path.join(QUALITY_ROOT, 'apps');
  return fs
    .readdirSync(appsDir)
    .filter((file) => file.endsWith('.yml') || file.endsWith('.yaml'))
    .sort()
    .map((file) => path.join(appsDir, file));
}

export function loadAppManifest(filePath: string): AppManifest {
  const manifest = loadYamlFile<AppManifest>(filePath);
  if (!manifest.key || !manifest.hosts) {
    throw new Error(`App manifest missing key or hosts: ${filePath}`);
  }
  const kind = manifest.kind ?? (manifest.cupId ? 'cup' : 'portal');
  if (kind === 'cup' && !manifest.cupId) {
    throw new Error(`Cup app manifest missing cupId: ${filePath}`);
  }
  return { ...manifest, kind };
}

export function loadAllAppManifests(): AppManifest[] {
  return listAppManifestFiles().map(loadAppManifest);
}

export function loadEnvironmentManifest(envName?: string): EnvironmentManifest {
  const env = (envName ?? process.env.QUALITY_ENV ?? 'local-quality').toLowerCase();
  const filePath = path.join(QUALITY_ROOT, 'manifests', `${env}.yml`);
  if (!fs.existsSync(filePath)) {
    throw new Error(
      `Unknown QUALITY_ENV "${env}". Expected manifest at ${filePath}`,
    );
  }
  return loadYamlFile<EnvironmentManifest>(filePath);
}

export function parseHostFromUrl(url: string): string | undefined {
  try {
    return new URL(url).hostname;
  } catch {
    return undefined;
  }
}
