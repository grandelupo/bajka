<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Coin packages
    |--------------------------------------------------------------------------
    |
    | The one list both the shop page and Stripe read from, so the price a
    | reader sees is always the price they are charged. Prices are in PLN.
    |
    */

    'packages' => [
        'basic' => [
            'name' => 'Podstawowy pakiet',
            'coins' => 100,
            'price' => 20.00,
        ],
        'standard' => [
            'name' => 'Mała historia',
            'coins' => 500,
            'price' => 80.00,
        ],
        'premium' => [
            'name' => 'Wielka opowieść',
            'coins' => 1000,
            'price' => 145.00,
        ],
        'ultimate' => [
            'name' => 'Bajkowy świat',
            'coins' => 2500,
            'price' => 255.00,
        ],
    ],
];
