<?php

namespace Jegex\LaravelPriceable;

if (! function_exists('Jegex\LaravelPriceable\priceable_currency_model')) {
    function priceable_currency_model(): string
    {
        return config('priceable.models.currency', \Jegex\LaravelPriceable\Models\Currency::class);
    }
}
