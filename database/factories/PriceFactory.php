<?php

namespace Jegex\LaravelPriceable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;

class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition(): array
    {
        return [
            'currency_id' => Currency::factory(),
            'price' => $this->faker->numberBetween(100, 99999),
            'compare_price' => null,
            'min_quantity' => 1,
        ];
    }

    public function sale(): static
    {
        return $this->state(fn (array $attrs) => [
            'compare_price' => ($attrs['price'] ?? 1000) * 2,
        ]);
    }
}
