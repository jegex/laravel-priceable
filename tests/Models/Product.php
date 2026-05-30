<?php

namespace Jegex\LaravelPriceable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Jegex\LaravelPriceable\Contracts\Purchasable;
use Jegex\LaravelPriceable\Traits\HasPrices;

class Product extends Model implements Purchasable
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
