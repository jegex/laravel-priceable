<?php

namespace Jegex\LaravelPriceable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Priceable
{
    public function prices(): MorphMany;

    public function getUnitQuantity(): int;
}
