<?php

namespace Jegex\LaravelPriceable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jegex\LaravelPriceable\Models\Currency;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0000000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attrs) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0000000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_default' => true,
        ]);
    }

    public function crypto(string $code = 'BTC'): static
    {
        $rates = ['BTC' => 0.0000150000, 'ETH' => 0.0003200000];

        return $this->state(fn (array $attrs) => [
            'code' => $code,
            'name' => match ($code) {
                'BTC' => 'Bitcoin', 'ETH' => 'Ethereum', default => 'Unknown'
            },
            'symbol' => match ($code) {
                'BTC' => '₿', 'ETH' => 'Ξ', default => ''
            },
            'exchange_rate' => $rates[$code] ?? 0.0001000000,
            'decimal_place' => 8,
            'type' => 'crypto',
        ]);
    }
}
