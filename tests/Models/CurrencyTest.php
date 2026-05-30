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

it('casts exchange rate and booleans correctly', function () {
    $currency = Currency::factory()->default()->create();

    expect($currency->exchange_rate)->toBeString();
    expect((float) $currency->exchange_rate)->toEqual(1.0);
    expect($currency->is_active)->toBeTrue();
    expect($currency->is_default)->toBeTrue();
});
