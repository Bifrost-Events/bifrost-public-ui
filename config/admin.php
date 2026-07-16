<?php

require_once __DIR__ . '/require-env.php';
require_once __DIR__ . '/resolve-events-url.php';
require_once __DIR__ . '/resolve-admin-url.php';

return [
    'api_base_url' => resolve_public_admin_api_base_url(),
];
