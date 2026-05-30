<?php

namespace Jegex\LaravelPriceable;

use Jegex\LaravelPriceable\Models\Currency;

if (! function_exists('Jegex\LaravelPriceable\priceable_currency_model')) {
    function priceable_currency_model(): string
    {
        return config('priceable.models.currency', Currency::class);
    }
}
