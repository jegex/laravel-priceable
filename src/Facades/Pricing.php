<?php

namespace Jegex\LaravelPriceable\Facades;

use Illuminate\Support\Facades\Facade;
use Jegex\LaravelPriceable\Contracts\PricingManagerInterface;

class Pricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PricingManagerInterface::class;
    }
}
