<?php

namespace Jegex\LaravelPriceable\Services;

use Jegex\LaravelPriceable\Contracts\CurrencyExchangeInterface;
use Jegex\LaravelPriceable\Models\Currency;

class CurrencyExchange implements CurrencyExchangeInterface
{
    public function convert(Currency $from, Currency $to, int|float $amount): float
    {
        if ($from->is($to)) {
            return (float) $amount;
        }

        return ($amount / (float) $from->exchange_rate) * (float) $to->exchange_rate;
    }
}
