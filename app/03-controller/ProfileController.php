<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminAuthClient;
use App\Service\PersonPickerService;
use App\Support\Auth;
use App\Support\CupConfigLoader;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;

final class ProfileController
{
    public function show(): array
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect('/auth/login?return_to=' . rawurlencode('/min-side/profil'));
        }

        $picker = (new PersonPickerService())->forCurrentUser();
        $me = (new AdminAuthClient())->me();
        $meData = ($me['ok'] ?? false) && is_array($me['data'] ?? null) ? $me['data'] : $user;

        return PublicView::render('profile-content', [
            'user' => $meData,
            'picker' => $picker,
            'auth_source' => Auth::authSource(),
            'flash' => Session::pullFlash(),
            'arrangor_portal_url' => $this->resolveArrangorPortalUrl(),
        ], 'Min side');
    }

    public function selectPerson(): array
    {
        if (!Auth::check()) {
            return Response::redirect('/auth/login?return_to=' . rawurlencode('/min-side/profil'));
        }

        $personId = (int) ($_POST['person_id'] ?? 0);
        $ok = (new PersonPickerService())->selectPerson($personId);
        if (!$ok) {
            Session::setFlash('error', 'Ugyldig personvalg.');
        } else {
            Session::setFlash('info', 'Handler nå på vegne av valgt person.');
        }

        $returnTo = trim((string) ($_POST['return_to'] ?? '/min-side/profil'));
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            $returnTo = '/min-side/profil';
        }

        return Response::redirect($returnTo);
    }

    public function createPerson(): array
    {
        if (!Auth::check()) {
            return Response::redirect('/auth/login?return_to=' . rawurlencode('/min-side/profil'));
        }

        $confirm = !empty($_POST['confirm']);
        $result = (new AdminAuthClient())->createPerson([
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'birth_date' => trim((string) ($_POST['birth_date'] ?? '')) ?: null,
            'email' => trim((string) ($_POST['email'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'relationship_type' => trim((string) ($_POST['relationship_type'] ?? 'guardian')),
            'confirm' => $confirm,
        ]);

        if ($result['ok'] ?? false) {
            $personId = (int) ($result['data']['person_id'] ?? 0);
            if ($personId > 0) {
                Session::setActingPersonId($personId);
            }
            Session::setFlash('info', 'Person opprettet og lagt til som representert.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke opprette person.'));
        }

        return Response::redirect('/min-side/profil');
    }

    public function updatePerson(): array
    {
        if (!Auth::check()) {
            return Response::redirect('/auth/login?return_to=' . rawurlencode('/min-side/profil'));
        }

        $personId = (int) ($_POST['person_id'] ?? 0);
        $body = [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'birth_date' => trim((string) ($_POST['birth_date'] ?? '')) ?: null,
            'email' => trim((string) ($_POST['email'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'display_name' => trim((string) ($_POST['display_name'] ?? '')) ?: null,
        ];

        $client = new AdminAuthClient();
        $selfPersonId = (int) (Auth::user()['person_id'] ?? 0);
        if ($personId > 0 && $personId !== $selfPersonId) {
            $result = $client->updatePeoplePerson($personId, $body);
        } else {
            $result = $client->updatePerson($body);
        }

        if ($result['ok'] ?? false) {
            Session::setFlash('info', 'Profilen er oppdatert.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke oppdatere profil.'));
        }

        return Response::redirect('/min-side/profil');
    }

    public function peopleIndex(): array
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect('/auth/login?return_to=' . rawurlencode('/min-side/personer'));
        }

        $picker = (new PersonPickerService())->forCurrentUser();

        return PublicView::render('profile-content', [
            'user' => $user,
            'picker' => $picker,
            'auth_source' => Auth::authSource(),
            'flash' => Session::pullFlash(),
            'arrangor_portal_url' => $this->resolveArrangorPortalUrl(),
            'focus_people' => true,
        ], 'Representerte personer');
    }

    private function resolveArrangorPortalUrl(): string
    {
        $url = CupConfigLoader::organizerPortalUrl();

        return $url !== '' ? $url : '#';
    }
}
