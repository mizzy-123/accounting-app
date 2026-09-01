<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS Plan (sementara: semua gratis)
    |--------------------------------------------------------------------------
    */

    'plan' => env('SAAS_PLAN', 'free'),

    'pricing_enabled' => (bool) env('SAAS_PRICING_ENABLED', false),

    'plans' => [
        'free' => [
            'name' => 'Gratis',
            'price' => 0,
            'currency' => 'IDR',
            'description' => 'Semua fitur akuntansi tersedia tanpa biaya.',
        ],
    ],

];
