<?php

namespace Jegex\LaravelPriceable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Purchasable
{
    public function prices(): MorphMany;
}
