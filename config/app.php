<?php

use function App\Lib\Utils\Functions\env;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    |
    */

    'name' => env('APP_NAME', 'malidkha-mvc'),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    |
    */

    'url' => env('APP_URL', 'http://localhost:8081'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    |
    */

    'locale' => env('APP_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    'environment' => [
        'env'          => env('APP_ENV', 'production'),
        'debug'        => (bool) env('APP_DEBUG', false),
        'maintenance'  => env('APP_MAINTENANCE', false),
    ],

];
