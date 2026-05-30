# laravel-priceable

Multi-currency price management package for Laravel — Spatie package-tools skeleton, early stage.

## Commands

| Purpose | Command |
|---------|---------|
| Run all tests | `composer test` |
| Run static analysis (level 5) | `composer analyse` |
| Format code | `composer format` |
| Prepare testbench | `composer prepare` |

- `post-autoload-dump` automatically runs `prepare` — expect a delay on `composer install`/`update`.
- CI runs PHPStan via `./vendor/bin/phpstan --error-format=github` and tests via `vendor/bin/pest --ci`.

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
│   └── MoneyCast.php               # Generic Eloquent cast: int cents ↔ MoneyValue
├── Commands/
│   └── LaravelPriceableCommand.php # php artisan laravel-priceable
├── Facades/
│   └── LaravelPriceable.php        # Facade → LaravelPriceable class
├── Models/
│   ├── Currency.php                 # code, name, symbol, exchange_rate, decimal_place, type, is_active, is_default
│   └── Price.php                    # Polymorphic: priceable, currency_id, price, compare_price, min_quantity
├── Traits/
│   └── HasPrices.php                # MorphMany prices(), priceIn(), convertTo(), formattedPrice()
├── ValueObjects/
│   └── MoneyValue.php               # cents, currency, unitQuantity → amount(), formatted()
├── LaravelPriceable.php             # Root class
└── LaravelPriceableServiceProvider.php
```

- Service provider uses `Spatie\LaravelPackageTools\PackageServiceProvider` — do NOT manually register things in `boot()`/`register()` unless `configurePackage` cannot express it.
- PSR-4: `Jegex\LaravelPriceable\` → `src/`, `Jegex\LaravelPriceable\Tests\` → `tests/`.
- Migration stubs in `database/migrations/*.stub`. Config publishes as `priceable.php`.

## Models

- **Currency**: exchange_rate decimal(20,10) relative to default currency. `type` enum(fiat, crypto). Seeded from `config/priceable.php`.
- **Price**: Polymorphic morphs (`priceable_id`, `priceable_type`). Prices stored as **integer cents** (bigint). Cast `price` and `compare_price` via `MoneyCast::class.':currency'`.
- **HasPrices** trait: attach `prices()` relation, `priceIn(Currency|string)`, `convertTo(?string)`, `formattedPrice(?string)`, `scopeWhereHasPriceIn()`.

## MoneyCast & MoneyValue

- **MoneyCast**: generic Eloquent `CastsAttributes`. Constructor parameter for currency source (relation name like `currency`, or fixed code like `USD`). `get()` → `?MoneyValue`, `set()` → `?int`. Reusable across models (Price, Order, etc).
- **MoneyValue**: `int $cents`, `Currency $currency`, `int $unitQuantity = 1`. Methods: `amount()` (float), `formatted()` (string with symbol), `__toString()`.

## Dependencies

- `spatie/laravel-activitylog` — for logging exchange rate and price changes.
- `spatie/laravel-package-tools` — service provider base.

## Code style

- Laravel Pint (`vendor/bin/pint`) — 4-space indent, trailing whitespace trimmed, final newline.
- PHPStan level 5 on `src/`, `config/`, `database/`. Baseline in `phpstan-baseline.neon` (currently empty).

## Constraints

- PHP ^8.4 required.
- No `dd()`, `dump()`, `ray()` — caught by arch test and CI.
- Build artifacts in `/build` (gitignored), cache in `.phpunit.cache`.
- Avoid hardcoding paths — use `__DIR__` relative to the package root or `config()`.
- Factory naming: `Jegex\LaravelPriceable\Database\Factories\{Model}Factory`.
