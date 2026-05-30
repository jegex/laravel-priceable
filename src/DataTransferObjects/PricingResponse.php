<?php

namespace Jegex\LaravelPriceable\DataTransferObjects;

use Illuminate\Support\Collection;
use Jegex\LaravelPriceable\Models\Price;

class PricingResponse
{
    /** @param Collection<int, Price> $priceBreaks */
    public function __construct(
        public ?Price $matched = null,
        public ?Price $base = null,
        public Collection $priceBreaks = new Collection(),
    ) {}
}
