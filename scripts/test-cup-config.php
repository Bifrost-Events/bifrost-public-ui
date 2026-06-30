<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = $argv[1] ?? 'jaktfeltcup.local';

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/06-support/bootstrap.php';

$c = \App\Support\CupConfigLoader::current();
echo json_encode([
    'host' => $_SERVER['HTTP_HOST'],
    'name' => $c['name'] ?? null,
    'config_file' => $c['_meta']['config_file'] ?? null,
    'blocks' => $c['layout']['frontpage_blocks'] ?? [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
