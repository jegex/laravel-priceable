<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter;

it('calculates decimal from cents', function () {
    $currency = new Currency([
        'decimal_place' => 2,
        'type' => 'fiat',
    ]);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    expect($formatter->decimal())->toBe(10.0);
});

it('formats fiat currency with locale', function () {
    $currency = new Currency([
        'code' => 'USD',
        'symbol' => '$',
        'decimal_place' => 2,
        'type' => 'fiat',
    ]);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    expect($formatter->formatted(locale: 'en_US'))->toBe('$10.00');
});

it('formats crypto without thousand separators', function () {
    $currency = new Currency([
        'code' => 'BTC',
        'symbol' => '₿',
        'decimal_place' => 8,
        'type' => 'crypto',
    ]);
    $formatter = new DefaultPriceFormatter(value: 123456789, currency: $currency);

    expect($formatter->formatted())->toBe('₿1.23456789');
});

it('formats unit price', function () {
    $currency = new Currency([
        'code' => 'USD',
        'symbol' => '$',
        'decimal_place' => 2,
        'type' => 'fiat',
    ]);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency, unitQty: 3);

    expect($formatter->unitFormatted(locale: 'en_US'))->toBe('$3.33');
});

it('falls back when NumberFormatter fails', function () {
    $currency = new Currency([
        'code' => 'XYZ',
        'symbol' => '~',
        'decimal_place' => 2,
        'type' => 'fiat',
    ]);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    expect($formatter->formatted(locale: 'xx_XX'))->toBe('~10.00');
});
