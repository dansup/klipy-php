<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Klipy API Key
    |--------------------------------------------------------------------------
    |
    | Get one at https://partner.klipy.com — start with a Test key
    | (100 calls/min) and request a production key when ready.
    |
    */
    'api_key' => env('KLIPY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | You almost never need to change this.
    |
    */
    'base_url' => env('KLIPY_BASE_URL', 'https://api.klipy.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | ISO 3166 Alpha-2 format, e.g. 'en_US', 'ge_GE', 'uk_UK'. Applied to all
    | requests when the per-call value is null. Leave null to omit.
    |
    */
    'default_locale' => env('KLIPY_DEFAULT_LOCALE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (float) env('KLIPY_TIMEOUT', 10.0),
];
