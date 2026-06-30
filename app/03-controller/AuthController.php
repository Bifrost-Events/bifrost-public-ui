<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\Auth;
use App\Support\PublicMenu;
use App\Support\PublicView;
use App\Support\RegistrationAgreements;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class AuthController
{
    public function loginForm(): array
    {
        return PublicView::render('auth-login-content', [
            'error' => '',
            'return_to' => (string) ($_GET['return_to'] ?? '/min-side/profil'),
        ], 'Logg inn');
    }

    public function loginFormFragment(): array
    {
        $html = PublicView::partialHtml('auth-login-fragment', [
            'error' => '',
            'return_to' => (string) ($_GET['return_to'] ?? '/'),
        ]);

        return Response::json(['mode' => 'form', 'html' => $html]);
    }

    public function registerForm(): array
    {
        if (Auth::check()) {
            return Response::redirect('/onboarding');
        }

        return PublicView::render('auth-register-content', [
            'error' => '',
            'return_to' => $this->resolveReturnTo((string) ($_GET['return_to'] ?? '')),
            'userAgreement' => RegistrationAgreements::userAgreement(),
        ], 'Registrer deg');
    }

    public function registerFormFragment(): array
    {
        if (Auth::check()) {
            return Response::json(['mode' => 'redirect', 'url' => '/onboarding']);
        }

        return Response::json(['mode' => 'redirect', 'url' => '/auth/register?return_to=' . rawurlencode($this->resolveReturnTo((string) ($_GET['return_to'] ?? '')))]);
    }

    public function actionsFragment(): array
    {
        if (!Session::startIfExists()) {
            $html = Response::partial('partials/_auth_actions', [
                'user' => null,
                'userName' => '',
                'userMenuItems' => PublicMenu::userItems(),
            ]);

            return Response::json([
                'loggedIn' => false,
                'html' => $html,
                'userName' => '',
            ]);
        }

        $user = Auth::user();
        $userName = '';
        if (is_array($user)) {
            $userName = trim((string) ($user['name'] ?? ''));
            if ($userName === '') {
                $userName = (string) ($user['email'] ?? 'Bruker');
            }
        }

        $html = Response::partial('partials/_auth_actions', [
            'user' => $user,
            'userName' => $userName,
            'userMenuItems' => PublicMenu::userItems(),
        ]);

        return Response::json([
            'loggedIn' => $user !== null,
            'html' => $html,
            'userName' => $userName,
        ]);
    }

    public function loginSubmit(): array
    {
        Session::startRequired();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $returnTo = $this->resolveReturnTo(trim((string) ($_POST['return_to'] ?? '/min-side/profil')));

        $result = (new BackendApiClient())->participantLogin($email, $password);
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($result['ok'] && is_array($result['data']['user'] ?? null)) {
            Session::setAuth($result['data']['user']);
            if ($isAjax) {
                return Response::json([
                    'success' => true,
                    'returnTo' => $returnTo,
                    'user' => $result['data']['user'],
                ]);
            }

            return Response::redirect($returnTo);
        }

        $error = (string) ($result['error'] ?? 'Innlogging feilet');
        if ($isAjax) {
            return Response::json(['success' => false, 'error' => $error], $result['status'] ?: 401);
        }

        return PublicView::render('auth-login-content', [
            'error' => $error,
            'return_to' => $returnTo,
        ], 'Logg inn');
    }

    public function registerSubmit(): array
    {
        Session::startRequired();
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $returnTo = $this->resolveReturnTo(trim((string) ($_POST['return_to'] ?? '/onboarding')));
        $userAgreement = RegistrationAgreements::userAgreement();
        $currentUserVer = $userAgreement['version'];

        if (Auth::check()) {
            if ($isAjax) {
                return Response::json(['success' => true, 'returnTo' => $returnTo]);
            }

            return Response::redirect($returnTo);
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $acceptUserAgreement = !empty($_POST['accept_user_agreement']);
        $userAgreementVersion = trim((string) ($_POST['user_agreement_version'] ?? ''));

        $err = function (string $message) use ($isAjax, $returnTo, $userAgreement): array {
            if ($isAjax) {
                return Response::json(['success' => false, 'error' => $message], 400);
            }

            return PublicView::render('auth-register-content', [
                'error' => $message,
                'return_to' => $returnTo,
                'userAgreement' => $userAgreement,
            ], 'Registrer deg', 400);
        };

        if (!$acceptUserAgreement || $userAgreementVersion !== $currentUserVer) {
            return $err('Du må godta brukeravtalen.');
        }
        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
            return $err('Fyll ut fornavn, etternavn, e-post og telefon.');
        }
        if (strlen($password) < 8) {
            return $err('Passordet må være minst 8 tegn.');
        }
        if ($password !== $passwordConfirm) {
            return $err('Passordene er ikke like.');
        }

        $tenant = TenantContext::current();
        $tenantId = is_array($tenant['tenant'] ?? null) ? (int) ($tenant['tenant']['id'] ?? 0) : 0;

        $result = (new BackendApiClient())->participantRegister(
            $email,
            $password,
            $firstName,
            $lastName,
            $phone,
            $tenantId > 0 ? $tenantId : null,
            $userAgreementVersion,
        );

        if (!($result['ok'] ?? false) || !is_array($result['data']['user'] ?? null)) {
            $error = (string) ($result['error'] ?? 'Registrering feilet');
            if (str_contains(strtolower($error), 'allerede')) {
                $error = 'E-postadressen er allerede registrert. Logg inn i stedet.';
            }

            return $err($error);
        }

        Session::setAuth($result['data']['user']);
        $this->applyOnboardingSummaryFromRegister($result['data']['onboarding'] ?? []);

        if ($isAjax) {
            return Response::json([
                'success' => true,
                'returnTo' => '/onboarding',
                'user' => $result['data']['user'],
            ]);
        }

        return Response::redirect('/onboarding');
    }

    /** @param array<string, mixed> $onboarding */
    private function applyOnboardingSummaryFromRegister(array $onboarding): void
    {
        if (!isset($_SESSION['onboarding_summary']) || !is_array($_SESSION['onboarding_summary'])) {
            $_SESSION['onboarding_summary'] = [];
        }

        $existing = $onboarding['existing_participant'] ?? null;
        if (is_array($existing)) {
            $_SESSION['onboarding_existing_participant'] = $existing;
            $isMine = !empty($existing['is_mine']);
            $_SESSION['onboarding_summary']['participant_status'] = $isMine ? 'found_mine' : 'found_other';
            $_SESSION['onboarding_summary']['participant_id'] = (int) ($existing['id'] ?? 0);
            $_SESSION['onboarding_summary']['participant_name'] = (string) ($existing['name'] ?? '');
            $_SESSION['onboarding_summary']['participant_jaktfelt_id'] = $existing['jaktfelt_id'] ?? null;

            return;
        }

        $created = $onboarding['created_participant'] ?? null;
        if (is_array($created)) {
            $_SESSION['onboarding_summary']['participant_status'] = 'created';
            $_SESSION['onboarding_summary']['participant_id'] = (int) ($created['id'] ?? 0);
            $_SESSION['onboarding_summary']['participant_name'] = (string) ($created['name'] ?? '');
            $_SESSION['onboarding_summary']['participant_jaktfelt_id'] = $created['jaktfelt_id'] ?? null;
        }
    }

    public function logout(): array
    {
        Session::startRequired();
        (new BackendApiClient())->logout();
        Session::clear();

        return Response::redirect('/');
    }

    private function resolveReturnTo(string $returnTo): string
    {
        $returnTo = trim($returnTo);
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            return '/onboarding';
        }

        return $returnTo;
    }
}
