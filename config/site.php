<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Cache TTL Configuration
    |--------------------------------------------------------------------------
    |
    | Defines default Time-To-Live (in seconds) for homepage queries and site
    | settings. Can be overridden via environment variables if necessary.
    |
    */
    'homepage_cache_ttl' => (int) env('HOMEPAGE_CACHE_TTL', 1800),
];
