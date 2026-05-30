# Beta Release: laravel-priceable

## Goal

Push `jegex/laravel-priceable` from Alpha to Beta by delivering three workstreams:
- **H**: Comprehensive test coverage for the existing public API
- **C**: Currency seeder (config-driven + artisan command)
- **A**: Production-quality README documentation

---

## H — Test Coverage

### Principles

- Tests document behavior, not implementation. Assert on public API, not internals.
- Each component gets its own test file mirroring `src/` structure.
- Use Pest PHP 4 throughout. Factories already exist for Currency and Price.
- Coverage target: >90% on `src/` (informational, no hard gate).

### Test plan (12–15 new tests, ~100–120 assertions)

#### 1. `CurrencyExchangeTest.php` (new file, 3 tests)
| Test | What it verifies |
|------|-----------------|
| `convert()` same currency | Returns amount unchanged when `$from->is($to)` |
| `convert()` cross-currency | `(amount / from.rate) * to.rate` — e.g. USD 100 → IDR 1,600,000 |
| `convert()` with crypto rates | Floating point precision within acceptable tolerance |

#### 2. `CurrencyTest.php` (new file, 4 tests)
| Test | What it verifies |
|------|-----------------|
| `scopeDefault()` | Returns only default currency |
| `scopeActive()` | Excludes inactive currencies |
| `convertTo()` | Delegates to CurrencyExchange, returns correct amount |
| model casts | `exchange_rate` stored/retrieved as decimal, `is_active`/`is_default` as boolean |

#### 3. `PriceTest.php` (new file, 3 tests)
| Test | What it verifies |
|------|-----------------|
| `priceable()` morphTo relation | Returns related model |
| `currency()` belongsTo relation | Returns Currency instance |
| casts on retrieve/save | `price` returns MoneyValue via MoneyCast, `compare_price` nullable, `min_quantity` integer |

#### 4. `DefaultPriceFormatterTest.php` (new file, 4 tests)
| Test | What it verifies |
|------|-----------------|
| `decimal()` fiat | 1000 cents, USD, dp=2 → 10.00 |
| `formatted()` crypto | ₿ with 8 dp, no thousand separators |
| `formatted()` NumberFormatter fallback | Non-existent locale falls back to `symbol . number_format()` |
| `unitFormatted()` | 1000 cents, qty 3 → $3.33/unit |

#### 5. `PricingManagerTest.php` additions (2 tests to existing)
| Test | What it verifies |
|------|-----------------|
| `currency(null)` clears/resets to default | get() uses default after reset |
| No default currency exists | get() returns empty PricingResponse gracefully |

#### 6. `MoneyCastTest.php` additions (1 test to existing)
| Test | What it verifies |
|------|-----------------|
| Fixed code resolve (e.g. `'USD'`) | MoneyCast with `MoneyCast::class.':USD'` resolves Currency by code |

#### 7. ServiceProvider/config tests (2 new files, 2 tests)
| Test | What it verifies |
|------|-----------------|
| Config defaults | `config('priceable.models.price')` returns `Price::class`, etc. |
| Facade resolves | `Pricing::for()` returns `PricingManager` instance |

---

## C — Currency Seeder

### Design

Config-driven + Artisan command. Currency definitions live in config (single source of truth), command reads & inserts.

### Config addition (`config/priceable.php`)

```php
'currencies' => [
    [
        'code'           => 'USD',
        'name'           => 'US Dollar',
        'symbol'         => '$',
        'exchange_rate'  => 1.0000000000,
        'decimal_place'  => 2,
        'type'           => 'fiat',
        'is_active'      => true,
        'is_default'     => true,
    ],
    [
        'code'           => 'EUR',
        'name'           => 'Euro',
        'symbol'         => '€',
        'exchange_rate'  => 0.9200000000,
        'decimal_place'  => 2,
        'type'           => 'fiat',
        'is_active'      => true,
    ],
    [
        'code'           => 'GBP',
        'name'           => 'British Pound',
        'symbol'         => '£',
        'exchange_rate'  => 0.7900000000,
        'decimal_place'  => 2,
        'type'           => 'fiat',
        'is_active'      => true,
    ],
    [
        'code'           => 'IDR',
        'name'           => 'Indonesian Rupiah',
        'symbol'         => 'Rp',
        'exchange_rate'  => 16000.0000000000,
        'decimal_place'  => 0,
        'type'           => 'fiat',
        'is_active'      => true,
    ],
    [
        'code'           => 'BTC',
        'name'           => 'Bitcoin',
        'symbol'         => '₿',
        'exchange_rate'  => 0.0000210000,
        'decimal_place'  => 8,
        'type'           => 'crypto',
        'is_active'      => true,
    ],
    [
        'code'           => 'ETH',
        'name'           => 'Ethereum',
        'symbol'         => 'Ξ',
        'exchange_rate'  => 0.0003100000,
        'decimal_place'  => 8,
        'type'           => 'crypto',
        'is_active'      => true,
    ],
],
```

### New command: `php artisan priceable:seed-currencies`

| Aspect | Detail |
|--------|--------|
| Signature | `priceable:seed-currencies {--force : Skip confirmation}` |
| Behavior | Truncates `currencies` table, then bulk-inserts from config |
| Safety | Asks confirmation unless `--force` |
| Idempotent | Yes — can be re-run safely |

A publishable seeder stub is unnecessary for Beta; the command is sufficient. Can add later if users request.

### Test for seeder (2 tests)
| Test | What it verifies |
|------|-----------------|
| Command inserts currencies | Count matches config, default currency flag set |
| `--force` skips confirmation | Runs without interaction |

---

## A — README Documentation

### Structure

```
# Laravel Priceable

> Multi-currency price management for Laravel. Fiat + crypto, quantity breaks, exchange rate conversion.

## Features
- Fiat and crypto currency support
- Quantity-based pricing tiers
- Polymorphic pricing (attach prices to any model)
- Exchange rate conversion
- Configurable formatting (locale-aware, crypto)
- Activity logging for changes

## Installation
```bash
composer require jegex/laravel-priceable
php artisan vendor:publish --tag="laravel-priceable-migrations"
php artisan migrate
php artisan vendor:publish --tag="laravel-priceable-config"
php artisan priceable:seed-currencies
```

## Quick Start
```php
use Jegex\LaravelPriceable\Traits\HasPrices;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Models\Price;

class Product extends Model
{
    use HasPrices;
}

// Create a price
$product->prices()->create([
    'currency_id' => Currency::where('is_default', true)->first()->id,
    'price' => 1999, // $19.99 in cents
]);

// Get formatted price
$pricing = $product->pricing()->get();
echo $pricing->matched->price->formatted(); // "$19.99"
```

## Usage (sections with code blocks only)
### Currencies
### Prices
### Pricing Manager
### Quantity Breaks
### Currency Conversion
### Formatting

## Customization
### Custom Models
### Custom Formatter
### Custom Pricing Logic

## Testing
```bash
composer test
composer analyse
```

## Changelog / Contributing / License
```

### Key decisions
- No explanatory paragraphs after code blocks — code is self-documenting with comments where needed
- Every public API method gets at least one usage example
- Uses real values (1999 cents, "$19.99") not placeholder "foo" / "bar"
- README is the source of truth for the API surface

---

## Dependencies on `minimum-stability`

`minimum-stability: dev` stays during Beta. Flip to `stable` only at stable release.

---

## Out of scope (for this Beta push)

- AGENTS.md update (will do alongside code changes)
- CHANGELOG population
- Artisan `laravel-priceable` command rewrite
- Convenience methods back to HasPrices
- CI workflow improvements
- Migration schema testing
- Integration/E2E tests
