<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Tests\Models\Product;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

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

it('can get price in specific currency', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);

    $price = $product->priceIn($this->defaultCurrency);

    expect($price)->not->toBeNull();
    expect($price->price->cents)->toBe(1000);
});

it('can get price in currency by code string', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);

    $price = $product->priceIn('USD');

    expect($price)->not->toBeNull();
    expect($price->price->cents)->toBe(1000);
});

it('returns null from priceIn when currency not found', function () {
    $product = Product::create(['name' => 'Test']);

    $price = $product->priceIn('XXX');

    expect($price)->toBeNull();
});

it('returns null from priceIn when no price exists', function () {
    $product = Product::create(['name' => 'Test']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    $price = $product->priceIn($eur);

    expect($price)->toBeNull();
});

it('can convert price to another currency', function () {
    $product = Product::create(['name' => 'Test']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'exchange_rate' => 0.9200000000]);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);

    $converted = $product->convertTo($eur);

    expect($converted)->toBeInstanceOf(MoneyValue::class);
    expect($converted->currency->code)->toBe('EUR');
    expect($converted->cents)->toBe(920);
});

it('returns same MoneyValue when converting to same currency', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1000]);

    $converted = $product->convertTo($this->defaultCurrency);

    expect($converted)->toBeInstanceOf(MoneyValue::class);
    expect($converted->cents)->toBe(1000);
});

it('returns null from convertTo when no price exists', function () {
    $product = Product::create(['name' => 'Test']);

    $result = $product->convertTo('EUR');

    expect($result)->toBeNull();
});

it('can get formatted base price', function () {
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create(['currency_id' => $this->defaultCurrency->id, 'price' => 1999]);

    $formatted = $product->formattedPrice(locale: 'en_US');

    expect($formatted)->toBe('$19.99');
});

it('can get formatted price in specific currency', function () {
    $product = Product::create(['name' => 'Test']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    $product->prices()->create(['currency_id' => $eur->id, 'price' => 2000]);

    $formatted = $product->formattedPrice(locale: 'en_US', currency: $eur);

    expect($formatted)->toBe('€20.00');
});

it('returns null from formattedPrice when no price exists', function () {
    $product = Product::create(['name' => 'Test']);

    $formatted = $product->formattedPrice();

    expect($formatted)->toBeNull();
});
