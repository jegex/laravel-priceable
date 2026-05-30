<?php

namespace Jegex\LaravelPriceable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jegex\LaravelPriceable\Casts\MoneyCast;
use Jegex\LaravelPriceable\Database\Factories\PriceFactory;

use function Jegex\LaravelPriceable\priceable_currency_model;

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

    public function getTable(): string
    {
        return config('priceable.tables.prices', parent::getTable());
    }

    protected function casts(): array
    {
        $money = MoneyCast::class;

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
        $class = priceable_currency_model();

        return $this->belongsTo($class);
    }

    protected static function newFactory(): PriceFactory
    {
        return PriceFactory::new();
    }
}
