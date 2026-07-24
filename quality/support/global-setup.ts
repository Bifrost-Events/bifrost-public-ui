import path from 'node:path';
import {
  bifrostDotenvForQualityEnv,
  shouldPrepareDatabaseBeforeRun,
} from './app-config';
import { assertApacheUsesQualityProfile } from './env-profile';
import { prepareQualityDatabase } from './database-prepare';

async function globalSetup(): Promise<void> {
  const projectRoot = path.resolve(__dirname, '../..');

  console.log(
    `[quality] Profil: QUALITY_ENV=${process.env.QUALITY_ENV ?? 'local-quality'} → ${bifrostDotenvForQualityEnv()}`,
  );

  if (shouldPrepareDatabaseBeforeRun()) {
    prepareQualityDatabase(projectRoot);
  } else if (
    process.env.QUALITY_ENV === 'staging' &&
    process.env.QUALITY_STAGING_RESET_VIA_HTTP === 'true'
  ) {
    console.log(
      '[quality] Staging-database allerede nullstilt via HTTPS reset-endepunkt — hopper over lokal prepare.',
    );
  } else {
    console.log(
      '[quality] Hopper over database prepare (ikke aktivert for dette miljøet).',
    );
  }

  // Lettvekt-smoke (modules-up) trenger ikke quality-DB-profil i Apache.
  if (process.env.QUALITY_SKIP_DB_PREPARE === 'true') {
    console.log(
      '[quality] Hopper over Apache quality-profil-sjekk (QUALITY_SKIP_DB_PREPARE).',
    );
    return;
  }

  await assertApacheUsesQualityProfile(projectRoot);
}

export default globalSetup;
