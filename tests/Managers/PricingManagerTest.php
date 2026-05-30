<?php

use Jegex\LaravelPriceable\DataTransferObjects\PricingResponse;
use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Tests\Models\Product;

beforeEach(function () {
    $this->defaultCurrency = Currency::factory()->default()->create();
    $this->product = Product::create(['name' => 'Test Product']);
});

it('can be instantiated via facade', function () {
    $manager = Pricing::for($this->product);

    expect($manager)->toBeInstanceOf(PricingManager::class);
});

it('returns pricing response from get', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);

    $response = $this->product->pricing()->get();

    expect($response)->toBeInstanceOf(PricingResponse::class);
});

it('resolves base price for default currency', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);

    $response = $this->product->pricing()->get();

    expect($response->matched)->not->toBeNull();
    expect($response->matched->price->cents)->toBe(1000);
    expect($response->base)->not->toBeNull();
    expect($response->base->price->cents)->toBe(1000);
});

it('returns null matched when no prices exist for currency', function () {
    $idr = Currency::factory()->create(['code' => 'IDR']);

    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);

    $response = $this->product->pricing()->currency($idr)->get();

    expect($response->matched)->toBeNull();
    expect($response->base)->toBeNull();
    expect($response->priceBreaks)->toHaveCount(0);
});

it('can set currency by code string', function () {
    $eur = Currency::factory()->create(['code' => 'EUR']);

    $this->product->prices()->create([
        'currency_id' => $eur->id,
        'price' => 2000,
    ]);

    $response = $this->product->pricing()->currency('EUR')->get();

    expect($response->matched)->not->toBeNull();
    expect($response->matched->price->cents)->toBe(2000);
});

it('selects matching price break for quantity', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 900,
        'min_quantity' => 5,
    ]);

    $response = $this->product->pricing()->qty(5)->get();

    expect($response->matched->price->cents)->toBe(900);
});

it('uses base price when quantity does not meet any break', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 900,
        'min_quantity' => 5,
    ]);

    $response = $this->product->pricing()->qty(3)->get();

    expect($response->matched->price->cents)->toBe(1000);
});

it('selects cheapest price break when multiple apply', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 900,
        'min_quantity' => 5,
    ]);
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 800,
        'min_quantity' => 10,
    ]);

    $response = $this->product->pricing()->qty(10)->get();

    expect($response->matched->price->cents)->toBe(800);
});

it('populates price breaks collection', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 900,
        'min_quantity' => 5,
    ]);

    $response = $this->product->pricing()->get();

    expect($response->priceBreaks)->toHaveCount(1);
});

it('uses default currency when none specified', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 500,
    ]);

    $response = $this->product->pricing()->get();

    expect($response->matched->price->cents)->toBe(500);
});

it('returns empty response for model without prices', function () {
    $response = $this->product->pricing()->get();

    expect($response->matched)->toBeNull();
    expect($response->base)->toBeNull();
});

it('supports chaining via product->pricing()', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 1000,
    ]);

    $response = $this->product->pricing()->currency($this->defaultCurrency)->qty(1)->get();

    expect($response->matched)->not->toBeNull();
});
