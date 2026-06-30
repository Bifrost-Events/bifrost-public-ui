import { execSync } from 'node:child_process';
import path from 'node:path';
import {
  bifrostDotenvForQualityEnv,
  shouldPrepareDatabaseBeforeRun,
} from './app-config';

/**
 * Kjør reset + migrate + seed mot backend-database (kun local-quality/staging).
 */
export function prepareQualityDatabase(projectRoot: string): void {
  const dotenv = bifrostDotenvForQualityEnv();
  const script = path.join(projectRoot, 'quality', 'bin', 'quality-db.php');

  console.log(`[quality] Forbereder database via ${dotenv} …`);

  execSync(`php ${JSON.stringify(script)} prepare`, {
    cwd: projectRoot,
    stdio: 'inherit',
    env: {
      ...process.env,
      BIFROST_DOTENV: dotenv,
    },
  });

  console.log('[quality] Database klar (reset → migrate → seed).');
  console.log(
    '[quality] Backend må bruke samme profil (f.eks. .env.local-quality) for tenant/API-data.',
  );
}
