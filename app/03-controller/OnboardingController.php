<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\Auth;
use App\Support\PublicView;
use App\Support\RegistrationAgreements;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class OnboardingController
{
    public function index(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $user = Auth::user();
        $step = trim((string) ($_GET['step'] ?? ''));
        if ($step === '') {
            $step = 'choose';
        }

        $client = new BackendApiClient();
        $profileResult = $client->participantProfile();
        $profile = is_array($profileResult['data']['profile'] ?? null) ? $profileResult['data']['profile'] : [];

        $orgsResult = $client->participantOrganizations();
        $organizations = is_array($orgsResult['data']['organizations'] ?? null) ? $orgsResult['data']['organizations'] : [];
        $hasOrganizerTerms = !empty($profile['organizer_agreement_version']);
        $hasOrganizer = $organizations !== [];

        $onboardingMode = $_SESSION['onboarding_mode'] ?? null;
        if (!in_array($onboardingMode, ['shooter', 'organizer', 'both'], true)) {
            $onboardingMode = null;
        }

        $steps = [
            ['id' => 'choose', 'label' => 'Velg vei'],
            ['id' => 'participant', 'label' => 'Deltaker'],
        ];
        if ($onboardingMode === 'both') {
            $steps[] = ['id' => 'organizer_terms', 'label' => 'Avtale'];
            $steps[] = ['id' => 'organizer_create', 'label' => 'Opprett'];
        }
        $steps[] = ['id' => 'done', 'label' => 'Ferdig'];

        $stepIds = array_map(static fn (array $s): string => (string) $s['id'], $steps);
        if (!in_array($step, $stepIds, true)) {
            $step = $stepIds[0] ?? 'choose';
        }
        $currentStepIndex = array_search($step, $stepIds, true);
        $currentStepNumber = ($currentStepIndex === false ? 1 : ((int) $currentStepIndex + 1));
        $totalSteps = max(1, count($steps));
        $remaining = max(0, $totalSteps - $currentStepNumber);

        $organizerAgreement = RegistrationAgreements::organizerAgreement();

        if (!isset($_SESSION['onboarding_summary']) || !is_array($_SESSION['onboarding_summary'])) {
            $_SESSION['onboarding_summary'] = [];
        }
        $_SESSION['onboarding_summary']['mode'] = $onboardingMode;

        $participantCandidate = null;
        $createdParticipant = null;

        if ($step === 'participant') {
            $apiResult = $client->onboardingParticipant();
            if ($apiResult['ok'] && is_array($apiResult['data'])) {
                $existing = $apiResult['data']['existing_participant'] ?? null;
                $created = $apiResult['data']['created_participant'] ?? null;
                if (is_array($existing)) {
                    $participantCandidate = $existing;
                    $candidateId = (int) ($existing['id'] ?? 0);
                    $isMine = !empty($existing['is_mine']);
                    $summaryCreatedSame = (($_SESSION['onboarding_summary']['participant_status'] ?? null) === 'created')
                        && (int) ($_SESSION['onboarding_summary']['participant_id'] ?? 0) === $candidateId;
                    if (!$summaryCreatedSame) {
                        $_SESSION['onboarding_summary']['participant_status'] = $isMine ? 'found_mine' : 'found_other';
                        $_SESSION['onboarding_summary']['participant_id'] = $candidateId;
                        $_SESSION['onboarding_summary']['participant_name'] = (string) ($existing['name'] ?? '');
                        $_SESSION['onboarding_summary']['participant_jaktfelt_id'] = $existing['jaktfelt_id'] ?? null;
                    }
                } elseif (is_array($created)) {
                    $createdParticipant = $created;
                    $_SESSION['onboarding_summary']['participant_status'] = 'created';
                    $_SESSION['onboarding_summary']['participant_id'] = (int) ($created['id'] ?? 0);
                    $_SESSION['onboarding_summary']['participant_name'] = (string) ($created['name'] ?? '');
                    $_SESSION['onboarding_summary']['participant_jaktfelt_id'] = $created['jaktfelt_id'] ?? null;
                }
            }

            if ($participantCandidate === null && $createdParticipant === null) {
                [$participantCandidate, $createdParticipant] = $this->participantFromSessionSummary(
                    $_SESSION['onboarding_summary'] ?? [],
                );
            }

            if ($participantCandidate === null && $createdParticipant === null) {
                $shootersResult = $client->participantShooters();
                if ($shootersResult['ok'] && is_array($shootersResult['data']['participants'] ?? null)) {
                    $participants = $shootersResult['data']['participants'];
                    if ($participants !== []) {
                        $row = $participants[0];
                        $createdParticipant = [
                            'id' => (int) ($row['id'] ?? 0),
                            'name' => trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
                            'first_name' => (string) ($row['first_name'] ?? ''),
                            'last_name' => (string) ($row['last_name'] ?? ''),
                            'date_of_birth' => $row['date_of_birth'] ?? null,
                            'phone' => $row['phone'] ?? null,
                            'jaktfelt_id' => $row['jaktfelt_id'] ?? null,
                            'is_mine' => true,
                        ];
                    }
                }
            }
        }

        $tenant = TenantContext::current();
        $welcome = (string) ($tenant['display_name'] ?? 'Velkommen');

        return PublicView::render('onboarding-content', [
            'user' => $user,
            'step' => $step,
            'steps' => $steps,
            'current_step_number' => $currentStepNumber,
            'total_steps' => $totalSteps,
            'remaining_steps' => $remaining,
            'onboarding_mode' => $onboardingMode,
            'has_organizer_terms' => $hasOrganizerTerms,
            'has_organizer' => $hasOrganizer,
            'organizer_agreement' => $organizerAgreement,
            'participant_candidate' => $participantCandidate,
            'created_participant' => $createdParticipant,
            'summary' => $_SESSION['onboarding_summary'] ?? [],
            'onboarding_welcome' => $welcome,
        ], 'Velkommen');
    }

    public function choose(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $mode = trim((string) ($_POST['mode'] ?? ''));
        $_SESSION['onboarding_mode'] = $mode === 'both' ? 'both' : 'shooter';

        return Response::redirect('/onboarding?step=participant');
    }

    public function acceptOrganizerTerms(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $accept = !empty($_POST['accept']);
        if (!$accept) {
            Session::setFlash('error', 'Du må godta arrangøravtalen for å fortsette.');

            return Response::redirect('/onboarding?step=organizer_terms');
        }

        $agreement = RegistrationAgreements::organizerAgreement();
        $version = (string) ($agreement['version'] ?? '');
        $postedVer = trim((string) ($_POST['version'] ?? ''));
        $versionToSave = $version !== '' ? $version : $postedVer;
        if ($versionToSave === '') {
            Session::setFlash('error', 'Arrangøravtalen er ikke tilgjengelig akkurat nå.');

            return Response::redirect('/onboarding?step=organizer_terms');
        }

        $result = (new BackendApiClient())->updateParticipantProfile([
            'organizer_agreement_version' => $versionToSave,
        ]);
        if (!$result['ok']) {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke lagre arrangøravtale'));

            return Response::redirect('/onboarding?step=organizer_terms');
        }

        if (!isset($_SESSION['onboarding_summary']) || !is_array($_SESSION['onboarding_summary'])) {
            $_SESSION['onboarding_summary'] = [];
        }
        $_SESSION['onboarding_summary']['organizer_terms_accepted'] = true;
        Session::setFlash('success', 'Arrangøravtalen er godkjent.');

        return Response::redirect('/onboarding?step=organizer_create');
    }

    public function createOrganizer(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $profileResult = (new BackendApiClient())->participantProfile();
        $profile = is_array($profileResult['data']['profile'] ?? null) ? $profileResult['data']['profile'] : [];
        if (empty($profile['organizer_agreement_version'])) {
            Session::setFlash('error', 'Du må godta arrangøravtalen før du kan opprette arrangør.');

            return Response::redirect('/onboarding?step=organizer_terms');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Session::setFlash('error', 'Arrangørnavn er påkrevd.');

            return Response::redirect('/onboarding?step=organizer_create');
        }

        $tenant = TenantContext::current();
        $body = [
            'name' => $name,
            'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'districts' => trim((string) ($_POST['districts'] ?? '')),
            'host' => (string) ($tenant['host'] ?? ''),
        ];
        if (is_array($tenant['tenant'] ?? null)) {
            $body['tenant_id'] = (int) ($tenant['tenant']['id'] ?? 0);
        }

        $result = (new BackendApiClient())->createParticipantOrganization($body);
        if (!$result['ok']) {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke opprette arrangør'));

            return Response::redirect('/onboarding?step=organizer_create');
        }

        $orgName = (string) ($result['data']['organization']['name'] ?? $name);
        if (!isset($_SESSION['onboarding_summary']) || !is_array($_SESSION['onboarding_summary'])) {
            $_SESSION['onboarding_summary'] = [];
        }
        $_SESSION['onboarding_summary']['organizer_created_name'] = $orgName;
        Session::setFlash('success', sprintf('Arrangør "%s" er opprettet. Du er satt som eier (OWNER).', $orgName));

        return Response::redirect('/onboarding?step=done');
    }

    public function claimParticipant(int $id): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($returnTo === '' || !str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
            $onboardingMode = $_SESSION['onboarding_mode'] ?? 'shooter';
            $returnTo = $onboardingMode === 'both' ? '/onboarding?step=organizer_terms' : '/onboarding?step=done';
        }

        if (!empty($_POST['onboarding_claim'])) {
            if (!isset($_SESSION['onboarding_summary']) || !is_array($_SESSION['onboarding_summary'])) {
                $_SESSION['onboarding_summary'] = [];
            }
            $_SESSION['onboarding_summary']['claim_requested'] = true;
            $_SESSION['onboarding_summary']['claim_participant_id'] = $id;
        }

        $result = (new BackendApiClient())->claimParticipant($id);
        if ($result['ok']) {
            Session::setFlash('success', (string) ($result['data']['message'] ?? 'Forespørselen er sendt.'));
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke sende forespørsel'));
        }

        return Response::redirect($returnTo);
    }

    /**
     * @param array<string, mixed> $summary
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function participantFromSessionSummary(array $summary): array
    {
        $status = $summary['participant_status'] ?? null;
        if (!is_string($status) || $status === '') {
            return [null, null];
        }

        $id = (int) ($summary['participant_id'] ?? 0);
        $name = trim((string) ($summary['participant_name'] ?? ''));
        if ($id <= 0 && $name === '') {
            return [null, null];
        }

        $participant = [
            'id' => $id,
            'name' => $name,
            'date_of_birth' => null,
            'phone' => null,
            'jaktfelt_id' => $summary['participant_jaktfelt_id'] ?? null,
            'is_mine' => $status !== 'found_other',
        ];

        if ($status === 'created') {
            return [null, $participant];
        }

        return [$participant, null];
    }
}
