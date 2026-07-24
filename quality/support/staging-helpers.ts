import { expect, type Page } from '@playwright/test';

export const QUALITY_TEST_COMPETITION_NAME = 'Quality test stevne';

/** Bootstrap-admin fra quality-seed (002_quality_admin_user.sql). */
export const LOCAL_ADMIN = {
  email: 'quality.admin@bifrost.test',
  password: 'QualityAdmin123!',
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
  options?: { baseUrl?: string },
): Promise<void> {
  const base = options?.baseUrl?.replace(/\/$/, '') ?? '';
  await page.goto(`${base}/auth/register`);
  await page.locator('#reg-first_name').fill(input.firstName);
  await page.locator('#reg-last_name').fill(input.lastName);
  await page.locator('#reg-phone').fill(input.phone);
  await page.locator('#reg-email').fill(input.email);
  await page.locator('#reg-password').fill(input.password);
  await page.locator('#reg-password_confirm').fill(input.password);
  await acceptUserAgreement(page);
  await page.locator('#auth-register-form button[type="submit"]').click();
  // V3: AuthController sender til profil (ikke lenger /onboarding)
  await page.waitForURL(/\/min-side\/profil/, { timeout: 30_000 });
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
  // Clear bypass auto-session so /login shows the form (AC-01).
  await page.goto('/logout');
  await page.goto('/login');
  await expect(page.locator('#email')).toBeVisible({ timeout: 15_000 });
  await page.locator('#email').fill(LOCAL_ADMIN.email);
  await page.locator('#password').fill(LOCAL_ADMIN.password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL((url) => !url.pathname.endsWith('/login'), {
    timeout: 30_000,
  });
}

/** Unik stamp for admin-core write-tester (f.eks. organisasjonsnavn). */
export function uniqueAdminStamp(): string {
  return `quality-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
}

/**
 * Reelle public-portal-cuper: app/domene + eierorganisasjon + eier-/admin-brukere.
 * Matcher CupConfigLoader / quality apps (jaktfeltcup, namdal, slatlem).
 */
export const PORTAL_CUPS = [
  {
    name: 'Jaktfeltcup',
    applicationKey: 'jaktfeltcup',
    description: 'Nasjonal jaktfeltcup',
    hostname: 'jaktfeltcup.local',
    arrangorHostname: 'arrangor.jaktfeltcup.local',
    organizationName: 'Nasjonal 15m Jaktfeltcup',
    organizationShortName: 'Jaktfeltcup',
    spaceName: 'Jaktfeltcup',
    spaceShortName: 'Jaktfeltcup',
    spaceSlug: 'portal-jaktfeltcup',
    owner: {
      email: 'owner.jaktfeltcup@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Eier',
      lastName: 'Jaktfeltcup',
    },
    admin: {
      email: 'admin.jaktfeltcup@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Admin',
      lastName: 'Jaktfeltcup',
    },
  },
  {
    name: 'Jaktfeltkarusell Namdal',
    applicationKey: 'jaktfeltkarusell-namdal',
    description: 'Regional jaktfeltkarusell',
    hostname: 'namdal.jaktfeltkarusell.local',
    arrangorHostname: 'arrangor.namdal.jaktfeltkarusell.local',
    organizationName: 'Jaktfeltkarusell Namdal',
    organizationShortName: 'Namdal',
    spaceName: 'Jaktfeltkarusell Namdal',
    spaceShortName: 'Namdal',
    spaceSlug: 'portal-namdal',
    owner: {
      email: 'owner.namdal@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Eier',
      lastName: 'Namdal',
    },
    admin: {
      email: 'admin.namdal@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Admin',
      lastName: 'Namdal',
    },
  },
  {
    name: 'Slatlem Cup',
    applicationKey: 'slatlem',
    description: 'Slatlem Cup',
    hostname: 'slatlemcup.local',
    arrangorHostname: 'arrangor.slatlemcup.local',
    organizationName: 'Slatlem Cup',
    organizationShortName: 'Slatlem',
    spaceName: 'Slatlem Cup',
    spaceShortName: 'Slatlem',
    spaceSlug: 'portal-slatlem',
    owner: {
      email: 'owner.slatlem@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Eier',
      lastName: 'Slatlem',
    },
    admin: {
      email: 'admin.slatlem@bifrost.test',
      password: 'QualityCup123!',
      firstName: 'Admin',
      lastName: 'Slatlem',
    },
  },
] as const;

/** Public-portal UI-terminologi (matcher events demo-seed / CupConfig). */
export const CUP_EVENT_SPACE_LABELS = {
  event_space: { singular: 'Cup', plural: 'Cuper' },
  series: { singular: 'Sesong', plural: 'Sesonger' },
  subseries: { singular: 'Runde', plural: 'Runder' },
  event: { singular: 'Stevne', plural: 'Stevner' },
} as const;

/** Alias for app/domene-felt (samme objekter som PORTAL_CUPS). */
export const PORTAL_APPLICATIONS = PORTAL_CUPS;

export async function createAdminApplication(
  page: Page,
  input: {
    name: string;
    applicationKey: string;
    description?: string;
    visibility?: 'public' | 'internal';
    showInSharedLoginList?: boolean;
  },
): Promise<void> {
  await page.goto('/core/applications/new');
  await expect(page.getByRole('heading', { name: /Opprett applikasjon/i })).toBeVisible();
  await page.locator('#name').fill(input.name);
  await page.locator('#application_key').fill(input.applicationKey);
  if (input.description) {
    await page.locator('#description').fill(input.description);
  }
  if (input.visibility) {
    await page.locator('#visibility').selectOption(input.visibility);
  }
  if (input.showInSharedLoginList) {
    await page.locator('#show_in_shared_login_list').check();
  }
  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/core\/applications\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText('Applikasjon opprettet.');
  await expect(page.locator('body')).toContainText(input.name);
  await expect(page.locator('body')).toContainText(input.applicationKey);
}

export async function createAdminDomain(
  page: Page,
  input: {
    applicationKey: string;
    hostname: string;
    isPrimary?: boolean;
  },
): Promise<void> {
  await page.goto('/core/domains/new');
  await expect(page.getByRole('heading', { name: /Opprett domene/i })).toBeVisible();
  const option = page
    .locator('#application_id option')
    .filter({ hasText: input.applicationKey });
  await expect(option.first()).toBeAttached({ timeout: 10_000 });
  const value = await option.first().getAttribute('value');
  expect(value, `Fant ikke applikasjon ${input.applicationKey} i select`).toBeTruthy();
  await page.locator('#application_id').selectOption(value!);
  await page.locator('#hostname').fill(input.hostname);
  if (input.isPrimary) {
    await page.locator('#is_primary').check();
  }
  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/core\/domains\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText('Domene opprettet.');
  await expect(page.locator('body')).toContainText(input.hostname);
}

/** Opprett de tre reelle portal-appene med public- og arrangør-domene. */
export async function createPortalApplicationsAndDomains(page: Page): Promise<void> {
  for (const cup of PORTAL_CUPS) {
    await createAdminApplication(page, {
      name: cup.name,
      applicationKey: cup.applicationKey,
      description: cup.description,
      visibility: 'public',
      showInSharedLoginList: true,
    });
    await createAdminDomain(page, {
      applicationKey: cup.applicationKey,
      hostname: cup.hostname,
      isPrimary: true,
    });
    await createAdminDomain(page, {
      applicationKey: cup.applicationKey,
      hostname: cup.arrangorHostname,
      isPrimary: false,
    });
  }
}

/** Les tallkort fra admin dashboard (`/`). */
export async function readAdminDashboardStats(
  page: Page,
): Promise<Record<string, number>> {
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  const cards = page.locator('.stat-card');
  const count = await cards.count();
  const stats: Record<string, number> = {};
  for (let i = 0; i < count; i++) {
    const card = cards.nth(i);
    const label = (await card.locator('span').innerText()).trim();
    const value = Number.parseInt((await card.locator('strong').innerText()).trim(), 10);
    stats[label] = value;
  }
  return stats;
}

export async function createAdminOrganization(
  page: Page,
  input: string | { name: string; shortName?: string; visibility?: 'public' | 'internal' },
): Promise<void> {
  const org = typeof input === 'string' ? { name: input } : input;
  await page.goto('/core/organizations/new');
  await expect(page.getByRole('heading', { name: /Opprett organisasjon/i })).toBeVisible();
  await page.locator('#name').fill(org.name);
  if (org.shortName) {
    await page.locator('#short_name').fill(org.shortName);
  }
  if (org.visibility) {
    await page.locator('#visibility').selectOption(org.visibility);
  }
  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/core\/organizations\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText('Organisasjon opprettet.');
  await expect(page.locator('body')).toContainText(org.name);
}

export async function createAdminUser(
  page: Page,
  input: {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    username?: string;
  },
): Promise<void> {
  await page.goto('/core/users/new');
  await expect(page.getByRole('heading', { name: /Opprett bruker/i })).toBeVisible();
  await page.locator('#email').fill(input.email);
  if (input.username) {
    await page.locator('#username').fill(input.username);
  }
  await page.locator('#first_name').fill(input.firstName);
  await page.locator('#last_name').fill(input.lastName);
  await page.locator('#password').fill(input.password);
  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/core\/users\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText('Bruker opprettet.');
  await expect(page.locator('body')).toContainText(input.email);
}

export async function createAdminMembership(
  page: Page,
  input: {
    organizationName: string;
    personEmail: string;
    roleKeys: string[];
  },
): Promise<void> {
  await page.goto('/core/memberships/create');
  await expect(
    page.getByRole('heading', { name: /Legg til person med roller/i }),
  ).toBeVisible();

  const orgOption = page.locator('#org_id option').filter({ hasText: input.organizationName });
  await expect(orgOption.first()).toBeAttached({ timeout: 10_000 });
  const orgValue = await orgOption.first().getAttribute('value');
  expect(orgValue, `Fant ikke organisasjon ${input.organizationName}`).toBeTruthy();
  await page.locator('#org_id').selectOption(orgValue!);

  const personOption = page
    .locator('#person_id option')
    .filter({ hasText: input.personEmail });
  await expect(personOption.first()).toBeAttached({ timeout: 10_000 });
  const personValue = await personOption.first().getAttribute('value');
  expect(personValue, `Fant ikke person ${input.personEmail}`).toBeTruthy();
  await page.locator('#person_id').selectOption(personValue!);

  for (const roleKey of input.roleKeys) {
    const roleCheckbox = page
      .locator('label.checkbox-row')
      .filter({ hasText: roleKey })
      .locator('input[type="checkbox"]');
    await expect(roleCheckbox, `Mangler rolle ${roleKey}`).toBeVisible();
    await roleCheckbox.check();
  }

  await page.getByRole('button', { name: 'Lagre medlemskap' }).click();
  await page.waitForURL(/\/core\/memberships\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText('Person lagt til med roller.');
}

/** Per cup: organisasjon + eier (org_owner) + administrator (org_admin). */
export async function createCupOrganizationsWithOwners(page: Page): Promise<void> {
  for (const cup of PORTAL_CUPS) {
    await createAdminOrganization(page, {
      name: cup.organizationName,
      shortName: cup.organizationShortName,
      visibility: 'public',
    });

    await createAdminUser(page, {
      email: cup.owner.email,
      password: cup.owner.password,
      firstName: cup.owner.firstName,
      lastName: cup.owner.lastName,
      username: cup.owner.email.split('@')[0],
    });
    await createAdminMembership(page, {
      organizationName: cup.organizationName,
      personEmail: cup.owner.email,
      roleKeys: ['org_owner'],
    });

    await createAdminUser(page, {
      email: cup.admin.email,
      password: cup.admin.password,
      firstName: cup.admin.firstName,
      lastName: cup.admin.lastName,
      username: cup.admin.email.split('@')[0],
    });
    await createAdminMembership(page, {
      organizationName: cup.organizationName,
      personEmail: cup.admin.email,
      roleKeys: ['org_admin'],
    });
  }
}

/**
 * Gi plattform-admin org_admin på cup-orgene.
 * Nødvendig for å velge eierorganisasjon når event space opprettes i admin.
 */
export async function grantPlatformAdminOnCupOrganizations(page: Page): Promise<void> {
  for (const cup of PORTAL_CUPS) {
    await createAdminMembership(page, {
      organizationName: cup.organizationName,
      personEmail: LOCAL_ADMIN.email,
      roleKeys: ['org_admin'],
    });
  }
}

export async function createAdminEventSpace(
  page: Page,
  input: {
    applicationName: string;
    organizationName: string;
    name: string;
    shortName?: string;
    slug?: string;
    status?: 'draft' | 'active' | 'inactive' | 'archived';
    visibility?: 'private' | 'internal' | 'public';
    labels?: typeof CUP_EVENT_SPACE_LABELS;
  },
): Promise<void> {
  await page.goto('/events/spaces/new');
  await expect(page.getByRole('heading', { name: /Opprett /i })).toBeVisible();

  const appOption = page
    .locator('#application_id option')
    .filter({ hasText: input.applicationName });
  await expect(appOption.first()).toBeAttached({ timeout: 10_000 });
  const appValue = await appOption.first().getAttribute('value');
  expect(appValue, `Fant ikke applikasjon ${input.applicationName}`).toBeTruthy();
  await page.locator('#application_id').selectOption(appValue!);

  const orgOption = page
    .locator('#owner_org_id option')
    .filter({ hasText: input.organizationName });
  await expect(
    orgOption.first(),
    `Fant ikke eierorg ${input.organizationName} (mangler org_admin for innlogget bruker?)`,
  ).toBeAttached({ timeout: 10_000 });
  const orgValue = await orgOption.first().getAttribute('value');
  expect(orgValue, `Fant ikke organisasjon ${input.organizationName}`).toBeTruthy();
  await page.locator('#owner_org_id').selectOption(orgValue!);

  await page.locator('#name').fill(input.name);
  if (input.shortName) {
    await page.locator('#short_name').fill(input.shortName);
  }
  if (input.slug) {
    await page.locator('#slug').fill(input.slug);
  }
  await page.locator('#status').selectOption(input.status ?? 'active');
  await page.locator('#visibility').selectOption(input.visibility ?? 'public');

  const labels = input.labels ?? CUP_EVENT_SPACE_LABELS;
  for (const [key, pair] of Object.entries(labels)) {
    await page.locator(`input[name="ui_labels[${key}][singular]"]`).fill(pair.singular);
    await page.locator(`input[name="ui_labels[${key}][plural]"]`).fill(pair.plural);
  }

  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/events\/spaces\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText(/opprettet\./i);
  await expect(page.locator('body')).toContainText(input.name);
  if (input.slug) {
    await expect(page.locator('body')).toContainText(input.slug);
  }
}

/** Per cup: aktivt public event space med cup-terminologi. */
export async function createCupEventSpaces(page: Page): Promise<void> {
  for (const cup of PORTAL_CUPS) {
    await createAdminEventSpace(page, {
      applicationName: cup.name,
      organizationName: cup.organizationName,
      name: cup.spaceName,
      shortName: cup.spaceShortName,
      slug: cup.spaceSlug,
      status: 'active',
      visibility: 'public',
      labels: CUP_EVENT_SPACE_LABELS,
    });
  }
}

/** Felles sesongår for portal-cuper (matcher events demo-seed-året). */
export const CUP_SEASON_YEAR = '2027' as const;

export async function createAdminSeries(
  page: Page,
  input: {
    spaceName: string;
    organizationName: string;
    name: string;
    shortName?: string;
    seasonLabel?: string;
    seriesType?: string;
    status?: 'draft' | 'active' | 'inactive' | 'archived';
    visibility?: 'private' | 'internal' | 'public';
  },
): Promise<void> {
  await page.goto('/events/series/new');
  await expect(page.getByRole('heading', { name: /Opprett /i })).toBeVisible();

  const spaceOption = page
    .locator('#space_id option')
    .filter({ hasText: input.spaceName });
  await expect(spaceOption.first()).toBeAttached({ timeout: 10_000 });
  const spaceValue = await spaceOption.first().getAttribute('value');
  expect(spaceValue, `Fant ikke event space ${input.spaceName}`).toBeTruthy();
  await page.locator('#space_id').selectOption(spaceValue!);

  const orgOption = page
    .locator('#owner_org_id option')
    .filter({ hasText: input.organizationName });
  await expect(orgOption.first()).toBeAttached({ timeout: 10_000 });
  const orgValue = await orgOption.first().getAttribute('value');
  expect(orgValue, `Fant ikke organisasjon ${input.organizationName}`).toBeTruthy();
  await page.locator('#owner_org_id').selectOption(orgValue!);

  await page.locator('#name').fill(input.name);
  if (input.shortName) {
    await page.locator('#short_name').fill(input.shortName);
  }
  if (input.seasonLabel) {
    await page.locator('#season_label').fill(input.seasonLabel);
  }
  if (input.seriesType) {
    await page.locator('#series_type').selectOption(input.seriesType);
  }
  await page.locator('#status').selectOption(input.status ?? 'active');
  await page.locator('#visibility').selectOption(input.visibility ?? 'public');

  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/events\/series\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash-success')).toContainText(/opprettet\./i);
  await expect(page.locator('body')).toContainText(input.name);
}

/** Sesongnavn for staging (opprettes i arrangørportal, ikke admin). */
export function cupSeasonName(cup: (typeof PORTAL_CUPS)[number]): string {
  return `${cup.spaceShortName} ${CUP_SEASON_YEAR}`;
}

/** Map Playwright arrangør-app key → PORTAL_CUPS-rad. */
export function portalCupForArrangorApp(
  appKey: string,
): (typeof PORTAL_CUPS)[number] | undefined {
  const byAppKey: Record<string, string> = {
    'arrangor-jaktfeltcup': 'jaktfeltcup',
    'arrangor-namdal': 'jaktfeltkarusell-namdal',
    'arrangor-slatlem': 'slatlem',
  };
  const applicationKey = byAppKey[appKey] ?? appKey.replace(/^arrangor-/, '');
  return PORTAL_CUPS.find((cup) => cup.applicationKey === applicationKey);
}

/**
 * Opprett root-sesong i arrangørportal V3 (`/sesonger/ny`).
 * Krever innlogget cup-eier/admin med aktiv org/space (etter admin bootstrap).
 * @returns series_id for den nye sesongen (lander på struktur-siden)
 */
export async function createArrangorSeason(
  page: Page,
  input: {
    name: string;
    shortName?: string;
    seasonLabel?: string;
    status?: 'draft' | 'active' | 'inactive';
    visibility?: 'private' | 'internal' | 'public';
  },
): Promise<number> {
  await page.goto('/sesonger/ny');
  await expect(page.getByRole('heading', { name: /Ny Sesong/i })).toBeVisible({
    timeout: 15_000,
  });

  await page.locator('#name').fill(input.name);
  if (input.shortName) {
    await page.locator('#short_name').fill(input.shortName);
  }
  if (input.seasonLabel) {
    await page.locator('#season_label').fill(input.seasonLabel);
  }
  await page.locator('#status').selectOption(input.status ?? 'active');
  await page.locator('#visibility').selectOption(input.visibility ?? 'public');

  await page.getByRole('button', { name: 'Lagre' }).click();
  await page.waitForURL(/\/sesonger\/\d+\/struktur/, { timeout: 30_000 });
  await expect(page.locator('.flash.success')).toContainText(/Serie opprettet/i);
  await expect(page.getByRole('heading', { name: 'Sesongstruktur' })).toBeVisible();

  const match = page.url().match(/\/sesonger\/(\d+)\/struktur/);
  expect(match, 'Forventet redirect til /sesonger/{id}/struktur').toBeTruthy();
  return Number(match![1]);
}

export type ArrangorSeasonStructure = 'events' | 'rounds';

/** Antall innledende runder for Jaktfeltcup-sesong. */
export const JAKTFELTCUP_ROUND_COUNT = 5 as const;

/**
 * Velg sesongstruktur (stevner direkte vs gruppert i runder).
 * Flash: «Sesongstruktur lagret.» → `/cup`
 */
export async function setArrangorSeasonStructure(
  page: Page,
  seriesId: number,
  structureType: ArrangorSeasonStructure,
): Promise<void> {
  const onStruktur = /\/sesonger\/\d+\/struktur/.test(page.url());
  if (!onStruktur || !page.url().includes(`/sesonger/${seriesId}/struktur`)) {
    await page.goto(`/sesonger/${seriesId}/struktur`);
  }
  await expect(page.getByRole('heading', { name: 'Sesongstruktur' })).toBeVisible({
    timeout: 15_000,
  });

  if (structureType === 'rounds') {
    await page.locator('#struct-rounds').check();
  } else {
    await page.locator('#struct-events').check();
  }

  await page.locator('#structure-save').click();
  await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === '/cup', {
    timeout: 30_000,
  });
  await expect(page.locator('.flash.success')).toContainText(/Sesongstruktur lagret/i);
}

/**
 * Opprett runde under sesong (krever structure_type=rounds).
 * Lander på `/cup` med flash «Serie opprettet.»
 */
export async function createArrangorRound(
  page: Page,
  seasonId: number,
  input: {
    name: string;
    shortName?: string;
    sortOrder?: number;
    status?: 'draft' | 'active' | 'inactive';
    visibility?: 'private' | 'internal' | 'public';
  },
): Promise<void> {
  await page.goto(`/sesonger/${seasonId}/undersoner/ny`);
  await expect(page.getByRole('heading', { name: /Ny Runde/i })).toBeVisible({
    timeout: 15_000,
  });

  await page.locator('#name').fill(input.name);
  if (input.shortName) {
    await page.locator('#short_name').fill(input.shortName);
  }
  if (input.sortOrder !== undefined) {
    await page.locator('#sort_order').fill(String(input.sortOrder));
  }
  await page.locator('#status').selectOption(input.status ?? 'active');
  await page.locator('#visibility').selectOption(input.visibility ?? 'public');

  await page.getByRole('button', { name: 'Lagre' }).click();
  await page.waitForURL((url) => url.pathname.replace(/\/$/, '') === '/cup', {
    timeout: 30_000,
  });
  await expect(page.locator('.flash.success')).toContainText(/Serie opprettet/i);
  await expect(page.locator('body')).toContainText(input.name);
}

/** Jaktfeltcup: rounds-struktur + N runder under sesongen. */
export async function setupJaktfeltcupRounds(
  page: Page,
  seasonId: number,
  roundCount: number = JAKTFELTCUP_ROUND_COUNT,
): Promise<void> {
  await setArrangorSeasonStructure(page, seasonId, 'rounds');
  for (let i = 1; i <= roundCount; i += 1) {
    await createArrangorRound(page, seasonId, {
      name: `Runde ${i}`,
      shortName: `R${i}`,
      sortOrder: i,
    });
  }
}

/** Jaktfeltkarusell (Namdal m.fl.): stevner direkte under sesongen. */
export async function setupKarusellDirectEvents(
  page: Page,
  seasonId: number,
): Promise<void> {
  await setArrangorSeasonStructure(page, seasonId, 'events');
}

/** Public-app key for en arrangør Playwright-app. */
export function publicAppKeyForArrangor(arrangorAppKey: string): string {
  const map: Record<string, string> = {
    'arrangor-jaktfeltcup': 'jaktfeltcup',
    'arrangor-namdal': 'namdal',
    'arrangor-slatlem': 'slatlem',
  };
  return map[arrangorAppKey] ?? arrangorAppKey.replace(/^arrangor-/, '');
}

export async function logoutArrangorUser(page: Page): Promise<void> {
  await page.locator('form[action="/logout"] button[type="submit"]').click();
  await page.waitForURL(/\/login/, { timeout: 15_000 });
}

/** Åpne sesong for arrangørsøknader (krever godkjenning). */
export async function openSeasonForOrganizerApplications(
  page: Page,
  seasonId: number,
): Promise<void> {
  await page.goto(`/sesonger/${seasonId}/arrangor-soknader`);
  await expect(page.getByRole('heading', { name: 'Søknader om stevne' })).toBeVisible({
    timeout: 15_000,
  });
  await page.locator('#onboarding_mode').selectOption('approval_required');
  await page.locator('#onboarding-settings-save').click();
  await expect(page.locator('.flash.success')).toContainText(
    /Innstillinger for arrangørsøknader er lagret/i,
  );
}

export async function createArrangorOrganization(
  page: Page,
  name: string,
): Promise<void> {
  await page.goto('/mine-organisasjoner/ny');
  await expect(page.getByRole('heading', { name: 'Ny organisasjon' })).toBeVisible({
    timeout: 15_000,
  });
  await page.locator('#name').fill(name);
  await page.getByRole('button', { name: 'Opprett' }).click();
  await page.waitForURL(/\/mine-organisasjoner/, { timeout: 30_000 });
  await expect(page.locator('.flash.success')).toContainText(/Organisasjonen er opprettet/i);
  await expect(page.locator('body')).toContainText(name);
}

/**
 * Opprett utkast og send inn arrangørsøknad mot en sesong.
 * @returns application id
 */
export async function submitOrganizerApplication(
  page: Page,
  input: {
    seasonId: number;
    seasonName?: string;
    eventName: string;
    message?: string;
  },
): Promise<number> {
  await page.goto('/arrangor-soknader/ny');
  await expect(page.getByRole('heading', { name: 'Ny arrangørsøknad' })).toBeVisible({
    timeout: 15_000,
  });

  const seriesOption = page.locator(`#series_id option[value="${input.seasonId}"]`);
  await expect(
    seriesOption,
    `Serie ${input.seasonId} må være åpen for søknader`,
  ).toBeAttached({ timeout: 10_000 });
  await expect(seriesOption).not.toBeDisabled();
  await page.locator('#series_id').selectOption(String(input.seasonId));

  await page.locator('#requested_event_name').fill(input.eventName);
  if (input.message) {
    await page.locator('#message').fill(input.message);
  }

  await page.getByRole('button', { name: 'Lagre utkast' }).click();
  await page.waitForURL(/\/arrangor-soknader\/\d+/, { timeout: 30_000 });
  await expect(page.locator('.flash.success')).toContainText(/Utkast til søknad er lagret/i);

  const match = page.url().match(/\/arrangor-soknader\/(\d+)/);
  expect(match).toBeTruthy();
  const applicationId = Number(match![1]);

  await page.getByRole('button', { name: 'Send inn' }).click();
  await expect(page.locator('.flash.success')).toContainText(/Søknaden er sendt inn/i);
  await expect(page.locator('body')).toContainText('Sendt inn');

  return applicationId;
}

/** Godkjenn arrangørsøknad som serieeier/cup-eier. */
export async function approveOrganizerApplication(
  page: Page,
  seasonId: number,
  applicationId: number,
): Promise<void> {
  await page.goto(`/sesonger/${seasonId}/arrangor-soknader/${applicationId}`);
  await expect(page.getByRole('heading', { name: /Behandle søknad/i })).toBeVisible({
    timeout: 15_000,
  });
  await page.locator('#review_notes_ok').fill('Godkjent i quality-staging');
  await page.getByRole('button', { name: 'Godkjenn' }).click();
  await expect(page.locator('.flash.success')).toContainText(/Søknaden er godkjent/i);
  await expect(page.locator('body')).toContainText('Godkjent');
}

/** Full cup-bootstrap: apper/domener + org + eier/admin + event spaces (uten sesonger). */
export async function bootstrapPortalCups(page: Page): Promise<void> {
  await createPortalApplicationsAndDomains(page);
  await createCupOrganizationsWithOwners(page);
  await grantPlatformAdminOnCupOrganizations(page);
  await createCupEventSpaces(page);
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
