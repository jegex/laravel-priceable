<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

beforeEach(function () {
    $this->currency = new Currency([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'decimal_place' => 2,
    ]);
});

it('can create money value', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency);

    expect($money->cents)->toBe(1000);
    expect($money->currency->code)->toBe('USD');
    expect($money->unitQty)->toBe(1);
});

it('calculates decimal', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency);

    expect($money->decimal())->toBe(10.0);
});

it('calculates unit decimal', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency, unitQty: 3);

    expect($money->unitDecimal())->toBe(3.33);
});

it('formats money with currency symbol', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency);

    expect($money->formatted(locale: 'en_US'))->toBe('$10.00');
});

it('handles zero decimal currencies', function () {
    $currency = new Currency(['code' => 'IDR', 'symbol' => 'Rp', 'decimal_place' => 0]);
    $money = new MoneyValue(cents: 15000, currency: $currency);

    expect($money->decimal())->toBe(15000.0);
    $formatted = $money->formatted(locale: 'id_ID');
    expect($formatted)->toContain('Rp');
    expect($formatted)->toContain('15.000');
});

it('handles crypto decimal places', function () {
    $currency = new Currency(['code' => 'BTC', 'symbol' => '₿', 'decimal_place' => 8, 'type' => 'crypto']);
    $money = new MoneyValue(cents: 100000000, currency: $currency);

    expect($money->decimal())->toBe(1.0);
});

it('formats crypto without thousand separators', function () {
    $currency = new Currency(['code' => 'BTC', 'symbol' => '₿', 'decimal_place' => 8, 'type' => 'crypto']);
    $money = new MoneyValue(cents: 123456789, currency: $currency);

    expect($money->formatted())->toBe('₿1.23456789');
});

it('is stringable', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency);

    expect((string) $money)->toBe('1000');
});

it('can format unit price', function () {
    $money = new MoneyValue(cents: 1000, currency: $this->currency, unitQty: 3);

    expect($money->unitFormatted(locale: 'en_US'))->toBe('$3.33');
});
