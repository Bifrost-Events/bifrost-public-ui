<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/app/06-support/bootstrap.php';

use App\Service\BackendApiClient;
use App\Support\Session;

Session::startRequired();

$email = 'signup-test-' . time() . '@example.com';
$client = new BackendApiClient();

echo "=== Register ===\n";
$reg = $client->participantRegister(
    $email,
    'password123',
    'Signup',
    'Test',
    '90001122',
    null,
    '1.0',
);
echo 'ok=' . (($reg['ok'] ?? false) ? 'yes' : 'no') . ' status=' . ($reg['status'] ?? 0) . "\n";
echo 'error=' . ($reg['error'] ?? '') . "\n";
echo 'backend_cookie=' . Session::getBackendCookie() . "\n";
$participantId = (int) ($reg['data']['onboarding']['created_participant']['id'] ?? 0);
echo 'participant_id=' . $participantId . "\n\n";

echo "=== Me ===\n";
$me = $client->me();
echo 'ok=' . (($me['ok'] ?? false) ? 'yes' : 'no') . ' status=' . ($me['status'] ?? 0) . "\n";
echo 'error=' . ($me['error'] ?? '') . "\n\n";

echo "=== Register signup ===\n";
$signup = $client->registerSignup('cup.bifrost.local', [
    'competition_id' => 1,
    'participant_id' => $participantId,
]);
echo 'ok=' . (($signup['ok'] ?? false) ? 'yes' : 'no') . ' status=' . ($signup['status'] ?? 0) . "\n";
echo 'error=' . ($signup['error'] ?? '') . "\n";
echo 'body=' . json_encode($signup['data'] ?? null) . "\n";
