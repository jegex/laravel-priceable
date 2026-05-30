<?php

use Jegex\LaravelPriceable\Models\Currency;

beforeEach(function () {
    Currency::factory()->default()->create();
    Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.9200000000]);
});

it('shows warning when no currencies exist', function () {
    Currency::query()->delete();

    $this->artisan('laravel-priceable')
        ->expectsOutputToContain('No currencies found')
        ->assertSuccessful();
});

it('displays currency summary', function () {
    $this->artisan('laravel-priceable')
        ->expectsOutputToContain('Laravel Priceable')
        ->expectsOutputToContain('Total currencies')
        ->expectsOutputToContain('2')
        ->assertSuccessful();
});

it('shows default currency', function () {
    Currency::factory()->create(['code' => 'GBP', 'is_default' => false]);

    $this->artisan('laravel-priceable')
        ->expectsOutputToContain('Default currency')
        ->expectsOutputToContain('USD')
        ->assertSuccessful();
});

it('lists all currencies with details', function () {
    $this->artisan('laravel-priceable')
        ->expectsOutputToContain('Total currencies')
        ->assertSuccessful();
});
