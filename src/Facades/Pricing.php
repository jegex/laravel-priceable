<?php

namespace Jegex\LaravelPriceable\Facades;

use Illuminate\Support\Facades\Facade;

class Pricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jegex\LaravelPriceable\Managers\PricingManager::class;
    }
}
