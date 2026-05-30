<?php

namespace Jegex\LaravelPriceable\Facades;

use Illuminate\Support\Facades\Facade;
use Jegex\LaravelPriceable\Managers\PricingManager;

class Pricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PricingManager::class;
    }
}
