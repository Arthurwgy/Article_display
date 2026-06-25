<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('CORS_ORIGIN_1', 'http://localhost:5173'),
        env('CORS_ORIGIN_2', 'http://localhost:8080'),
        env('CORS_ORIGIN_3', 'http://localhost:9000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
