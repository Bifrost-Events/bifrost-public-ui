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

  console.log('[quality] Database klar (reset → migrate → minimal seed: roller + admin).');
  console.log(
    '[quality] Apache må bruke samme profil (activate / .env.local-quality) for api.bifrost.local.',
  );
}
