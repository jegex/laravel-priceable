<?php

namespace Jegex\LaravelPriceable\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\LaravelPriceable\Contracts\CurrencyExchangeInterface;
use Jegex\LaravelPriceable\Contracts\PricingManagerInterface;
use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

use function Jegex\LaravelPriceable\priceable_currency_model;

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

    public function pricing(): PricingManagerInterface
    {
        return Pricing::for($this);
    }

    public function priceIn(Currency|string $currency): ?Price
    {
        $currency = $this->resolveCurrency($currency);

        if (! $currency) {
            return null;
        }

        return $this->prices()
            ->with('currency')
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

        $targetCurrency = $this->resolveCurrency($target);

        if (! $targetCurrency || $matched->currency->is($targetCurrency)) {
            return $matched->price;
        }

        $converted = app(CurrencyExchangeInterface::class)->convert(
            $matched->currency,
            $targetCurrency,
            $matched->price->cents,
        );

        return new MoneyValue(
            cents: (int) round($converted),
            currency: $targetCurrency,
            unitQty: $qty,
        );
    }

    public function formattedPrice(?string $locale = null, Currency|string|null $currency = null): ?string
    {
        $price = $currency
            ? $this->priceIn($currency)
            : $this->basePrices()->with('currency')->first();

        if (! $price || ! $price->price) {
            return null;
        }

        return $price->price->formatted(locale: $locale);
    }

    private function resolveCurrency(Currency|string|null $currency): ?Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        $class = priceable_currency_model();

        return $class::where('code', $currency)->first();
    }
}
