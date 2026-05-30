<?php

namespace Jegex\LaravelPriceable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;
use function Jegex\LaravelPriceable\priceable_currency_model;

class MoneyCast implements CastsAttributes
{
    public function __construct(protected ?string $currencySource = null) {}

    public function get($model, string $key, $value, array $attributes): ?MoneyValue
    {
        if ($value === null) {
            return null;
        }

        return new MoneyValue(
            cents: (int) $value,
            currency: $this->resolveCurrency($model),
        );
    }

    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof MoneyValue) {
            return $value->cents;
        }

        return (int) $value;
    }

    protected function resolveCurrency($model): Currency
    {
        if ($this->currencySource === null) {
            return $this->resolveDefaultCurrency();
        }

        if ($model && isset($model->{$this->currencySource}) && $model->{$this->currencySource} instanceof Currency) {
            return $model->{$this->currencySource};
        }

        return priceable_currency_model()::where('code', $this->currencySource)->firstOrFail();
    }

    protected function resolveDefaultCurrency(): Currency
    {
        return priceable_currency_model()::where('is_default', true)->firstOrFail();
    }
}
