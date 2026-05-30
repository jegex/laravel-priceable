<?php

use Illuminate\Support\Facades\Schema;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\Tests\Models\Product;

it('creates currencies table with expected columns', function () {
    $columns = Schema::getColumnListing('currencies');

    expect($columns)->toContain('id', 'code', 'name', 'symbol', 'exchange_rate', 'decimal_place', 'type', 'is_active', 'is_default', 'created_at', 'updated_at');
});

it('creates prices table with expected columns', function () {
    $columns = Schema::getColumnListing('prices');

    expect($columns)->toContain('id', 'priceable_type', 'priceable_id', 'currency_id', 'price', 'compare_price', 'min_quantity', 'created_at', 'updated_at');
});

it('currencies table has unique code index', function () {
    $indexes = Schema::getIndexes('currencies');
    $unique = collect($indexes)->firstWhere('unique', true);

    expect($unique)->not->toBeNull();
    expect($unique['columns'])->toContain('code');
});

it('prices table has unique constraint on currency+priceable+min_qty', function () {
    $indexes = Schema::getIndexes('prices');
    $compound = collect($indexes)->firstWhere('columns', ['currency_id', 'priceable_type', 'priceable_id', 'min_quantity']);

    expect($compound)->not->toBeNull();
    expect($compound['unique'])->toBeTrue();
});

it('prices table has foreign key to currencies', function () {
    $foreignKeys = Schema::getForeignKeys('prices');
    $fk = collect($foreignKeys)->firstWhere('columns', ['currency_id']);

    expect($fk)->not->toBeNull();
    expect($fk['foreign_table'])->toBe('currencies');
    expect($fk['on_delete'])->toBe('cascade');
});

it('stores and retrieves currency correctly', function () {
    $currency = Currency::create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
        'exchange_rate' => 0.7900000000,
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => true,
        'is_default' => false,
    ]);

    $fresh = Currency::find($currency->id);

    expect($fresh->code)->toBe('GBP');
    expect((float) $fresh->exchange_rate)->toBe(0.79);
    expect($fresh->is_active)->toBeTrue();
});

it('stores and retrieves price correctly', function () {
    $currency = Currency::factory()->default()->create();
    $product = Product::create(['name' => 'Test']);

    $product->prices()->create([
        'currency_id' => $currency->id,
        'price' => 2500,
        'compare_price' => 3000,
        'min_quantity' => 1,
    ]);

    $price = Price::first();

    expect($price)->not->toBeNull();
    expect($price->price->cents)->toBe(2500);
    expect($price->compare_price->cents)->toBe(3000);
    expect($price->min_quantity)->toBe(1);
});
