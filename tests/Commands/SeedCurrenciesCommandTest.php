<?php

use Jegex\LaravelPriceable\Models\Currency;

it('seeds currencies from config', function () {
    Currency::factory()->create();

    $this->artisan('priceable:seed-currencies', ['--force' => true])
        ->expectsOutputToContain('Seeded 6 currencies successfully.')
        ->assertSuccessful();

    expect(Currency::count())->toBe(6);
});

it('does not seed when config is empty', function () {
    config()->set('priceable.currencies', []);

    $this->artisan('priceable:seed-currencies', ['--force' => true])
        ->expectsOutputToContain('No currencies defined')
        ->assertSuccessful();
});
