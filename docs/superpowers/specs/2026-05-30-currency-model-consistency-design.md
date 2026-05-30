# Currency Model Consistency

**Date:** 2026-05-31
**Status:** Draft

## Problem

The package config allows customizing the Currency model via `config('priceable.models.currency')`, but most files import and use `Jegex\LaravelPriceable\Models\Currency` directly. This means:

1. **Queries bypass the config** — `Currency::where(...)`, `Currency::all()`, `Currency::factory()`, etc. always use the hardcoded class, not the customized one.
2. **No single source of truth** — files that do read from config repeat `config('priceable.models.currency', Currency::class)` in multiple places, risking inconsistencies.

## Assumption

Users customize Currency by extending the package class:

```php
class MyCurrency extends \Jegex\LaravelPriceable\Models\Currency
{
    // custom fields, relationships, etc.
}
```

Type-hints (`Currency $currency`, `Currency|string`, `protected ?Currency $currency`) remain compatible via inheritance and do NOT need to change.

## Solution: Centralized Resolver

Create a single helper function `priceable_currency_model()` as the only entry point for resolving the Currency model class.

### Helper function

`src/helpers.php`:

```php
if (! function_exists('Jegex\LaravelPriceable\priceable_currency_model')) {
    function priceable_currency_model(): string
    {
        return config('priceable.models.currency', \Jegex\LaravelPriceable\Models\Currency::class);
    }
}
```

Autoload via `composer.json`:

```json
"autoload": {
    "files": ["src/helpers.php"]
}
```

### Changes per file

| File | Current | After |
|------|---------|-------|
| `HasPrices` | `config('...', Currency::class)` | `priceable_currency_model()` |
| `PricingManager` (2 places) | `config('...', Currency::class)` | `priceable_currency_model()` |
| `Price` | `config('...', Currency::class)` | `priceable_currency_model()` |
| `MoneyCast` (2 queries) | `Currency::where('code', ...)` | `priceable_currency_model()::where(...)` |
| `LaravelPriceableCommand` | `Currency::all()` | `priceable_currency_model()::all()` |
| `SeedCurrenciesCommand` (2 lines) | `Currency::query()->truncate()`, `Currency::insert(...)` | via resolver |
| `UpdateExchangeRatesCommand` (2 queries) | `Currency::where(is_default: true)` and `where(is_default: false, is_active: true)` | via resolver |
| `PriceFactory` | `Currency::factory()` | `priceable_currency_model()::factory()` |
| `CurrencyFactory` | `$model = Currency::class` | tetap hardcode (static property — tidak bisa dinamis) |

### Files NOT changed

- **Contracts** (`PricingManagerInterface`, `CurrencyExchangeInterface`) — type-hints tetap `Currency`. Kompatibel via inheritance.
- **ValueObjects** (`MoneyValue`) — `public readonly Currency $currency`. Kompatibel via inheritance.
- **Services** (`CurrencyExchange`, `DefaultPriceFormatter`) — parameter type-hints tetap `Currency`.
- **Factory** (`CurrencyFactory`) — `$model = Currency::class` tetap hardcode. Ini adalah factory bawaan; user yang extend Currency bikin factory sendiri.

### Test implications

- Semua test tetap jalan — tidak ada perubahan API publik.
- Tidak perlu test baru untuk helper — lifecycle test yang sudah ada sudah mencakup flow yang menggunakan model.

### Future considerations

- Jika nanti ada paket lain yang perlu resolve model class dengan pola yang sama, helper ini bisa dijadikan method static di service provider.
