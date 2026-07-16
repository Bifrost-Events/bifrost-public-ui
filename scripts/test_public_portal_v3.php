<?php

declare(strict_types=1);

/**
 * Fase 1–4 tests for public-ui V3 transition
 * (context, calendar, series, events, results, standings).
 *
 * Usage:
 *   php scripts/test_public_portal_v3.php
 *   php scripts/test_public_portal_v3.php --offline
 */

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
require $basePath . '/app/06-support/bootstrap.php';

use App\Service\AdminAuthClient;
use App\Service\BackendApiClient;
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
$assert($urls->v3EventUrl(['id' => 7]) === '/arrangementer/7', 'V3 event-URL uten legacy');
$assert($urls->v3SeriesUrl(['id' => 3]) === '/serier/3', 'V3 series-URL');
$assert($urls->v2SignupUrl($mapped['competitions'][0]) === '/calendar/42', 'Sikker V2-påmeldingslenke ved jaktfelt legacy_id');
$assert($urls->v2ResultsUrl($mapped['competitions'][0]) === '/results/42', 'Sikker V2-resultatlenke');
$assert($urls->v2SignupUrl(['legacy' => ['table' => 'demo', 'id' => 'grong']]) === null, 'Ingen usikker V2-lenke');
$assert($urls->v2SignupUrl(['name' => 'Foo', 'id' => 1]) === null, 'Ingen V2-lenke kun basert på id/navn');

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

$assert(is_file($basePath . '/app/02-view/series-show-content.php'), 'Series view finnes');
$assert(is_file($basePath . '/app/02-view/event-show-content.php'), 'Event view finnes');
$routes = (string) file_get_contents($basePath . '/routes/web.php');
$assert(str_contains($routes, "/serier/{seriesId}"), 'Route /serier/{seriesId}');
$assert(str_contains($routes, "/arrangementer/{eventId}"), 'Route /arrangementer/{eventId}');

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
    is_string($authFile) && str_contains($authFile, 'AdminAuthClient') && str_contains($authFile, 'auth_source'),
    'Auth resolves via V3 AdminAuthClient'
);
$loginCtrl = file_get_contents($basePath . '/app/03-controller/AuthController.php');
$assert(
    is_string($loginCtrl) && str_contains($loginCtrl, 'AdminAuthClient') && str_contains($loginCtrl, 'participantLogin'),
    'Login uses V3 primary + best-effort V2 hybrid'
);

echo "\n=== Offline: V3 registration ===\n";
$assert(method_exists(EventUrlResolver::class, 'registrationFlow'), 'EventUrlResolver::registrationFlow');
$assert(
    (new EventUrlResolver())->registrationFlow(['legacy' => ['table' => 'jaktfelt_competitions', 'id' => '42']]) === 'v2_legacy',
    'registrationFlow v2_legacy for jaktfelt'
);
$assert(
    (new EventUrlResolver())->registrationFlow(['legacy' => ['table' => 'demo', 'id' => 'x']]) === 'v3',
    'registrationFlow v3 for non-jaktfelt'
);
$assert(method_exists(EventsApiClient::class, 'createEventRegistration'), 'EventsApiClient::createEventRegistration');
$assert(method_exists(EventsApiClient::class, 'eventRegistrationsMe'), 'EventsApiClient::eventRegistrationsMe');
$assert(method_exists(EventsApiClient::class, 'myRegistrations'), 'EventsApiClient::myRegistrations');
$assert(
    str_contains($routes, '/arrangementer/{eventId}/pamelding')
    && str_contains($routes, '/min-side/pameldinger/avmeld'),
    'V3 registration routes registered'
);
$assert(
    is_string(file_get_contents($basePath . '/app/02-view/event-show-content.php'))
    && str_contains((string) file_get_contents($basePath . '/app/02-view/event-show-content.php'), 'registration-section'),
    'Event show has registration section'
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
$demoEventId = null;
$demoSeriesId = null;

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
    $eventLabel = (string) ($portal['labels']['event']['singular'] ?? '');
    $assert($eventLabel !== '' && $eventLabel !== 'event', "Terminologi event-label for $host ($eventLabel)");

    $calendar = (new PublicCalendarService())->forHost($host);
    $assert(($calendar['ok'] ?? false) === true, "V3 calendar OK for $host");
    $assert(($calendar['source'] ?? '') === 'v3', "Calendar source=v3 for $host");
    $calKey = (string) (($calendar['application']['key'] ?? ''));
    $assert($calKey === $expectedKey, "Calendar scoped to $expectedKey for $host");

    if (is_array($calendar['season'] ?? null)) {
        $seasonUrl = $calendar['season']['detail_url'] ?? null;
        $assert(is_string($seasonUrl) && str_starts_with($seasonUrl, '/serier/'), "Season linker til V3 serie for $host");
    }

    foreach ($calendar['competitions'] as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        $assert(
            is_string($comp['detail_url'] ?? null) && str_starts_with((string) $comp['detail_url'], '/arrangementer/'),
            "Kalender event linker til V3 for $host"
        );
        if ($demoEventId === null) {
            $demoEventId = (int) ($comp['event_id'] ?? $comp['id'] ?? 0);
            $demoSeriesId = (int) (($calendar['season']['id'] ?? 0));
        }
        break;
    }
}

echo "\n=== Live: series + event detail (skytecuper) ===\n";
$demoHost = 'skytecuper.bifrost.local';
$demoCal = (new PublicCalendarService())->forHost($demoHost);
$assert(($demoCal['ok'] ?? false) === true, 'Demo calendar OK');
$spaceId = (int) ($demoCal['space']['id'] ?? 0);
$seasonId = (int) ($demoCal['season']['id'] ?? 0);
$eventId = (int) (($demoCal['competitions'][0]['event_id'] ?? $demoCal['competitions'][0]['id'] ?? 0));
$assert($spaceId > 0 && $seasonId > 0 && $eventId > 0, 'Demo space/season/event ids');

$seriesResult = $catalog->series($demoHost, $seasonId);
$assert(($seriesResult['ok'] ?? false) === true, 'Series detail OK');
$assert(is_array($seriesResult['data']['children'] ?? null), 'Series has children array');
$assert(is_array($seriesResult['data']['breadcrumb'] ?? null), 'Series has breadcrumb');
$labels = $seriesResult['data']['labels'] ?? [];
$assert(($labels['event']['singular'] ?? '') === 'Stevne', 'Skytecuper labels: Stevne');

$roundId = 0;
foreach ($seriesResult['data']['children'] ?? [] as $child) {
    if (is_array($child) && (int) ($child['id'] ?? 0) > 0) {
        $roundId = (int) $child['id'];
        break;
    }
}
if ($roundId > 0) {
    $round = $catalog->series($demoHost, $roundId);
    $assert(($round['ok'] ?? false) === true, 'Round/underserie detail OK');
    $assert((int) ($round['data']['parent']['id'] ?? 0) === $seasonId, 'Round parent = season');
    $assert(is_array($round['data']['events'] ?? null), 'Round has events list');
}

$eventResult = $catalog->event($demoHost, $eventId);
$assert(($eventResult['ok'] ?? false) === true, 'Event detail OK');
$assert(($eventResult['data']['breadcrumb'] ?? []) !== [], 'Event breadcrumb ikke tom');
$v2 = $eventResult['data']['v2_links'] ?? [];
$assert(($v2['signup'] ?? null) === null, 'Demo-event uten jaktfelt-legacy: ingen V2-signup');
$assert(str_starts_with((string) ($eventResult['data']['urls']['self'] ?? ''), '/arrangementer/'), 'Event self URL er V3');

$cross = $catalog->event('jaktfeltcup.local', $eventId);
$assert(($cross['ok'] ?? true) === false, 'Event fra annen application → ikke tilgjengelig');
$assert((int) ($cross['status'] ?? 0) === 404, 'Cross-app event → 404');

$missing = $catalog->event($demoHost, 999999999);
$assert(($missing['ok'] ?? true) === false, 'Ukjent event → feil');

echo "\n=== Live: V3 results + standings (skytecuper) ===\n";
$results = $catalog->eventResults($demoHost, $eventId);
$assert(($results['ok'] ?? false) === true, 'Event results OK');
$assert(($results['data']['has_results'] ?? false) === true, 'Event has_results true');
$byClass = $results['data']['results_by_class'] ?? [];
$assert(is_array($byClass) && count($byClass) >= 2, 'Results grouped by at least 2 classes');
$classKeys = array_map(static fn ($g) => (string) ($g['key'] ?? ''), $byClass);
$assert(in_array('klasse-a', $classKeys, true) && in_array('klasse-b', $classKeys, true), 'Class keys klasse-a/b present');
$firstGroup = is_array($byClass[0] ?? null) ? $byClass[0] : [];
$entries = is_array($firstGroup['rows'] ?? null) ? $firstGroup['rows'] : [];
$assert($entries !== [], 'First class has rows');
$assert(isset($entries[0]['placement'], $entries[0]['display_name'], $entries[0]['total_score']), 'Entry has placement/name/score');
$assert(str_starts_with((string) ($results['data']['urls']['self'] ?? ''), '/arrangementer/'), 'Results self URL is V3');
$assert(!str_contains(json_encode($results['data'], JSON_THROW_ON_ERROR), 'legacy_table'), 'Results payload hides legacy import fields');

$crossResults = $catalog->eventResults('jaktfeltcup.local', $eventId);
$assert(($crossResults['ok'] ?? true) === false, 'Cross-app results → not ok');
$assert((int) ($crossResults['status'] ?? 0) === 404, 'Cross-app results → 404');

$enriched = $catalog->event($demoHost, $eventId);
$assert(($enriched['data']['has_v3_results'] ?? false) === true, 'Event enrich has_v3_results');
$assert(($enriched['data']['v2_links']['results'] ?? null) === null, 'V2 results link hidden when V3 results exist');

$standings = $catalog->seriesStandings($demoHost, $seasonId);
$assert(($standings['ok'] ?? false) === true, 'Top series standings OK');
$assert(($standings['data']['resolved_from_series_id'] ?? null) === null, 'Root series not redirected');
$standingsByClass = $standings['data']['class_groups'] ?? [];
$assert(is_array($standingsByClass) && $standingsByClass !== [], 'Standings has class groups');
$mode = (string) ($standings['data']['standings_mode'] ?? '');
$assert(in_array($mode, ['total_score', 'placement_points'], true), 'Standings mode documented');
$row = $standingsByClass[0]['rows'][0] ?? null;
$assert(is_array($row), 'Standings has at least one row');
$assert(isset($row['placement'], $row['display_name'], $row['total_score'], $row['events_count']), 'Standing row fields');
$countBest = (int) ($standings['data']['count_best'] ?? 0);
$assert($countBest === 1, 'Demo count_best=1');

if ($roundId > 0) {
    $roundStandings = $catalog->seriesStandings($demoHost, $roundId);
    $assert(($roundStandings['ok'] ?? false) === true, 'Underserie standings resolves (not 404)');
    $assert((int) ($roundStandings['data']['resolved_from_series_id'] ?? 0) === $roundId, 'Underserie → root series standings');
    $assert((int) ($roundStandings['data']['series']['id'] ?? 0) === $seasonId, 'Resolved standings series = toppserie');
}

$assert(method_exists(EventUrlResolver::class, 'v3EventResultsUrl'), 'EventUrlResolver::v3EventResultsUrl');
$assert(method_exists(EventUrlResolver::class, 'v3StandingsUrl'), 'EventUrlResolver::v3StandingsUrl');
$assert(
    file_exists($basePath . '/app/02-view/event-results-content.php')
    && file_exists($basePath . '/app/02-view/series-standings-content.php'),
    'Results + standings views exist'
);
$routes = file_get_contents($basePath . '/routes/web.php');
$assert(
    is_string($routes)
    && str_contains($routes, '/arrangementer/{eventId}/resultater')
    && str_contains($routes, '/serier/{seriesId}/sammenlagt'),
    'V3 results/standings routes registered'
);

echo "\n=== Live: unknown domain + API error handling ===\n";
$unknown = $client->publicContext('unknown-host.example.invalid');
$assert(($unknown['ok'] ?? true) === false, 'Ukjent domene → ikke ok');
$assert((int) ($unknown['status'] ?? 0) === 404, 'Ukjent domene → 404');

$badCalendar = (new PublicCalendarService())->forHost('unknown-host.example.invalid');
$assert(($badCalendar['ok'] ?? true) === false, 'Kalender ukjent host → feil (ingen V2-fallback)');

echo "\n=== Live: V2 results path still callable ===\n";
$_SERVER['HTTP_HOST'] = 'jaktfeltcup.local';
$v2Client = (new BackendApiClient())->publicResultsIndex('jaktfeltcup.local');
$assert(method_exists(BackendApiClient::class, 'publicResultsIndex'), 'BackendApiClient::publicResultsIndex exists');
$assert(method_exists(BackendApiClient::class, 'competitionSignup'), 'BackendApiClient::competitionSignup exists (påmelding V2)');
if ($v2Client['ok'] ?? false) {
    $assert(true, 'V2 results index reachable');
} else {
    echo "WARN  V2 results not reachable (" . ($v2Client['error'] ?? 'unknown') . ") — hybrid path code still present\n";
}

echo "\n=== Guard: calendar index uses V3 URLs ===\n";
$calendarFile = file_get_contents($basePath . '/app/03-controller/CalendarController.php');
$assert(
    is_string($calendarFile)
    && str_contains($calendarFile, 'PublicCalendarService')
    && !preg_match('/function index\(\)[\s\S]*BackendApiClient\(\)->publicCalendar/', $calendarFile),
    'CalendarController::index uses PublicCalendarService (not V2 publicCalendar)'
);
$assert(
    is_string($calendarFile) && str_contains($calendarFile, 'TenantContext') && str_contains($calendarFile, 'competitionSignup'),
    'Calendar show/register still hybrid V2'
);
$calService = file_get_contents($basePath . '/app/04-services/PublicCalendarService.php');
$assert(is_string($calService) && str_contains($calService, 'v3EventUrl'), 'Calendar service bruker v3EventUrl');

echo "\nResult: $passes passed, $failures failed\n";
exit($failures > 0 ? 1 : 0);
