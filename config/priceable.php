<?php

use Jegex\LaravelPriceable\DataTransferObjects\PricingResponse;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter;

return [
    'models' => [
        'price' => Price::class,
        'currency' => Currency::class,
    ],

    'tables' => [
        'prices' => 'prices',
        'currencies' => 'currencies',
    ],

    'morph_name' => 'priceable',

    'pricing' => [
        'manager' => PricingManager::class,
        'response' => PricingResponse::class,
        'formatter' => DefaultPriceFormatter::class,
    ],

    'log_activity_name' => 'jegex',

    'currencies' => [
        [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0000000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => true,
        ],
        [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.9200000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.7900000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'IDR',
            'name' => 'Indonesian Rupiah',
            'symbol' => 'Rp',
            'exchange_rate' => 16000.0000000000,
            'decimal_place' => 0,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'BTC',
            'name' => 'Bitcoin',
            'symbol' => '₿',
            'exchange_rate' => 0.0000210000,
            'decimal_place' => 8,
            'type' => 'crypto',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'ETH',
            'name' => 'Ethereum',
            'symbol' => 'Ξ',
            'exchange_rate' => 0.0003100000,
            'decimal_place' => 8,
            'type' => 'crypto',
            'is_active' => true,
            'is_default' => false,
        ],
    ],
];
