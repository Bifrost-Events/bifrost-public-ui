import fs from 'node:fs';
import path from 'node:path';
import {
  bifrostDotenvForQualityEnv,
  getEnvironmentManifest,
} from './app-config';

export interface QualityEnvProfile {
  qualityEnv: string;
  dotenvFile: string;
  publicAppEnv: string;
  backendApiUrl: string;
  backendDotenvFile: string;
  backendAppEnv: string;
  backendDatabase: string;
}

function parseDotEnvFile(filePath: string): Record<string, string> {
  if (!fs.existsSync(filePath)) {
    throw new Error(`Mangler env-fil: ${filePath}`);
  }

  const vars: Record<string, string> = {};
  const raw = fs.readFileSync(filePath, 'utf8');

  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (trimmed === '' || trimmed.startsWith('#') || !trimmed.includes('=')) {
      continue;
    }

    const eq = trimmed.indexOf('=');
    const name = trimmed.slice(0, eq).trim();
    let value = trimmed.slice(eq + 1).trim();
    if (
      (value.startsWith('"') && value.endsWith('"'))
      || (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }
    vars[name] = value;
  }

  return vars;
}

function databaseNameFromDsn(dsn: string): string {
  for (const part of dsn.split(';')) {
    const [key, value] = part.split('=');
    if (key?.trim().toLowerCase() === 'dbname' && value) {
      return value.trim();
    }
  }

  throw new Error(`Kunne ikke lese dbname fra DB_DSN: ${dsn}`);
}

export function loadQualityEnvProfile(projectRoot: string): QualityEnvProfile {
  const qualityEnv = (process.env.QUALITY_ENV ?? 'local-quality').toLowerCase();
  const dotenvFile = bifrostDotenvForQualityEnv();
  const publicEnvPath = path.join(projectRoot, dotenvFile);
  const publicVars = parseDotEnvFile(publicEnvPath);

  const backendPath = path.resolve(
    projectRoot,
    publicVars.BACKEND_PATH ?? '../bifrost-admin-core',
  );
  const backendDotenvFile = publicVars.BACKEND_DOTENV ?? '.env';
  const backendEnvPath = path.join(backendPath, backendDotenvFile);
  const backendVars = parseDotEnvFile(backendEnvPath);

  const backendApiUrl = (publicVars.BACKEND_API_URL ?? 'http://api.bifrost.local')
    .replace(/\/$/, '');
  const backendDatabase = databaseNameFromDsn(backendVars.DB_DSN ?? '');

  return {
    qualityEnv,
    dotenvFile,
    publicAppEnv: publicVars.APP_ENV ?? qualityEnv,
    backendApiUrl,
    backendDotenvFile,
    backendAppEnv: backendVars.APP_ENV ?? '',
    backendDatabase,
  };
}

export async function assertApacheUsesQualityProfile(
  projectRoot: string,
): Promise<void> {
  const manifest = getEnvironmentManifest();
  if (!manifest.database?.prepareBeforeRun) {
    return;
  }

  const expected = loadQualityEnvProfile(projectRoot);
  const healthUrl = `${expected.backendApiUrl}/api/health`;

  let response: Response;
  try {
    response = await fetch(healthUrl, { signal: AbortSignal.timeout(15_000) });
  } catch (error) {
    throw new Error(
      `Kunne ikke nå admin-core API på ${healthUrl}. Start Apache og sjekk api.bifrost.local.\n${String(error)}`,
    );
  }

  if (!response.ok) {
    throw new Error(
      `Admin-core health feilet (${response.status}) på ${healthUrl}.`,
    );
  }

  const body = (await response.json()) as {
    app_env?: string;
    database_name?: string;
    database?: string;
  };

  const liveAppEnv = body.app_env ?? '(mangler)';
  const liveDatabase = body.database_name ?? '(mangler – restart Apache etter env-endring)';

  const envOk = liveAppEnv === expected.backendAppEnv;
  const dbOk = liveDatabase === expected.backendDatabase;

  if (envOk && dbOk) {
    console.log(
      `[quality] Apache/admin-core OK: APP_ENV=${liveAppEnv}, database=${liveDatabase}`,
    );
    return;
  }

  throw new Error(
    [
      'Quality kjører mot feil database/profil i Apache.',
      '',
      `Forventet (fra ${expected.dotenvFile} → admin-core):`,
      `  APP_ENV=${expected.backendAppEnv}`,
      `  database=${expected.backendDatabase}`,
      '',
      `Apache/api.bifrost.local bruker nå:`,
      `  APP_ENV=${liveAppEnv}`,
      `  database=${liveDatabase}`,
      '',
      'CLI leser .env.local-quality, men Apache laster .env (+ .env.local) som standard.',
      '',
      'Løsning (velg én):',
      '  1. npm run quality:local      (aktiverer midlertidig, gjenoppretter dev-.env etterpå)',
      '  2. npm run quality:activate   (permanent til du kjører quality:deactivate)',
      '  3. SetEnv BIFROST_DOTENV .env.local-quality i Apache vhost',
      '  4. Restart Apache etter endring',
      '',
      `Verifiser: curl ${healthUrl}`,
    ].join('\n'),
  );
}
