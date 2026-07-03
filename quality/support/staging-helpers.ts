import { expect, type Page } from '@playwright/test';

export const QUALITY_TEST_COMPETITION_NAME = 'Quality test stevne';

export const LOCAL_ADMIN = {
  email: 'admin@bifrost.local',
  password: 'local-admin-change-me',
} as const;

export function uniqueTestEmail(prefix = 'quality'): string {
  const stamp = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  return `${prefix}-${stamp}@example.test`;
}

/** Unikt 8-sifret mobilnummer (unngår kryss-match mot tidligere tester i samme kjøring). */
export function uniqueTestPhone(): string {
  const suffix = Math.floor(Math.random() * 9_000_000) + 1_000_000;
  return `9${suffix}`;
}

/** Unikt navn + telefon (onboarding matcher også uten telefon på fornavn/etternavn). */
export function uniqueTestPerson(prefix: string): {
  firstName: string;
  lastName: string;
  phone: string;
} {
  const stamp = `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
  return {
    firstName: prefix,
    lastName: `Test${stamp}`,
    phone: uniqueTestPhone(),
  };
}

export async function acceptUserAgreement(page: Page): Promise<void> {
  const overlay = page.locator('#agreement-modal-overlay');
  const checkbox = page.locator('#reg-accept-user');

  await page.locator('.agreement-open-modal').click();
  await expect(overlay).toHaveAttribute('aria-hidden', 'false');

  // Avtalen må åpnes for å aktivere avkryssingen; lukk modalen før submit
  // (overlay blokkerer ellers «Registrer bruker og gå videre»).
  await page.locator('.agreement-modal-close').click();
  await expect(overlay).toHaveAttribute('aria-hidden', 'true');

  await expect(checkbox).toBeEnabled();
  await checkbox.check();
  await expect(checkbox).toBeChecked();
}

export interface RegisterUserInput {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  password: string;
}

export async function registerParticipantUser(
  page: Page,
  input: RegisterUserInput,
): Promise<void> {
  await page.goto('/auth/register');
  await page.locator('#reg-first_name').fill(input.firstName);
  await page.locator('#reg-last_name').fill(input.lastName);
  await page.locator('#reg-phone').fill(input.phone);
  await page.locator('#reg-email').fill(input.email);
  await page.locator('#reg-password').fill(input.password);
  await page.locator('#reg-password_confirm').fill(input.password);
  await acceptUserAgreement(page);
  await page.locator('#auth-register-form button[type="submit"]').click();
  await page.waitForURL(/\/onboarding/, { timeout: 30_000 });
}

export async function completeOnboardingAsShooter(page: Page): Promise<void> {
  const participantHeading = page.getByRole('heading', {
    name: /Vi opprettet|Vi fant deltakeren din|Vi fant en mulig match|Kunne ikke sjekke/i,
  });
  await expect(participantHeading).toBeVisible({ timeout: 30_000 });

  const claimButton = page.getByRole('button', { name: /be om å overta/i });
  if (await claimButton.isVisible().catch(() => false)) {
    throw new Error(
      'Onboarding fant eksisterende deltaker fra annen bruker — bruk uniqueTestPhone() i testen.',
    );
  }

  const continueLink = page
    .locator('.onboarding')
    .getByRole('link', { name: 'Fortsett' })
    .first();
  if (await continueLink.isVisible().catch(() => false)) {
    await continueLink.click();
    await page.waitForURL(/step=done/, { timeout: 15_000 });
  }

  await page.goto('/min-side/deltakere');
  await page.waitForURL(/\/min-side\/deltakere/, { timeout: 15_000 });
}

export async function loginParticipantUser(
  page: Page,
  email: string,
  password: string,
  returnTo = '/min-side/deltakere',
): Promise<void> {
  await page.goto(`/auth/login?return_to=${encodeURIComponent(returnTo)}`);
  await page.locator('#login-modal-email').fill(email);
  await page.locator('#login-modal-password').fill(password);
  await page.locator('#login-modal-form button[type="submit"]').click();
  await page.waitForURL(new RegExp(returnTo.replace(/\//g, '\\/')), {
    timeout: 30_000,
  });
}

export async function logoutParticipantUser(page: Page): Promise<void> {
  await page.goto('/auth/logout');
  await page.waitForURL(/\//, { timeout: 15_000 });
}

export async function createAdditionalParticipant(
  page: Page,
  firstName: string,
  lastName: string,
): Promise<void> {
  await page.goto('/min-side/deltakere');
  const openButton = page
    .locator('[data-modal-open="participant-modal"]')
    .first();
  await openButton.click();
  await expect(page.locator('#participant-modal')).toHaveAttribute(
    'aria-hidden',
    'false',
  );

  await page.locator('#first_name').fill(firstName);
  await page.locator('#last_name').fill(lastName);
  const classSelect = page.locator('#class_id');
  const optionValue = await classSelect
    .locator('option')
    .evaluateAll((options) => {
      for (const option of options) {
        const value = (option as HTMLOptionElement).value;
        if (value && value !== '') {
          return value;
        }
      }
      return null;
    });
  expect(optionValue, 'Seed må ha minst én klasse').toBeTruthy();
  await classSelect.selectOption(optionValue!);
  await page.locator('#participant-form button[type="submit"]').click();
  await page.waitForURL(/\/min-side\/deltakere/, { timeout: 15_000 });
}

export async function loginAdminUser(page: Page): Promise<void> {
  await page.goto('/login');
  await page.locator('#email').fill(LOCAL_ADMIN.email);
  await page.locator('#password').fill(LOCAL_ADMIN.password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL((url) => !url.pathname.endsWith('/login'), {
    timeout: 30_000,
  });
}

export const BACKEND_API_URL =
  process.env.BACKEND_API_URL ?? 'http://api.bifrost.local';

/** Registrer deltaker via backend API (brukes fra arrangør-tester). */
export async function registerParticipantViaApi(
  request: import('@playwright/test').APIRequestContext,
  input: RegisterUserInput,
): Promise<void> {
  const response = await request.post(
    `${BACKEND_API_URL}/api/auth/participant/register`,
    {
      data: {
        first_name: input.firstName,
        last_name: input.lastName,
        email: input.email,
        phone: input.phone,
        password: input.password,
        password_confirm: input.password,
        accept_user_agreement: true,
        user_agreement_version: '1.0',
      },
    },
  );
  expect(response.ok(), `register failed: ${response.status()}`).toBeTruthy();
}

export async function loginArrangorUser(
  page: Page,
  email: string,
  password: string,
): Promise<void> {
  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL((url) => !url.pathname.endsWith('/login'), {
    timeout: 30_000,
  });
}

export async function completeOrganizerOnboarding(
  page: Page,
  organizationName: string,
): Promise<void> {
  await page.goto('/bli-arrangor');
  await page.locator('input[name="accept_terms"]').check();
  await page.getByRole('button', { name: 'Fortsett' }).click();
  await page.waitForURL(/\/bli-arrangor\/opprett/, { timeout: 15_000 });

  await page.locator('#name').fill(organizationName);
  await page.getByRole('button', { name: 'Opprett arrangør' }).click();
  await page.waitForURL(/\//, { timeout: 30_000 });
}

/** Godkjenn siste ventende arrangørsøknad via admin API. */
export async function approveLatestOrganizerApplication(
  request: import('@playwright/test').APIRequestContext,
): Promise<void> {
  const login = await request.post(`${BACKEND_API_URL}/api/auth/login`, {
    data: {
      email: LOCAL_ADMIN.email,
      password: LOCAL_ADMIN.password,
    },
  });
  expect(login.ok(), `admin login failed: ${login.status()}`).toBeTruthy();

  const list = await request.get(
    `${BACKEND_API_URL}/api/admin/organization-season-approvals`,
  );
  expect(list.ok(), `list approvals failed: ${list.status()}`).toBeTruthy();
  const payload = (await list.json()) as {
    data?: { approvals?: Array<{ id?: number }> };
    approvals?: Array<{ id?: number }>;
  };
  const approvals = payload.data?.approvals ?? payload.approvals ?? [];
  expect(approvals.length, 'Ingen ventende søknader').toBeGreaterThan(0);
  const id = approvals[0]?.id;
  expect(id, 'Mangler approval id').toBeTruthy();

  const approve = await request.post(
    `${BACKEND_API_URL}/api/admin/organization-season-approvals/${id}/approve`,
    { data: {} },
  );
  expect(approve.ok(), `approve failed: ${approve.status()}`).toBeTruthy();
}

export async function registerForQualityTestCompetition(
  page: Page,
  participantName: string,
): Promise<void> {
  await page.goto('/calendar');
  await page.getByRole('link', { name: QUALITY_TEST_COMPETITION_NAME }).click();
  await expect(page.getByRole('heading', { name: 'Påmelding' })).toBeVisible();

  const freeSlot = page.locator('button.pick-slot').first();
  await expect(freeSlot).toBeVisible();
  await freeSlot.click();

  const dialog = page.locator('#signup-dialog');
  await expect(dialog).toBeVisible();
  const participantSelect = page.locator('#signup_participant_id');
  await participantSelect.selectOption({ label: participantName });
  await page.locator('#signup-form button[type="submit"]').click();
  await page.waitForURL(/\/calendar\/\d+/, { timeout: 15_000 });
}
