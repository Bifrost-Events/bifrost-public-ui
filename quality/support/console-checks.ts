/**
 * Kjente ufarlige console-meldinger som ikke skal feile testen.
 * Dokumenter hvorfor hver melding er tillatt.
 */
export const CONSOLE_ERROR_ALLOWLIST: RegExp[] = [
  // Favicon mangler lokalt – påvirker ikke funksjonalitet.
  /Failed to load resource.*favicon/i,
  // Nettverksfeil mot backend i lokal dev uten full stack.
  /Failed to fetch/i,
  /NetworkError/i,
  /net::ERR_/i,
];

export interface ConsoleCollector {
  errors: string[];
  attach(page: import('@playwright/test').Page): void;
  assertClean(): void;
}

export function isAllowlistedConsoleMessage(message: string): boolean {
  return CONSOLE_ERROR_ALLOWLIST.some((pattern) => pattern.test(message));
}

export function createConsoleCollector(): ConsoleCollector {
  const errors: string[] = [];

  return {
    errors,
    attach(page) {
      page.on('console', (msg) => {
        if (msg.type() !== 'error') {
          return;
        }
        const text = msg.text();
        if (isAllowlistedConsoleMessage(text)) {
          return;
        }
        errors.push(`[console.error] ${text}`);
      });

      page.on('pageerror', (error) => {
        const text = error.message ?? String(error);
        if (isAllowlistedConsoleMessage(text)) {
          return;
        }
        errors.push(`[pageerror] ${text}`);
      });
    },
    assertClean() {
      if (errors.length > 0) {
        throw new Error(
          `Uventede console/page-feil:\n${errors.map((e) => `  - ${e}`).join('\n')}`,
        );
      }
    },
  };
}
