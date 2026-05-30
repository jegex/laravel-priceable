<?php

use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter;

it('has priceable config defaults', function () {
    expect(config('priceable.models.price'))->toBe(Price::class);
    expect(config('priceable.models.currency'))->toBe(Currency::class);
    expect(config('priceable.tables.prices'))->toBe('prices');
    expect(config('priceable.tables.currencies'))->toBe('currencies');
    expect(config('priceable.morph_name'))->toBe('priceable');
});

it('has pricing config defaults', function () {
    expect(config('priceable.pricing.manager'))->toBe(PricingManager::class);
    expect(config('priceable.pricing.formatter'))->toBe(DefaultPriceFormatter::class);
});

it('facade resolves pricing manager', function () {
    $manager = Pricing::for(null);

    expect($manager)->toBeInstanceOf(PricingManager::class);
});
