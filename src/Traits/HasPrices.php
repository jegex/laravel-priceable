<?php

namespace Jegex\LaravelPriceable\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\Services\CurrencyExchange;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

trait HasPrices
{
    public function prices(): MorphMany
    {
        $class = config('priceable.models.price', Price::class);
        $morph = config('priceable.morph_name', 'priceable');

        return $this->morphMany($class, $morph);
    }

    public function basePrices(): MorphMany
    {
        return $this->prices()->where('min_quantity', 1);
    }

    public function priceBreaks(): MorphMany
    {
        return $this->prices()->where('min_quantity', '>', 1);
    }

    public function pricing(): PricingManager
    {
        return Pricing::for($this);
    }

    public function priceIn(Currency|string $currency): ?Price
    {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->first();
        }

        if (! $currency) {
            return null;
        }

        return $this->prices()
            ->where('currency_id', $currency->id)
            ->where('min_quantity', 1)
            ->first();
    }

    public function convertTo(Currency|string $target, int $qty = 1): ?MoneyValue
    {
        $matched = $this->pricing()->qty($qty)->get()->matched;

        if (! $matched || ! $matched->price) {
            return null;
        }

        if (is_string($target)) {
            $target = Currency::where('code', $target)->first();
        }

        if (! $target || $matched->currency->is($target)) {
            return $matched->price;
        }

        $converted = app(CurrencyExchange::class)->convert(
            $matched->currency,
            $target,
            $matched->price->cents,
        );

        return new MoneyValue(
            cents: (int) round($converted),
            currency: $target,
            unitQty: $qty,
        );
    }

    public function formattedPrice(?string $locale = null, Currency|string|null $currency = null): ?string
    {
        $price = $currency
            ? $this->priceIn($currency)
            : $this->basePrices()->first();

        if (! $price || ! $price->price) {
            return null;
        }

        return $price->price->formatted(locale: $locale);
    }
}
