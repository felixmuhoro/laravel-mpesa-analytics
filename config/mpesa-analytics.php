<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    | Prefix and middleware for the analytics dashboard routes.
    */
    'route_prefix'     => env('MPESA_ANALYTICS_ROUTE_PREFIX', 'mpesa-analytics'),
    'route_middleware' => explode(',', env('MPESA_ANALYTICS_MIDDLEWARE', 'web')),

    /*
    |--------------------------------------------------------------------------
    | Default Business Short Code
    |--------------------------------------------------------------------------
    | Used as a fallback when an event doesn't carry a short code.
    */
    'default_short_code' => env('MPESA_SHORT_CODE', null),

    /*
    |--------------------------------------------------------------------------
    | Event Listener Mapping
    |--------------------------------------------------------------------------
    | Map your application's payment events to the analytics listener.
    | The listener expects each event to carry a 'transaction' property.
    */
    'listen' => [
        // 'App\Events\PaymentSuccessful' => true,
        // 'App\Events\PaymentFailed'     => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discover Events
    |--------------------------------------------------------------------------
    | When true, the package attempts to auto-wire events from
    | felixmuhoro/laravel-mpesa if they exist in the application.
    */
    'auto_discover_events' => env('MPESA_ANALYTICS_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Default Date Range
    |--------------------------------------------------------------------------
    | Default period shown on the dashboard.
    | Options: today, yesterday, last_7_days, last_30_days, this_month, last_month, this_year
    */
    'default_range' => env('MPESA_ANALYTICS_DEFAULT_RANGE', 'last_30_days'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency'        => env('MPESA_ANALYTICS_CURRENCY', 'KES'),
    'currency_symbol' => env('MPESA_ANALYTICS_CURRENCY_SYMBOL', 'KSh'),

    /*
    |--------------------------------------------------------------------------
    | Phone Masking
    |--------------------------------------------------------------------------
    | When true, phone numbers on the dashboard are masked (e.g., 2547****1234).
    */
    'mask_phones' => env('MPESA_ANALYTICS_MASK_PHONES', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    | Seconds to cache aggregated metrics. Set to 0 to disable caching.
    */
    'cache_ttl' => (int) env('MPESA_ANALYTICS_CACHE_TTL', 300),
];
