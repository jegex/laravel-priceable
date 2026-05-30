<?php

use Jegex\LaravelPriceable\Casts\MoneyCast;
use Jegex\LaravelPriceable\DataTransferObjects\PricingResponse;
use Jegex\LaravelPriceable\Managers\PricingManager;

return [
    'default_currency' => 'USD',

    'pricing_manager' => PricingManager::class,

    'pricing_response' => PricingResponse::class,

    'money_cast' => MoneyCast::class,
];
