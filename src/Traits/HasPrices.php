<?php

namespace Jegex\LaravelPriceable\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Price;

trait HasPrices
{
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
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
}
