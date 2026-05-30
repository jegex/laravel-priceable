<?php

namespace Jegex\LaravelPriceable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Jegex\LaravelPriceable\Contracts\Priceable;
use Jegex\LaravelPriceable\Traits\HasPrices;

class Product extends Model implements Priceable
{
    use HasPrices;

    protected $table = 'products';

    protected $guarded = [];

    public $timestamps = false;

    public function getUnitQuantity(): int
    {
        return 1;
    }
}
