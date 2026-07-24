<?php

declare(strict_types=1);

/**
 * Public-ui V3-only tests (context, calendar, series, events, results, standings, auth).
 *
 * Usage:
 *   php scripts/test_public_portal_v3.php
 *   php scripts/test_public_portal_v3.php --offline
 */

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
require $basePath . '/app/06-support/bootstrap.php';

use App\Service\AdminAuthClient;
use App\Service\EventUrlResolver;
use App\Service\EventsApiClient;
use App\Service\PersonPickerService;
use App\Service\PublicCalendarService;
use App\Service\PublicCatalogClient;
use App\Service\V3EventMapper;
use App\Support\CupConfigLoader;
use App\Support\PublicPortalContext;

$offline = in_array('--offline', $argv, true);
$failures = 0;
$passes = 0;

$assert = static function (bool $ok, string $label) use (&$failures, &$passes): void {
    if ($ok) {
        echo "PASS  $label\n";
        $passes++;
    } else {
        echo "FAIL  $label\n";
        $failures++;
    }
};

echo "=== Offline: mapper + URL resolver ===\n";

$mapper = new V3EventMapper();
$mapped = $mapper->mapCalendar([
    'application' => ['id' => 1, 'key' => 'jaktfeltcup', 'name' => 'Jaktfeltcup'],
    'space' => ['id' => 2, 'name' => 'Cup'],
    'season' => ['id' => 3, 'name' => '2027', 'year' => 2027],
    'labels' => [
        'event' => ['singular' => 'Stevne', 'plural' => 'Stevner'],
        'series' => ['singular' => 'Sesong', 'plural' => 'Sesonger'],
    ],
    'competitions' => [[
        'event_id' => 10,
        'name' => 'Teststevne',
        'starts_at' => '2027-06-01 10:00:00',
        'location' => 'Grong',
        'organizer_name' => 'Org',
        'status' => 'active',
        'legacy' => ['table' => 'jaktfelt_competitions', 'id' => '42'],
    ]],
]);
$assert(($mapped['competitions'][0]['id'] ?? 0) === 10, 'Mapper beholder event_id som id');
$assert(($mapped['labels']['event']['singular'] ?? '') === 'Stevne', 'Mapper labels.event.singular');

$urls = new EventUrlResolver();
$assert($urls->v3EventUrl($mapped['competitions'][0]) === '/arrangementer/10', 'Kalenderlenke er V3 /arrangementer/{id}');
$assert($urls->v3EventUrl(['id' => 7]) === '/arrangementer/7', 'V3 event-URL');
$assert($urls->v3SeriesUrl(['id' => 3]) === '/serier/3', 'V3 series-URL');
$assert(!method_exists($urls, 'v2SignupUrl'), 'Ingen V2 signup-URL i EventUrlResolver');
$assert(
    $urls->registrationFlow(['legacy' => ['table' => 'jaktfelt_competitions', 'id' => '42']]) === 'v3',
    'Uten jaktfelt-modul → v3 (ikke v2_legacy)'
);
$assert(
    $urls->registrationFlow(['modules' => ['jaktfelt' => true]]) === 'jaktfelt_v3',
    'registrationFlow jaktfelt_v3 ved modul'
);

echo "\n=== Offline: branding per host ===\n";
$brandHosts = [
    'jaktfeltcup.local' => 'nasjonal-15m-jaktfeltcup.json',
    'namdal.jaktfeltkarusell.local' => 'namdal-jaktfeltkarusell.json',
    'slatlemcup.local' => 'slatlem-cup.json',
];
foreach ($brandHosts as $host => $expectedFile) {
    PublicPortalContext::resetCache();
    $ref = new ReflectionClass(CupConfigLoader::class);
    $prop = $ref->getProperty('cached');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
    $_SERVER['HTTP_HOST'] = $host;
    $cup = CupConfigLoader::current();
    $assert(($cup['_meta']['config_file'] ?? '') === $expectedFile, "Branding $host → $expectedFile");
}

$_SERVER['HTTP_HOST'] = 'jaktfeltcup.local';
PublicPortalContext::resetCache();
$ref = new ReflectionClass(CupConfigLoader::class);
$prop = $ref->getProperty('cached');
$prop->setAccessible(true);
$prop->setValue(null, null);
$nasjonal = CupConfigLoader::current();
$assert(CupConfigLoader::organizerPortalUrl($nasjonal) !== '', 'Nasjonal cup har organizer_portal_url');
$menuIds = array_map(static fn ($i) => (string) ($i['id'] ?? ''), CupConfigLoader::menuItems($nasjonal));
$assert(in_array('nav_how_to', $menuIds, true), 'Nasjonal meny inkluderer Hvordan delta');
$assert(in_array('nav_sponsor', $menuIds, true), 'Nasjonal meny inkluderer Sponsor');

$assert(is_file($basePath . '/app/02-view/series-show-content.php'), 'Series view finnes');
$assert(is_file($basePath . '/app/02-view/event-show-content.php'), 'Event view finnes');
$assert(!class_exists(\App\Service\BackendApiClient::class, false), 'BackendApiClient er fjernet');
$assert(!is_file($basePath . '/app/04-services/BackendApiClient.php'), 'BackendApiClient.php slettet');
$assert(!is_file($basePath . '/app/06-support/TenantContext.php'), 'TenantContext.php slettet');

$routes = (string) file_get_contents($basePath . '/routes/web.php');
$assert(str_contains($routes, "/serier/{seriesId}"), 'Route /serier/{seriesId}');
$assert(str_contains($routes, "/arrangementer/{eventId}"), 'Route /arrangementer/{eventId}');
$assert(str_contains($routes, '/hvordan-delta'), 'Route /hvordan-delta');
$assert(str_contains($routes, '/arrangor'), 'Route /arrangor');

echo "\n=== Offline: V3 auth / personvelger ===\n";
$assert(class_exists(AdminAuthClient::class), 'AdminAuthClient exists');
$assert(class_exists(PersonPickerService::class), 'PersonPickerService exists');
$assert(method_exists(AdminAuthClient::class, 'login'), 'AdminAuthClient::login');
$assert(method_exists(AdminAuthClient::class, 'me'), 'AdminAuthClient::me');
$assert(method_exists(AdminAuthClient::class, 'people'), 'AdminAuthClient::people');
$assert(file_exists($basePath . '/app/02-view/profile-content.php'), 'Profile view exists');
$assert(file_exists($basePath . '/app/02-view/partials/_person_picker.php'), 'Person picker partial exists');
$assert(
    str_contains($routes, '/min-side/personvelger')
    && str_contains($routes, '/min-side/personer'),
    'V3 profile/person routes registered'
);
$authFile = file_get_contents($basePath . '/app/06-support/Auth.php');
$assert(
    is_string($authFile) && str_contains($authFile, 'AdminAuthClient') && !str_contains($authFile, 'BackendApiClient'),
    'Auth is V3-only (AdminAuthClient)'
);
$loginCtrl = file_get_contents($basePath . '/app/03-controller/AuthController.php');
$assert(
    is_string($loginCtrl) && str_contains($loginCtrl, 'AdminAuthClient') && !str_contains($loginCtrl, 'participantLogin'),
    'Login is V3-only'
);
$publicView = file_get_contents($basePath . '/app/06-support/PublicView.php');
$assert(
    is_string($publicView) && !str_contains($publicView, 'TenantContext'),
    'PublicView bruker ikke TenantContext'
);

echo "\n=== Offline: V3 registration ===\n";
$assert(method_exists(EventUrlResolver::class, 'registrationFlow'), 'EventUrlResolver::registrationFlow');
$assert(method_exists(EventsApiClient::class, 'createEventRegistration'), 'EventsApiClient::createEventRegistration');
$assert(method_exists(EventsApiClient::class, 'createJaktfeltRegistration'), 'EventsApiClient::createJaktfeltRegistration');
$assert(method_exists(EventsApiClient::class, 'myRegistrations'), 'EventsApiClient::myRegistrations');
$assert(
    str_contains($routes, '/arrangementer/{eventId}/pamelding')
    && str_contains($routes, '/min-side/pameldinger/avmeld'),
    'V3 registration routes registered'
);
$eventShow = (string) file_get_contents($basePath . '/app/02-view/event-show-content.php');
$assert(str_contains($eventShow, 'registration-section'), 'Event show has registration section');
$assert(!str_contains($eventShow, 'data-hybrid="v2"'), 'Event show uten V2-hybrid UI');

$calendarFile = (string) file_get_contents($basePath . '/app/03-controller/CalendarController.php');
$assert(
    str_contains($calendarFile, 'PublicCalendarService')
    && str_contains($calendarFile, 'findEventIdByLegacyCompetitionId')
    && !str_contains($calendarFile, 'competitionSignup'),
    'CalendarController redirects legacy IDs, no V2 signup'
);

if ($offline) {
    echo "\n(offline mode — skipping live API tests)\n";
    echo "\nResult: $passes passed, $failures failed\n";
    exit($failures > 0 ? 1 : 0);
}

echo "\n=== Live: Events API context per domain ===\n";

$hosts = [
    'jaktfeltcup.local' => 'jaktfeltcup',
    'namdal.jaktfeltkarusell.local' => 'jaktfeltkarusell-namdal',
    'slatlemcup.local' => 'slatlem',
];

$client = new EventsApiClient();
$catalog = new PublicCatalogClient();

foreach ($hosts as $host => $expectedKey) {
    PublicPortalContext::resetCache();
    $_SERVER['HTTP_HOST'] = $host;

    $ctxResponse = $client->publicContext($host);
    $assert(($ctxResponse['ok'] ?? false) === true, "Context API OK for $host");
    $key = (string) ($ctxResponse['data']['application']['key'] ?? '');
    $assert($key === $expectedKey, "Context $host → application_key=$expectedKey (got $key)");

    $portal = PublicPortalContext::current();
    $assert(($portal['resolved'] ?? false) === true, "PublicPortalContext resolved for $host");
    $assert(($portal['application_key'] ?? null) === $expectedKey, "PublicPortalContext key for $host");

    $calendar = (new PublicCalendarService())->forHost($host);
    $assert(($calendar['ok'] ?? false) === true, "V3 calendar OK for $host");
    $assert(($calendar['source'] ?? '') === 'v3', "Calendar source=v3 for $host");
    $calKey = (string) (($calendar['application']['key'] ?? ''));
    $assert($calKey === $expectedKey, "Calendar scoped to $expectedKey for $host");

    foreach ($calendar['competitions'] as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        $assert(
            is_string($comp['detail_url'] ?? null) && str_starts_with((string) $comp['detail_url'], '/arrangementer/'),
            "Kalender event linker til V3 for $host"
        );
        break;
    }
}

echo "\n=== Live: series + event detail (skytecuper, valgfri demo) ===\n";
$demoHost = 'skytecuper.bifrost.local';
$demoCal = (new PublicCalendarService())->forHost($demoHost);
if (!($demoCal['ok'] ?? false)) {
    echo "SKIP  skytecuper.bifrost.local ikke seedet i dette miljøet\n";
} else {
    $assert(true, 'Demo calendar OK');
    $spaceId = (int) ($demoCal['space']['id'] ?? 0);
    $seasonId = (int) ($demoCal['season']['id'] ?? 0);
    $eventId = (int) (($demoCal['competitions'][0]['event_id'] ?? $demoCal['competitions'][0]['id'] ?? 0));
    $assert($spaceId > 0 && $seasonId > 0 && $eventId > 0, 'Demo space/season/event ids');

    $seriesResult = $catalog->series($demoHost, $seasonId);
    $assert(($seriesResult['ok'] ?? false) === true, 'Series detail OK');

    $roundId = 0;
    foreach ($seriesResult['data']['children'] ?? [] as $child) {
        if (is_array($child) && (int) ($child['id'] ?? 0) > 0) {
            $roundId = (int) $child['id'];
            break;
        }
    }

    $eventResult = $catalog->event($demoHost, $eventId);
    $assert(($eventResult['ok'] ?? false) === true, 'Event detail OK');
    $assert(!isset($eventResult['data']['v2_links']), 'Event payload uten v2_links');
    $assert(str_starts_with((string) ($eventResult['data']['urls']['self'] ?? ''), '/arrangementer/'), 'Event self URL er V3');

    $cross = $catalog->event('jaktfeltcup.local', $eventId);
    $assert(($cross['ok'] ?? true) === false, 'Event fra annen application → ikke tilgjengelig');
    $assert((int) ($cross['status'] ?? 0) === 404, 'Cross-app event → 404');

    echo "\n=== Live: V3 results + standings (skytecuper) ===\n";
    $results = $catalog->eventResults($demoHost, $eventId);
    $assert(($results['ok'] ?? false) === true, 'Event results OK');
    $assert(($results['data']['has_results'] ?? false) === true, 'Event has_results true');

    $enriched = $catalog->event($demoHost, $eventId);
    $assert(($enriched['data']['has_v3_results'] ?? false) === true, 'Event enrich has_v3_results');

    $standings = $catalog->seriesStandings($demoHost, $seasonId);
    $assert(($standings['ok'] ?? false) === true, 'Top series standings OK');

    if ($roundId > 0) {
        $roundStandings = $catalog->seriesStandings($demoHost, $roundId);
        $assert(($roundStandings['ok'] ?? false) === true, 'Underserie standings resolves (not 404)');
    }
}

echo "\n=== Live: unknown domain ===\n";
$unknown = $client->publicContext('unknown-host.example.invalid');
$assert(($unknown['ok'] ?? true) === false, 'Ukjent domene → ikke ok');
$assert((int) ($unknown['status'] ?? 0) === 404, 'Ukjent domene → 404');

$badCalendar = (new PublicCalendarService())->forHost('unknown-host.example.invalid');
$assert(($badCalendar['ok'] ?? true) === false, 'Kalender ukjent host → feil');

echo "\nResult: $passes passed, $failures failed\n";
exit($failures > 0 ? 1 : 0);
