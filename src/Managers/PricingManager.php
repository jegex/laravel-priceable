<?php

namespace Jegex\LaravelPriceable\Managers;

use Illuminate\Support\Collection;
use Jegex\LaravelPriceable\Contracts\Purchasable;
use Jegex\LaravelPriceable\DataTransferObjects\PricingResponse;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;

class PricingManager
{
    protected ?Purchasable $model = null;

    protected ?Currency $currency = null;

    protected int $qty = 1;

    public function for(?Purchasable $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function currency(Currency|string|null $currency): static
    {
        if ($currency === null) {
            $this->currency = null;

            return $this;
        }

        if ($currency instanceof Currency) {
            $this->currency = $currency;

            return $this;
        }

        $this->currency = Currency::where('code', $currency)->first();

        return $this;
    }

    public function qty(int $qty): static
    {
        $this->qty = $qty;

        return $this;
    }

    public function get(): PricingResponse
    {
        if (! $this->currency) {
            $this->currency = Currency::where('is_default', true)->first();
        }

        if (! $this->model || ! $this->currency) {
            return new PricingResponse(priceBreaks: new Collection);
        }

        /** @var Collection<int, Price> $prices */
        $prices = $this->model->prices()
            ->where('currency_id', $this->currency->id)
            ->orderBy('price')
            ->get();

        if ($prices->isEmpty()) {
            return new PricingResponse(priceBreaks: new Collection);
        }

        $basePrice = $prices->first(fn (Price $price) => $price->min_quantity === 1);

        $matched = $basePrice;

        $priceBreaks = $prices->filter(fn (Price $price) => $price->min_quantity > 1);

        $applicableBreak = $priceBreaks
            ->filter(fn (Price $price) => $this->qty >= $price->min_quantity)
            ->sortBy('price')
            ->first();

        if ($applicableBreak) {
            $matched = $applicableBreak;
        }

        return new PricingResponse(
            matched: $matched,
            base: $basePrice,
            priceBreaks: $priceBreaks,
        );
    }
}
