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
];
