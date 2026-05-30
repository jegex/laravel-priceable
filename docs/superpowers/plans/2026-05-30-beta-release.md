# Beta Release Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Push `jegex/laravel-priceable` from Alpha to Beta with comprehensive test coverage, currency seeder, and production-quality README.

**Architecture:** Three independent workstreams ordered test-first: (1) expand test coverage across all untested public API, (2) config-driven currency seeder with artisan command, (3) rewrite README with real usage examples. Each phase produces independently testable output.

**Tech Stack:** PHP 8.4, Laravel 11-13, Pest PHP 4, Spatie Package Tools, PHPStan level 5.

---

### Task 1: CurrencyExchange service tests

**Files:**
- Create: `tests/Services/CurrencyExchangeTest.php`
- No source changes

- [ ] **Step 1: Write tests**

```php
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

    expect($result)->toBe(100.0);
});

it('converts between currencies using exchange rates', function () {
    $result = $this->service->convert($this->usd, $this->idr, 100);

    expect($result)->toBe(1_600_000.0);
});

it('converts from non-base currency to another', function () {
    $result = $this->service->convert($this->idr, $this->eur, 16000);

    expect($result)->toBe(0.92);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Services/CurrencyExchangeTest.php --compact`
Expected: 3 tests, all FAIL (class not found)

- [ ] **Step 3: Create test directory**

```bash
mkdir -p tests/Services
```

- [ ] **Step 4: Write the test file** (copy from Step 1 into `tests/Services/CurrencyExchangeTest.php`)

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Services/CurrencyExchangeTest.php --compact`
Expected: 3 tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add tests/Services/CurrencyExchangeTest.php
git commit -m "test: add CurrencyExchange service tests"
```

---

### Task 2: Currency model tests

**Files:**
- Create: `tests/Models/CurrencyTest.php`
- No source changes

- [ ] **Step 1: Write tests**

```php
<?php

use Jegex\LaravelPriceable\Models\Currency;

beforeEach(function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true]);
    Currency::factory()->create(['code' => 'EUR', 'is_default' => false, 'is_active' => true]);
    Currency::factory()->create(['code' => 'BTC', 'is_default' => false, 'is_active' => false]);
});

it('scope default returns only default currency', function () {
    $defaults = Currency::default()->get();

    expect($defaults)->toHaveCount(1);
    expect($defaults->first()->code)->toBe('USD');
});

it('scope active returns only active currencies', function () {
    $actives = Currency::active()->get();

    expect($actives)->toHaveCount(2);
    expect($actives->pluck('code')->toArray())->toEqual(['USD', 'EUR']);
});

it('convertTo delegates to CurrencyExchange', function () {
    $usd = Currency::where('code', 'USD')->first();
    $eur = Currency::where('code', 'EUR')->first();

    $result = $usd->convertTo($eur, 100);

    expect($result)->toBe(92.0);
});

it('casts exchange rate and booleans correctly', function () {
    $currency = Currency::factory()->create([
        'exchange_rate' => '1.5000000000',
        'is_active' => 1,
        'is_default' => 0,
    ]);

    $fresh = Currency::find($currency->id);

    expect($fresh->is_active)->toBeTrue();
    expect($fresh->is_default)->toBeFalse();
});
```

- [ ] **Step 2: Write the test file** (copy from Step 1 into `tests/Models/CurrencyTest.php`)

- [ ] **Step 3: Run tests**

Run: `vendor/bin/pest tests/Models/CurrencyTest.php --compact`
Expected: 4 tests, all PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Models/CurrencyTest.php
git commit -m "test: add Currency model tests"
```

---

### Task 3: Price model tests

**Files:**
- Create: `tests/Models/PriceTest.php`
- No source changes

- [ ] **Step 1: Write tests**

```php
<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;
use Jegex\LaravelPriceable\Tests\Models\Product;
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

beforeEach(function () {
    $this->currency = Currency::factory()->default()->create();
    $this->product = Product::create(['name' => 'Test']);
});

it('has priceable morphTo relation', function () {
    $price = $this->product->prices()->create([
        'currency_id' => $this->currency->id,
        'price' => 1000,
    ]);

    expect($price->priceable)->toBeInstanceOf(Product::class);
    expect($price->priceable->id)->toBe($this->product->id);
});

it('has currency belongsTo relation', function () {
    $price = $this->product->prices()->create([
        'currency_id' => $this->currency->id,
        'price' => 1000,
    ]);

    expect($price->currency)->toBeInstanceOf(Currency::class);
    expect($price->currency->code)->toBe('USD');
});

it('casts price to MoneyValue on retrieve', function () {
    $price = $this->product->prices()->create([
        'currency_id' => $this->currency->id,
        'price' => 2500,
    ]);

    expect($price->price)->toBeInstanceOf(MoneyValue::class);
    expect($price->price->cents)->toBe(2500);
});

it('casts compare_price as nullable MoneyValue', function () {
    $price = $this->product->prices()->create([
        'currency_id' => $this->currency->id,
        'price' => 2500,
        'compare_price' => 3000,
    ]);

    expect($price->compare_price)->toBeInstanceOf(MoneyValue::class);
    expect($price->compare_price->cents)->toBe(3000);
});

it('casts min_quantity as integer', function () {
    $price = $this->product->prices()->create([
        'currency_id' => $this->currency->id,
        'price' => 1000,
        'min_quantity' => 5,
    ]);

    expect($price->min_quantity)->toBeInt();
    expect($price->min_quantity)->toBe(5);
});
```

- [ ] **Step 2: Write the test file** (copy from Step 1 into `tests/Models/PriceTest.php`)

- [ ] **Step 3: Run tests**

Run: `vendor/bin/pest tests/Models/PriceTest.php --compact`
Expected: 5 tests, all PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Models/PriceTest.php
git commit -m "test: add Price model tests"
```

---

### Task 4: DefaultPriceFormatter tests

**Files:**
- Create: `tests/Pricing/DefaultPriceFormatterTest.php`
- No source changes

- [ ] **Step 1: Write tests**

```php
<?php

use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter;

it('calculates decimal from cents', function () {
    $currency = new Currency(['code' => 'USD', 'symbol' => '$', 'decimal_place' => 2, 'type' => 'fiat']);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    expect($formatter->decimal())->toBe(10.0);
});

it('formats fiat currency with locale', function () {
    $currency = new Currency(['code' => 'USD', 'symbol' => '$', 'decimal_place' => 2, 'type' => 'fiat']);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    expect($formatter->formatted(locale: 'en_US'))->toBe('$10.00');
});

it('formats crypto without thousand separators', function () {
    $currency = new Currency(['code' => 'BTC', 'symbol' => '₿', 'decimal_place' => 8, 'type' => 'crypto']);
    $formatter = new DefaultPriceFormatter(value: 123456789, currency: $currency);

    expect($formatter->formatted())->toBe('₿1.23456789');
});

it('formats unit price', function () {
    $currency = new Currency(['code' => 'USD', 'symbol' => '$', 'decimal_place' => 2, 'type' => 'fiat']);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency, unitQty: 3);

    expect($formatter->unitFormatted(locale: 'en_US'))->toBe('$3.33');
});

it('falls back when NumberFormatter fails', function () {
    $currency = new Currency(['code' => 'XYZ', 'symbol' => '~', 'decimal_place' => 2, 'type' => 'fiat']);
    $formatter = new DefaultPriceFormatter(value: 1000, currency: $currency);

    $result = $formatter->formatted(locale: 'en_US');

    expect($result)->toBe('~10.00');
});
```

- [ ] **Step 2: Create test directory**

```bash
mkdir -p tests/Pricing
```

- [ ] **Step 3: Write the test file** (copy from Step 1 into `tests/Pricing/DefaultPriceFormatterTest.php`)

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Pricing/DefaultPriceFormatterTest.php --compact`
Expected: 5 tests, all PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Pricing/DefaultPriceFormatterTest.php
git commit -m "test: add DefaultPriceFormatter tests"
```

---

### Task 5: PricingManager edge case tests

**Files:**
- Modify: `tests/Managers/PricingManagerTest.php`

- [ ] **Step 1: Append two new tests to the existing test file**

Add before the closing `?>` (or at end of file — Pest files don't need `?>`):

```php
it('returns default pricing when currency is reset with null', function () {
    $this->product->prices()->create([
        'currency_id' => $this->defaultCurrency->id,
        'price' => 500,
    ]);

    $response = $this->product->pricing()->currency(null)->get();

    expect($response->matched)->not->toBeNull();
    expect($response->matched->price->cents)->toBe(500);
});

it('returns empty response when no default currency exists', function () {
    // Remove all currencies
    Currency::query()->delete();

    $response = $this->product->pricing()->get();

    expect($response->matched)->toBeNull();
    expect($response->base)->toBeNull();
});
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/pest tests/Managers/PricingManagerTest.php --compact`
Expected: 14 tests, all PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Managers/PricingManagerTest.php
git commit -m "test: add PricingManager edge case tests"
```

---

### Task 6: MoneyCast fixed-code resolve test

**Files:**
- Modify: `tests/Casts/MoneyCastTest.php`

- [ ] **Step 1: Add test for fixed currency code resolve (when default currency exists)**

Append to the end of the file:

```php
it('resolves default currency when source is null and default exists', function () {
    $cast = new MoneyCast();
    $model = new stdClass;

    $result = $cast->get($model, 'price', 500, []);

    expect($result)->toBeInstanceOf(MoneyValue::class);
    expect($result->cents)->toBe(500);
    expect($result->currency->code)->toBe('USD');
});
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/pest tests/Casts/MoneyCastTest.php --compact`
Expected: 6 tests, all PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Casts/MoneyCastTest.php
git commit -m "test: add MoneyCast default currency resolve test"
```

---

### Task 7: Config defaults and ServiceProvider tests

**Files:**
- Create: `tests/ConfigTest.php`

- [ ] **Step 1: Write tests**

```php
<?php

use Jegex\LaravelPriceable\Facades\Pricing;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;

it('has priceable config defaults', function () {
    expect(config('priceable.models.price'))->toBe(Price::class);
    expect(config('priceable.models.currency'))->toBe(Currency::class);
    expect(config('priceable.tables.prices'))->toBe('prices');
    expect(config('priceable.tables.currencies'))->toBe('currencies');
    expect(config('priceable.morph_name'))->toBe('priceable');
});

it('has pricing config defaults', function () {
    expect(config('priceable.pricing.manager'))->toBe(PricingManager::class);
    expect(config('priceable.pricing.formatter'))->toBe(\Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter::class);
});

it('facade resolves pricing manager', function () {
    $manager = Pricing::for(null);

    expect($manager)->toBeInstanceOf(PricingManager::class);
});
```

- [ ] **Step 2: Write the test file** (copy from Step 1 into `tests/ConfigTest.php`)

- [ ] **Step 3: Run tests**

Run: `vendor/bin/pest tests/ConfigTest.php --compact`
Expected: 3 tests, all PASS

- [ ] **Step 4: Commit**

```bash
git add tests/ConfigTest.php
git commit -m "test: add config defaults and facade resolution tests"
```

---

### Task 8: Add default currencies to config

**Files:**
- Modify: `config/priceable.php`

- [ ] **Step 1: Add `currencies` key to config**

```php
    'currencies' => [
        [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0000000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
            'is_default' => true,
        ],
        [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.9200000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
        ],
        [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.7900000000,
            'decimal_place' => 2,
            'type' => 'fiat',
            'is_active' => true,
        ],
        [
            'code' => 'IDR',
            'name' => 'Indonesian Rupiah',
            'symbol' => 'Rp',
            'exchange_rate' => 16000.0000000000,
            'decimal_place' => 0,
            'type' => 'fiat',
            'is_active' => true,
        ],
        [
            'code' => 'BTC',
            'name' => 'Bitcoin',
            'symbol' => '₿',
            'exchange_rate' => 0.0000210000,
            'decimal_place' => 8,
            'type' => 'crypto',
            'is_active' => true,
        ],
        [
            'code' => 'ETH',
            'name' => 'Ethereum',
            'symbol' => 'Ξ',
            'exchange_rate' => 0.0003100000,
            'decimal_place' => 8,
            'type' => 'crypto',
            'is_active' => true,
        ],
    ],
```

Edit `config/priceable.php` to add this after the `'log_activity_name' => 'jegex'` line (before the closing `];`).

- [ ] **Step 2: Run tests to verify nothing broke**

Run: `vendor/bin/pest --compact`
Expected: All tests PASS (count will be ~39 now)

- [ ] **Step 3: Commit**

```bash
git add config/priceable.php
git commit -m "feat: add default currencies to config"
```

---

### Task 9: Create seed currencies command

**Files:**
- Create: `src/Commands/SeedCurrenciesCommand.php`

- [ ] **Step 1: Write the command**

```php
<?php

namespace Jegex\LaravelPriceable\Commands;

use Illuminate\Console\Command;
use Jegex\LaravelPriceable\Models\Currency;

class SeedCurrenciesCommand extends Command
{
    public $signature = 'priceable:seed-currencies
        {--force : Skip confirmation prompt}';

    public $description = 'Seed default currencies from the priceable config';

    public function handle(): int
    {
        $currencies = config('priceable.currencies');

        if (empty($currencies)) {
            $this->warn('No currencies defined in config(priceable.currencies).');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('This will truncate the currencies table and re-seed. Continue?')) {
            $this->info('Seeding cancelled.');

            return self::SUCCESS;
        }

        Currency::query()->truncate();
        Currency::insert($currencies);

        $count = count($currencies);
        $this->info("Seeded {$count} currencies successfully.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Create test directory for commands**

```bash
mkdir -p tests/Commands
```

- [ ] **Step 3: Write tests**

```php
<?php

use Jegex\LaravelPriceable\Models\Currency;

it('seeds currencies from config', function () {
    Currency::factory()->default()->create(); // pre-existing

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
```

Write to `tests/Commands/SeedCurrenciesCommandTest.php`

- [ ] **Step 4: Run tests (expect failure — command not registered yet)**

Run: `vendor/bin/pest tests/Commands/SeedCurrenciesCommandTest.php --compact`
Expected: 2 tests, FAIL (command not found)

- [ ] **Step 5: Commit the command and test**

```bash
git add src/Commands/SeedCurrenciesCommand.php tests/Commands/SeedCurrenciesCommandTest.php
git commit -m "feat: add priceable:seed-currencies command (+ tests)"
```

---

### Task 10: Register SeedCurrenciesCommand in ServiceProvider

**Files:**
- Modify: `src/LaravelPriceableServiceProvider.php`

- [ ] **Step 1: Register the new command**

Edit `src/LaravelPriceableServiceProvider.php`:

Add import:
```php
use Jegex\LaravelPriceable\Commands\SeedCurrenciesCommand;
```

Update `->hasCommand(LaravelPriceableCommand::class)` to:
```php
            ->hasCommands([
                LaravelPriceableCommand::class,
                SeedCurrenciesCommand::class,
            ]);
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/pest tests/Commands/SeedCurrenciesCommandTest.php --compact`
Expected: 2 tests, all PASS

- [ ] **Step 3: Run full test suite**

Run: `vendor/bin/pest --compact`
Expected: All tests PASS

- [ ] **Step 4: Commit**

```bash
git add src/LaravelPriceableServiceProvider.php
git commit -m "feat: register SeedCurrenciesCommand in service provider"
```

---

### Task 11: Run static analysis

- [ ] **Step 1: Run PHPStan**

Run: `vendor/bin/phpstan analyse --error-format=github`
Expected: 0 errors

- [ ] **Step 2: Fix any issues if present**

- [ ] **Step 3: Commit if any fixes needed**

---

### Task 12: Rewrite README

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Write the new README**

```markdown
# Laravel Priceable

Multi-currency price management for Laravel. Supports fiat and crypto currencies, quantity-based pricing tiers, and exchange rate conversion.

## Features

- Polymorphic pricing — attach prices to any Eloquent model
- Fiat and crypto currency support
- Quantity-based price breaks
- Exchange rate conversion between currencies
- Locale-aware formatting with `ext-intl`
- Configurable models, formatters, and pricing logic
- Activity logging for changes

## Installation

```bash
composer require jegex/laravel-priceable
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-priceable-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-priceable-config"
```

Seed the default currencies (USD, EUR, GBP, IDR, BTC, ETH):

```bash
php artisan priceable:seed-currencies
```

## Quick Start

```php
use Jegex\LaravelPriceable\Traits\HasPrices;
use Jegex\LaravelPriceable\Models\Currency;

class Product extends Model
{
    use HasPrices;
}

// Create a price in the default currency
$product = Product::create(['name' => 'Widget']);
$defaultCurrency = Currency::where('is_default', true)->first();

$product->prices()->create([
    'currency_id' => $defaultCurrency->id,
    'price' => 1999, // $19.99 in cents
]);

// Get the formatted price
$pricing = $product->pricing()->get();
echo $pricing->matched->price->formatted(); // "$19.99"
```

## Usage

### Currencies

Currencies are stored in the `currencies` table with code, name, symbol, exchange rate, decimal places, and type (fiat/crypto).

```php
use Jegex\LaravelPriceable\Models\Currency;

// Create a currency
$usd = Currency::create([
    'code' => 'USD',
    'name' => 'US Dollar',
    'symbol' => '$',
    'exchange_rate' => 1.0000000000,
    'decimal_place' => 2,
    'type' => 'fiat',
]);

// Set as default
$usd->update(['is_default' => true]);

// Query scopes
Currency::default()->get(); // only the default currency
Currency::active()->get();  // only active currencies
```

### Prices

Prices are stored as integers (cents) and cast to `MoneyValue` objects automatically.

```php
use Jegex\LaravelPriceable\Models\Currency;

$currency = Currency::where('code', 'USD')->first();

// Base price (min_quantity = 1 by default)
$product->prices()->create([
    'currency_id' => $currency->id,
    'price' => 1999,
]);

// Sale price with compare_at
$product->prices()->create([
    'currency_id' => $currency->id,
    'price' => 1499,
    'compare_price' => 1999,
]);

// Quantity-based price break
$product->prices()->create([
    'currency_id' => $currency->id,
    'price' => 1299,
    'min_quantity' => 10,
]);
```

### Retrieving Prices

Use the `PricingManager` fluent API:

```php
// Simple — uses default currency
$pricing = $product->pricing()->get();

// Specific currency
$pricing = $product->pricing()->currency('EUR')->get();

// Quantity-based pricing
$pricing = $product->pricing()->qty(10)->get();

// Chained
$pricing = $product->pricing()->currency($eur)->qty(5)->get();
```

The `PricingResponse` DTO contains:

```php
$pricing->matched;    // Price matched to quantity + currency (or null)
$pricing->base;       // Base price (min_quantity = 1) for that currency
$pricing->priceBreaks; // Collection of available price breaks
$pricing->matched->price->formatted(); // e.g. "$12.99"
```

### Currency Conversion

```php
use Jegex\LaravelPriceable\Services\CurrencyExchange;

$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();

// Via Currency model
$converted = $usd->convertTo($eur, 100); // ~92 EUR

// Via service
$service = app(CurrencyExchange::class);
$converted = $service->convert($usd, $eur, 100);
```

### Formatting

`MoneyValue::formatted()` accepts locale, decimal places, and trailing zero trimming:

```php
$price = $product->pricing()->get()->matched->price;

echo $price->formatted(locale: 'en_US'); // "$19.99"
echo $price->formatted(locale: 'id_ID'); // "Rp19.999"
echo $price->unitFormatted(locale: 'en_US'); // per-unit price
```

Crypto currencies (type: crypto) are formatted without thousand separators:

```php
// BTC with 8 decimal places
echo $btcPrice->formatted(); // "₿1.23456789"
```

## Customization

### Custom Models

Swap models in `config/priceable.php`:

```php
'models' => [
    'price' => \App\Models\Price::class,
    'currency' => \App\Models\Currency::class,
],
```

### Custom Formatter

Implement `PriceFormatterInterface` and set it in config:

```php
'pricing' => [
    'formatter' => \App\Pricing\MyFormatter::class,
],
```

### Custom Pricing Logic

Extend `PricingManager` and set it in config:

```php
'pricing' => [
    'manager' => \App\Pricing\MyManager::class,
],
```

## Testing

```bash
composer test
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: rewrite README with real usage examples"
```

---

### Task 13: Final verification

- [ ] **Step 1: Run full test suite**

Run: `vendor/bin/pest --compact`
Expected: All tests PASS

- [ ] **Step 2: Run static analysis**

Run: `vendor/bin/phpstan analyse --error-format=github`
Expected: 0 errors

- [ ] **Step 3: Run code style check**

Run: `vendor/bin/pint --test`
Expected: No style issues
