<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/app/06-support/bootstrap.php';

use App\Service\BackendApiClient;
use App\Support\Session;

Session::startRequired();

$client = new BackendApiClient();
$email = 'shooters-test-' . time() . '@example.com';

$reg = $client->participantRegister($email, 'password123', 'A', 'B', '90001122', null, '1.0');
echo 'register ok=' . (($reg['ok'] ?? false) ? 'yes' : 'no') . "\n";

$shooters = $client->participantShooters();
echo 'shooters ok=' . (($shooters['ok'] ?? false) ? 'yes' : 'no') . ' status=' . ($shooters['status'] ?? 0) . ' error=' . ($shooters['error'] ?? '') . "\n";

$signup = $client->registerSignup('cup.bifrost.local', ['competition_id' => 1, 'participant_id' => 1]);
echo 'signup status=' . ($signup['status'] ?? 0) . ' error=' . ($signup['error'] ?? '') . "\n";
