<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\CalendarController;
use App\Controller\EventController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\OnboardingController;
use App\Controller\ParticipantController;
use App\Controller\PlaceholderController;
use App\Controller\ProfileController;
use App\Controller\ResultsController;
use App\Controller\SeriesController;
use App\Controller\SignupController;
use App\Controller\StandingsController;
use App\Support\Auth;
use App\Support\PublicView;
use App\Support\Router;

return function (array $app): Router {
    $router = new Router();
    $placeholder = new PlaceholderController();
    $auth = new AuthController();

    $requireLogin = static function (callable $handler): callable {
        return static function (...$args) use ($handler) {
            if ($redirect = Auth::requireLogin()) {
                return $redirect;
            }

            return $handler(...$args);
        };
    };

    $router->get('/', fn () => (new HomeController())());
    $router->get('/health', fn () => (new HealthController())());

    $router->get('/calendar', fn () => (new CalendarController())->index());
    $router->get('/calendar/{id}', fn (int $id) => (new CalendarController())->show($id));
    $router->post('/calendar/{id}/register', $requireLogin(fn (int $id) => (new CalendarController())->register($id)));
    $router->post('/calendar/{id}/unregister', $requireLogin(fn (int $id) => (new CalendarController())->unregister($id)));

    $router->get('/serier/{seriesId}', fn (int $seriesId) => (new SeriesController())->show($seriesId));
    $router->get('/serier/{seriesId}/sammenlagt', fn (int $seriesId) => (new SeriesController())->standings($seriesId));
    $router->get('/arrangementer/{eventId}', fn (int $eventId) => (new EventController())->show($eventId));
    $router->get('/arrangementer/{eventId}/resultater', fn (int $eventId) => (new EventController())->results($eventId));
    $router->post('/arrangementer/{eventId}/pamelding', $requireLogin(fn (int $eventId) => (new EventController())->register($eventId)));
    $router->post('/arrangementer/{eventId}/avmelding', $requireLogin(fn (int $eventId) => (new EventController())->cancelRegistration($eventId)));

    $router->get('/results', fn () => (new ResultsController())->index());
    $router->get('/results/{id}', fn (int $id) => (new ResultsController())->show($id));
    $router->get('/sammenlagt', fn () => (new StandingsController())->index());
    $router->get('/om', fn () => $placeholder->about());
    $router->get('/sponsor', fn () => $placeholder->sponsor());
    $router->get('/arrangor', fn () => $placeholder->organizerInfo());
    $router->get('/hvordan-delta', fn () => $placeholder->howToParticipate());
    $router->get('/finaler', fn () => $placeholder->finals());
    $router->get('/arkiv', fn () => $placeholder->archive());

    $router->get('/auth/login', fn () => $auth->loginForm());
    $router->get('/auth/register', fn () => $auth->registerForm());
    $router->get('/auth/login-form', fn () => $auth->loginFormFragment());
    $router->get('/auth/register-form', fn () => $auth->registerFormFragment());
    $router->get('/auth/actions-fragment', fn () => $auth->actionsFragment());
    $router->post('/auth/login', fn () => $auth->loginSubmit());
    $router->post('/auth/register', fn () => $auth->registerSubmit());
    $router->get('/auth/logout', fn () => $auth->logout());

    $onboarding = new OnboardingController();
    $router->get('/onboarding', $requireLogin(fn () => $onboarding->index()));

    $router->get('/min-side/profil', $requireLogin(fn () => (new ProfileController())->show()));
    $router->post('/min-side/profil', $requireLogin(fn () => (new ProfileController())->updatePerson()));
    $router->post('/min-side/personvelger', $requireLogin(fn () => (new ProfileController())->selectPerson()));
    $router->get('/min-side/personer', $requireLogin(fn () => (new ProfileController())->peopleIndex()));
    $router->post('/min-side/personer', $requireLogin(fn () => (new ProfileController())->createPerson()));
    $router->get('/min-side/deltakere', $requireLogin(fn () => (new ParticipantController())->index()));
    $router->post('/min-side/deltakere', $requireLogin(fn () => (new ParticipantController())->store()));
    $router->post('/min-side/deltakere/{id}', $requireLogin(fn (int $id) => (new ParticipantController())->update($id)));
    $router->get('/min-side/pameldinger', $requireLogin(fn () => (new SignupController())->index()));
    $router->post('/min-side/pameldinger/avmeld', $requireLogin(fn () => (new SignupController())->cancel()));

    return $router;
};
