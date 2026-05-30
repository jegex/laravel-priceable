<?php

use Jegex\LaravelPriceable\Casts\MoneyCast;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

beforeEach(function () {
    $this->currency = Currency::factory()->default()->create();
});

it('returns null for null value', function () {
    $cast = new MoneyCast('USD');
    $model = new stdClass;

    $result = $cast->get($model, 'price', null, []);

    expect($result)->toBeNull();
});

it('returns MoneyValue from integer cents via relation', function () {
    $model = new stdClass;
    $model->currency = $this->currency;

    $cast = new MoneyCast('currency');
    $result = $cast->get($model, 'price', 1000, ['currency_id' => $this->currency->id]);

    expect($result)->toBeInstanceOf(MoneyValue::class);
    expect($result->cents)->toBe(1000);
    expect($result->currency->code)->toBe('USD');
});

it('sets MoneyValue to integer cents', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency);
    $cast = new MoneyCast('currency');
    $model = new stdClass;

    $result = $cast->set($model, 'price', $money, []);

    expect($result)->toBe(1000);
});

it('stores raw integer as-is', function () {
    $cast = new MoneyCast('USD');
    $model = new stdClass;

    $result = $cast->set($model, 'price', 1000, []);

    expect($result)->toBe(1000);
});

it('uses fixed currency code when specified', function () {
    $cast = new MoneyCast('USD');
    $model = new stdClass;

    $result = $cast->get($model, 'price', 500, []);

    expect($result)->toBeInstanceOf(MoneyValue::class);
    expect($result->currency->code)->toBe('USD');
});
