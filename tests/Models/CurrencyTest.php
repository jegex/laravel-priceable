<?php

use Jegex\LaravelPriceable\Models\Currency;

it('scope default returns only default currency', function () {
    Currency::factory()->default()->create();
    Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.9200000000]);
    Currency::factory()->create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.7800000000]);

    $defaults = Currency::default()->get();

    expect($defaults)->toHaveCount(1);
    expect($defaults->first()->code)->toBe('USD');
});

it('scope active returns only active currencies', function () {
    Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.9200000000]);
    Currency::factory()->create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.7800000000]);
    Currency::factory()->create(['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'is_active' => false]);

    $active = Currency::active()->get();

    expect($active)->toHaveCount(2);
});

it('convertTo delegates to CurrencyExchange', function () {
    $usd = Currency::factory()->default()->create();
    $eur = Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.9200000000,
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => true,
    ]);

    $result = $usd->convertTo($eur, 100);

    expect($result)->toEqual(92.0);
});

it('forces is_active true when currency is default on save', function () {
    $currency = Currency::create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
        'exchange_rate' => 1.0000000000,
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => false,
        'is_default' => true,
    ]);

    expect($currency->is_active)->toBeTrue();
    expect($currency->is_default)->toBeTrue();
});

it('clears default from other currencies when new default is set', function () {
    $first = Currency::factory()->default()->create();
    $second = Currency::factory()->create(['code' => 'EUR']);

    $second->update(['is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse();
    expect($second->fresh()->is_default)->toBeTrue();
});

it('allows non-default currencies to be inactive', function () {
    $default = Currency::factory()->default()->create();
    $inactive = Currency::factory()->create([
        'code' => 'EUR',
        'is_active' => false,
    ]);

    expect($inactive->fresh()->is_active)->toBeFalse();
    expect($default->fresh()->is_default)->toBeTrue();
});

it('prevents deactivating the default currency', function () {
    $currency = Currency::factory()->default()->create();

    $currency->update(['is_active' => false]);

    expect($currency->fresh()->is_active)->toBeTrue();
});

it('casts exchange rate and booleans correctly', function () {
    $currency = Currency::create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
        'exchange_rate' => '1.5000000000',
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => 1,
        'is_default' => 0,
    ]);

    expect($currency->exchange_rate)->toBeString();
    expect((float) $currency->exchange_rate)->toEqual(1.5);
    expect($currency->is_active)->toBeTrue();
    expect($currency->is_default)->toBeFalse();
});
