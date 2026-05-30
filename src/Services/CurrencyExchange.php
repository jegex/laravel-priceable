<?php

namespace Jegex\LaravelPriceable\Services;

use Jegex\LaravelPriceable\Models\Currency;

class CurrencyExchange
{
    public function convert(Currency $from, Currency $to, int|float $amount): int|float
    {
        if ($from->is($to)) {
            return $amount;
        }

        return ($amount / (float) $from->exchange_rate) * (float) $to->exchange_rate;
    }
}
