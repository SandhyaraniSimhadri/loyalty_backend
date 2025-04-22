<?php

return [
    'paths' => ['api/*'],  // Allow CORS for API routes
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],  // Your Angular URL
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
