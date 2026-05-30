<?php

namespace Jegex\LaravelPriceable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Jegex\LaravelPriceable\Database\Factories\CurrencyFactory;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $exchange_rate
 * @property int $decimal_place
 * @property string $type
 * @property bool $is_active
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Currency extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'code', 'name', 'symbol',
        'exchange_rate', 'decimal_place',
        'type', 'is_active', 'is_default',
    ];

    public function getTable(): string
    {
        return config('priceable.tables.currencies', parent::getTable());
    }

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function convertTo(self $target, int|float $amount): int|float
    {
        if ($this->is($target)) {
            return $amount;
        }

        return ($amount / (float) $this->exchange_rate) * (float) $target->exchange_rate;
    }

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }

    /**
     * Get the log options for the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(config('priceable.log_activity_name', 'jegex'))
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
