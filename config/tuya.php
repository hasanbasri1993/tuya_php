<?php

return [
    'client_id' => env('TUYA_CLIENT_ID', ''),
    'client_secret' => env('TUYA_CLIENT_SECRET', ''),
    'api_url' => env('TUYA_API_URL', 'https://openapi.tuya.com'),
    'cache_ttl' => (int) env('TUYA_CACHE_TTL', 3600),
];

