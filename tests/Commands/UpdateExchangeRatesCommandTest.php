<?php

use Illuminate\Support\Facades\Http;
use Jegex\LaravelPriceable\Models\Currency;

beforeEach(function () {
    Currency::factory()->default()->create();

    Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.9200000000,
        'is_active' => true,
    ]);

    Currency::factory()->create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
        'exchange_rate' => 0.7900000000,
        'is_active' => true,
    ]);
});

it('updates exchange rates for active currencies', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response([
            'date' => '2026-05-31',
            'usd' => [
                'eur' => 0.85,
                'gbp' => 0.75,
            ],
        ]),
    ]);

    $this->artisan('priceable:update-exchange-rates')
        ->expectsOutputToContain('Updated 2')
        ->expectsOutputToContain('Skipped 0')
        ->assertSuccessful();

    expect((float) Currency::where('code', 'EUR')->first()->exchange_rate)->toEqual(0.85);
    expect((float) Currency::where('code', 'GBP')->first()->exchange_rate)->toEqual(0.75);
});

it('does not update on dry run', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response([
            'date' => '2026-05-31',
            'usd' => [
                'eur' => 0.85,
                'gbp' => 0.75,
            ],
        ]),
    ]);

    $this->artisan('priceable:update-exchange-rates', ['--dry-run' => true])
        ->expectsOutputToContain('Would update 2')
        ->assertSuccessful();

    expect((float) Currency::where('code', 'EUR')->first()->exchange_rate)->toEqual(0.92);
    expect((float) Currency::where('code', 'GBP')->first()->exchange_rate)->toEqual(0.79);
});

it('fails when no default currency exists', function () {
    Currency::where('is_default', true)->update(['is_default' => false]);

    $this->artisan('priceable:update-exchange-rates')
        ->expectsOutputToContain('No default currency found')
        ->assertFailed();
});

it('fails when API is unreachable', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response(null, 500),
        'https://latest.currency-api.pages.dev/*' => Http::response(null, 500),
    ]);

    $this->artisan('priceable:update-exchange-rates')
        ->expectsOutputToContain('Failed to fetch exchange rates')
        ->assertFailed();
});

it('skips currencies not returned by the API', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response([
            'date' => '2026-05-31',
            'usd' => [
                'eur' => 0.85,
            ],
        ]),
    ]);

    $this->artisan('priceable:update-exchange-rates')
        ->expectsOutputToContain('Updated 1')
        ->expectsOutputToContain('Skipped 1')
        ->assertSuccessful();
});

it('does not update the default currency', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response([
            'date' => '2026-05-31',
            'usd' => [
                'eur' => 0.85,
                'gbp' => 0.75,
            ],
        ]),
    ]);

    $this->artisan('priceable:update-exchange-rates')
        ->assertSuccessful();

    expect((float) Currency::where('is_default', true)->first()->exchange_rate)->toEqual(1.0);
});

it('uses fallback URL when primary fails', function () {
    Http::fake([
        'https://cdn.jsdelivr.net/*' => Http::response(null, 500),
        'https://latest.currency-api.pages.dev/*' => Http::response([
            'date' => '2026-05-31',
            'usd' => [
                'eur' => 0.88,
                'gbp' => 0.78,
            ],
        ]),
    ]);

    $this->artisan('priceable:update-exchange-rates')
        ->expectsOutputToContain('Updated 2')
        ->assertSuccessful();

    expect((float) Currency::where('code', 'EUR')->first()->exchange_rate)->toEqual(0.88);
});
