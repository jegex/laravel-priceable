<?php

namespace Jegex\LaravelPriceable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jegex\LaravelPriceable\Casts\MoneyCast;
use Jegex\LaravelPriceable\Database\Factories\PriceFactory;

/**
 * @property int $id
 * @property int $currency_id
 * @property int $min_quantity
 */
class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id', 'price', 'compare_price', 'min_quantity',
    ];

    protected function casts(): array
    {
        $money = config('priceable.money_cast', MoneyCast::class);

        return [
            'price' => $money.':currency',
            'compare_price' => $money.':currency',
            'min_quantity' => 'integer',
        ];
    }

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    protected static function newFactory(): PriceFactory
    {
        return PriceFactory::new();
    }
}
