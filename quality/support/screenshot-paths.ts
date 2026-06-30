import path from 'node:path';
import { qualityRoot } from './manifest-loader';

export function slugifyRouteName(routeName: string): string {
  return routeName
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80);
}

export function screenshotPath(
  environment: string,
  appKey: string,
  routeName: string,
  variant: 'capture' | 'failure' = 'capture',
): string {
  const slug = slugifyRouteName(routeName);
  const fileName = variant === 'failure' ? `${slug}-failure.png` : `${slug}.png`;
  return path.join(
    qualityRoot(),
    'screenshots',
    environment,
    appKey,
    fileName,
  );
}

export function screenshotRelativePath(
  environment: string,
  appKey: string,
  routeName: string,
  variant: 'capture' | 'failure' = 'capture',
): string {
  const slug = slugifyRouteName(routeName);
  const fileName = variant === 'failure' ? `${slug}-failure.png` : `${slug}.png`;
  return path.posix.join(
    'quality/screenshots',
    environment,
    appKey,
    fileName,
  );
}
