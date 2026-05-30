<?php

namespace Jegex\LaravelPriceable\Contracts;

use Jegex\LaravelPriceable\DataTransferObjects\PricingResponse;
use Jegex\LaravelPriceable\Models\Currency;

interface PricingManagerInterface
{
    public function for(?Priceable $model): static;

    public function currency(Currency|string|null $currency): static;

    public function qty(int $qty): static;

    public function get(): PricingResponse;
}
