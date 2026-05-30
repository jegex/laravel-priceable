<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Tests\Models\Product;

beforeEach(function () {
    $this->defaultCurrency = Currency::factory()->default()->create();
});

it('has prices relationship', function () {
    $product = Product::create(['name' => 'Test']);

    expect($product->prices())->toBeInstanceOf(MorphMany::class);
});

it('can attach price to model', function () {
    $product = Product::create(['name' => 'Test']);
    $currency = Currency::factory()->create(['code' => 'EUR']);

    $product->prices()->create([
        'currency_id' => $currency->id,
        'price' => 1000,
    ]);

    expect($product->prices)->toHaveCount(1);
    expect($product->prices->first()->price->cents)->toBe(1000);
});

it('can get base prices', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);
    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 900, 'min_quantity' => 5]);

    expect($product->basePrices)->toHaveCount(1);
    expect($product->basePrices->first()->price->cents)->toBe(1000);
});

it('can get price breaks', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);
    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 900, 'min_quantity' => 5]);
    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 800, 'min_quantity' => 10]);

    expect($product->priceBreaks)->toHaveCount(2);
});

it('returns pricing manager from pricing method', function () {
    $product = Product::create(['name' => 'Test']);

    expect($product->pricing())->toBeInstanceOf(PricingManager::class);
});
