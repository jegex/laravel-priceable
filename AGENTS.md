# laravel-priceable

Multi-currency price management package for Laravel — fiat & crypto, quantity breaks, exchange rate conversion.

## Commands

| Purpose | Command |
|---------|---------|
| Run all tests | `composer test` |
| Run static analysis (level 5) | `composer analyse` |
| Format code | `composer format` |
| Prepare testbench | `composer prepare` |

- `post-autoload-dump` automatically runs `prepare` — expect a delay on `composer install`/`update`.
- CI runs PHPStan via `./vendor/bin/phpstan --error-format=github` and tests via `vendor/bin/pest --ci`.

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan laravel-priceable` | Display package info and currency summary |
| `php artisan priceable:seed-currencies` | Seed default currencies from `config/priceable.currencies` |

## Testing

- Pest PHP v4. All tests in `tests/`. Random execution order.
- `TestCase` extends `Orchestra\Testbench\TestCase`. Registers `LaravelPriceableServiceProvider`.
- Arch test enforces no `dd`, `dump`, `ray` usage.
- Test database defaults to `:memory:` SQLite (`database.default = testing`).
- CI matrix: PHP 8.3–8.5, Laravel 12–13, prefer-lowest/prefer-stable, ubuntu + windows.

## Architecture

```
src/
├── Casts/
│   └── MoneyCast.php                 # Generic Eloquent cast: int cents ↔ MoneyValue
├── Commands/
│   ├── LaravelPriceableCommand.php   # php artisan laravel-priceable (info/status)
│   └── SeedCurrenciesCommand.php     # php artisan priceable:seed-currencies
├── Contracts/
│   └── Priceable.php               # Interface: prices(): MorphMany
├── DataTransferObjects/
│   └── PricingResponse.php           # DTO: matched, base, priceBreaks
├── Facades/
│   └── Pricing.php                   # Facade → PricingManager
├── Managers/
│   └── PricingManager.php            # Fluent API: for(), currency(), qty(), get()
├── Models/
│   ├── Currency.php                  # code, name, symbol, exchange_rate, decimal_place, type, is_active, is_default
│   └── Price.php                     # Polymorphic: priceable, currency_id, price, compare_price, min_quantity
├── Pricing/
│   └── DefaultPriceFormatter.php     # PriceFormatterInterface impl: decimal(), formatted()
├── Services/
│   └── CurrencyExchange.php          # convert(Currency $from, Currency $to, int|float $amount)
├── Traits/
│   └── HasPrices.php                 # MorphMany prices(), basePrices(), priceBreaks(), pricing()
├── ValueObjects/
│   └── MoneyValue.php                # cents, currency, unitQty → decimal(), amount(), formatted()
└── LaravelPriceableServiceProvider.php
```

- Service provider uses `Spatie\LaravelPackageTools\PackageServiceProvider` — do NOT manually register things in `boot()`/`register()` unless `configurePackage` cannot express it.
- PSR-4: `Jegex\LaravelPriceable\` → `src/`, `Jegex\LaravelPriceable\Tests\` → `tests/`.
- Migration stubs in `database/migrations/*.stub`. Config publishes as `priceable.php`.
- The root class `LaravelPriceable.php` and its facade were removed in the refactor. The facade `Pricing` proxies directly to `PricingManager`.

## Models

- **Currency**: exchange_rate decimal(20,10) relative to default currency. `type` enum(fiat, crypto). Seeded from `config/priceable.php`. Includes `LogsActivity` for change tracking.
- **Price**: Polymorphic morphs (`priceable_id`, `priceable_type`). Prices stored as **integer cents** (bigint). Cast `price` and `compare_price` via `MoneyCast::class.':currency'`. `min_quantity` for tiered pricing.
- **HasPrices** trait: `prices()` (MorphMany), `basePrices()` (min_qty=1), `priceBreaks()` (min_qty>1), `pricing()` (PricingManager fluent API).
- **Priceable** contract: models using `HasPrices` should implement `Priceable` interface.

## PricingManager

- **PricingManager**: fluent static API. `Pricing::for($model)->currency($currency)->qty(5)->get()`.
- **PricingResponse**: DTO with `$matched` (best price), `$base` (base price), `$priceBreaks` (collection of breaks).
- **Pricing facade** (`\Jegex\LaravelPriceable\Facades\Pricing`): proxies to `PricingManager`.

## MoneyCast & MoneyValue

- **MoneyCast**: generic Eloquent `CastsAttributes`. Constructor parameter for currency source (relation name like `currency`, or fixed code like `USD`). `get()` → `?MoneyValue`, `set()` → `?int`. Reusable across models (Price, Order, etc).
- **MoneyValue**: `int $cents`, `Currency $currency`, `int $unitQty = 1`. Methods: `decimal()` (float), `amount()` (decimal * qty), `formatted()` (string with symbol), `unitFormatted()`, `__toString()`.

## CurrencyExchange

- **CurrencyExchange**: `convert(Currency $from, Currency $to, int|float $amount)` — same-currency passthrough, cross-currency via `(amount / from.rate) * to.rate`.

## Dependencies

- `spatie/laravel-activitylog` — for logging exchange rate and price changes.
- `spatie/laravel-package-tools` — service provider base.
- `ext-intl` — locale-aware currency formatting via `NumberFormatter`.

## Code style

- Laravel Pint (`vendor/bin/pint`) — 4-space indent, trailing whitespace trimmed, final newline.
- PHPStan level 5 on `src/`, `config/`, `database/`. Baseline in `phpstan-baseline.neon` (currently empty).

## Constraints

- PHP ^8.4 required.
- No `dd()`, `dump()`, `ray()` — caught by arch test and CI.
- Build artifacts in `/build` (gitignored), cache in `.phpunit.cache`.
- Avoid hardcoding paths — use `__DIR__` relative to the package root or `config()`.
- Factory naming: `Jegex\LaravelPriceable\Database\Factories\{Model}Factory`.
