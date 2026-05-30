<?php

namespace Jegex\LaravelPriceable\Contracts;

use Jegex\LaravelPriceable\Models\Currency;

interface CurrencyExchangeInterface
{
    public function convert(Currency $from, Currency $to, int|float $amount): float;
}
