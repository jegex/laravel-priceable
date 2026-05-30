<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Tests\Models\Product;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

beforeEach(function () {
    $this->defaultCurrency = Currency::factory()->default()->create();
});

it('has priceable morphTo relation', function () {
    $product = Product::create(['name' => 'Test']);
    $price = $product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 2500,
    ]);

    expect($price->priceable)->toBeInstanceOf(Product::class);
    expect($price->priceable->id)->toBe($product->id);
});

it('has currency belongsTo relation', function () {
    $product = Product::create(['name' => 'Test']);
    $price = $product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 2500,
    ]);

    expect($price->currency)->toBeInstanceOf(Currency::class);
    expect($price->currency->code)->toBe('USD');
});

it('casts price to MoneyValue on retrieve', function () {
    $product = Product::create(['name' => 'Test']);
    $price = $product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 2500,
    ]);

    expect($price->price)->toBeInstanceOf(MoneyValue::class);
    expect($price->price->cents)->toBe(2500);
});

it('casts compare_price as nullable MoneyValue', function () {
    $product = Product::create(['name' => 'Test']);
    $price = $product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 2500,
        'compare_price' => 3000,
    ]);

    expect($price->compare_price)->toBeInstanceOf(MoneyValue::class);
    expect($price->compare_price->cents)->toBe(3000);
});

it('casts min_quantity as integer', function () {
    $product = Product::create(['name' => 'Test']);
    $price = $product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 2500,
        'min_quantity' => 5,
    ]);

    expect($price->min_quantity)->toBeInt();
    expect($price->min_quantity)->toBe(5);
});
