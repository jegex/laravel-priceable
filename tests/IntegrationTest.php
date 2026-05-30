<?php

use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Tests\Models\Product;

beforeEach(function () {
    $this->usd = Currency::factory()->default()->create();
    $this->eur = Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.9200000000,
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => true,
    ]);
    $this->product = Product::create(['name' => 'Widget']);
});

it('creates and retrieves price for default currency', function () {
    $this->product->prices()->create([
        'currency_id' => $this->usd->id,
        'price' => 1999,
    ]);

    $response = Pricing::for($this->product)->get();

    expect($response->matched)->not->toBeNull();
    expect($response->matched->price->formatted(locale: 'en_US'))->toBe('$19.99');
});

it('creates prices in multiple currencies', function () {
    $this->product->prices()->create(['currency_id' => $this->usd->id, 'price' => 1999]);
    $this->product->prices()->create(['currency_id' => $this->eur->id, 'price' => 1599]);

    $usdResponse = Pricing::for($this->product)->currency('USD')->get();
    $eurResponse = Pricing::for($this->product)->currency('EUR')->get();

    expect($usdResponse->matched->price->formatted(locale: 'en_US'))->toBe('$19.99');
    expect($eurResponse->matched->price->formatted(locale: 'en_US'))->toBe('€15.99');
});

it('applies quantity-based pricing', function () {
    $this->product->prices()->create(['currency_id' => $this->usd->id, 'price' => 1999]);
    $this->product->prices()->create(['currency_id' => $this->usd->id, 'price' => 1499, 'min_quantity' => 5]);
    $this->product->prices()->create(['currency_id' => $this->usd->id, 'price' => 999, 'min_quantity' => 10]);

    $single = Pricing::for($this->product)->qty(1)->get();
    $bulk5 = Pricing::for($this->product)->qty(5)->get();
    $bulk10 = Pricing::for($this->product)->qty(10)->get();

    expect($single->matched->price->cents)->toBe(1999);
    expect($bulk5->matched->price->cents)->toBe(1499);
    expect($bulk10->matched->price->cents)->toBe(999);
});

it('converts price between currencies', function () {
    $this->product->prices()->create(['currency_id' => $this->usd->id, 'price' => 10000]);

    $converted = $this->product->convertTo($this->eur);

    expect($converted)->not->toBeNull();
    expect($converted->currency->code)->toBe('EUR');
    expect($converted->cents)->toBe(9200);
});

it('seeds currencies from config and uses them', function () {
    Currency::query()->delete();

    config()->set('priceable.currencies', [
        [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0000000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => true,
        ],
        [
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'exchange_rate' => 150.0000000000,
            'decimal_place' => 0,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => false,
        ],
    ]);

    $this->artisan('priceable:seed-currencies', ['--force' => true])
        ->assertSuccessful();

    expect(Currency::count())->toBe(2);

    $product = Product::create(['name' => 'Test']);
    $jpy = Currency::where('code', 'JPY')->first();

    $product->prices()->create(['currency_id' => $jpy->id, 'price' => 15000]);

    $response = Pricing::for($product)->currency('JPY')->get();

    expect($response->matched->price->cents)->toBe(15000);
    expect($response->matched->price->decimal())->toBe(15000.0);
});

it('returns empty pricing response for model without prices', function () {
    $response = Pricing::for($this->product)->get();

    expect($response->matched)->toBeNull();
    expect($response->base)->toBeNull();
    expect($response->priceBreaks)->toHaveCount(0);
});
