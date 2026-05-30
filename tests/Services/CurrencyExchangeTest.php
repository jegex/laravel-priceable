<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Services\CurrencyExchange;

beforeEach(function () {
    $this->service = app(CurrencyExchange::class);

    $this->usd = Currency::factory()->default()->create();
    $this->idr = Currency::factory()->create([
        'code' => 'IDR',
        'name' => 'Indonesian Rupiah',
        'symbol' => 'Rp',
        'exchange_rate' => 16000.0000000000,
        'decimal_place' => 0,
        'type' => 'fiat',
        'is_active' => true,
    ]);
    $this->eur = Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.9200000000,
        'decimal_place' => 2,
        'type' => 'fiat',
        'is_active' => true,
    ]);
});

it('returns amount unchanged for same currency', function () {
    $result = $this->service->convert($this->usd, $this->usd, 100);

    expect($result)->toEqual(100.0);
});

it('converts between currencies using exchange rates', function () {
    $result = $this->service->convert($this->usd, $this->idr, 100);

    expect($result)->toEqual(1_600_000.0);
});

it('converts from non-base currency to another', function () {
    $result = $this->service->convert($this->idr, $this->eur, 16000);

    expect($result)->toEqual(0.92);
});
