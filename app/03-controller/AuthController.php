<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminAuthClient;
use App\Support\Auth;
use App\Support\PublicMenu;
use App\Support\PublicView;
use App\Support\RegistrationAgreements;
use App\Support\Response;
use App\Support\Session;

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
            return Response::redirect('/min-side/profil');
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
            return Response::json(['mode' => 'redirect', 'url' => '/min-side/profil']);
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

        $result = (new AdminAuthClient())->login($email, $password);
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($result['ok'] && is_array($result['data'] ?? null)) {
            $user = $result['data'];
            Session::setAuth($user);
            Session::setAuthSource('v3');
            if ((int) ($user['person_id'] ?? 0) > 0) {
                Session::setActingPersonId((int) $user['person_id']);
            }

            if ($isAjax) {
                return Response::json([
                    'success' => true,
                    'returnTo' => $returnTo,
                    'user' => $user,
                    'auth_source' => 'v3',
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
        $returnTo = $this->resolveReturnTo(trim((string) ($_POST['return_to'] ?? '/min-side/profil')));
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

        $result = (new AdminAuthClient())->register([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
        ]);

        if (!($result['ok'] ?? false) || !is_array($result['data'] ?? null)) {
            $error = (string) ($result['error'] ?? 'Registrering feilet');
            if (str_contains(strtolower($error), 'allerede') || str_contains(strtolower($error), 'already')) {
                $error = 'E-postadressen er allerede registrert. Logg inn i stedet.';
            }

            return $err($error);
        }

        $user = $result['data'];
        Session::setAuth($user);
        Session::setAuthSource('v3');
        if ((int) ($user['person_id'] ?? 0) > 0) {
            Session::setActingPersonId((int) $user['person_id']);
        }

        Session::setFlash('info', 'Fullfør profilen din.');

        $profileUrl = '/min-side/profil';
        if ($isAjax) {
            return Response::json([
                'success' => true,
                'returnTo' => $profileUrl,
                'user' => $user,
                'auth_source' => 'v3',
            ]);
        }

        return Response::redirect($profileUrl);
    }

    public function logout(): array
    {
        Session::startRequired();
        (new AdminAuthClient())->logout();
        Session::clear();

        return Response::redirect('/');
    }

    private function resolveReturnTo(string $returnTo): string
    {
        $returnTo = trim($returnTo);
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            return '/min-side/profil';
        }

        return $returnTo;
    }
}
