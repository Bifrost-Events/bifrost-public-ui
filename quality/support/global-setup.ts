import path from 'node:path';
import {
  bifrostDotenvForQualityEnv,
  shouldPrepareDatabaseBeforeRun,
} from './app-config';
import { prepareQualityDatabase } from './database-prepare';

async function globalSetup(): Promise<void> {
  const projectRoot = path.resolve(__dirname, '../..');

  if (!shouldPrepareDatabaseBeforeRun()) {
    if (
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
    return;
  }

  console.log(
    `[quality] Miljø: ${process.env.QUALITY_ENV ?? 'local-quality'} → ${bifrostDotenvForQualityEnv()}`,
  );

  prepareQualityDatabase(projectRoot);
}

export default globalSetup;
