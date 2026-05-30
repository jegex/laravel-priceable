# PricingManager & Simplified HasPrices

Date: 2026-05-30

## Motivation

Align `HasPrices` trait and introduce a `PricingManager` inspired by
[Lunar](https://github.com/lunarphp/core) — a dedicated pricing resolution layer
with fluent API, quantity-based price breaks, and a structured `PricingResponse` DTO.

## Architecture

```
src/
├── DataTransferObjects/
│   └── PricingResponse.php
├── Managers/
│   └── PricingManager.php
├── Facades/
│   └── Pricing.php              # Facade → PricingManager
└── Traits/
    └── HasPrices.php             # Simplified — prices(), basePrices(), priceBreaks(), pricing()
```

**Flow:** `$product->pricing()->currency('USD')->qty(5)->get()` → `PricingResponse`

---

## 1. HasPrices Trait (Simplified)

```php
trait HasPrices
{
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function basePrices(): MorphMany
    {
        return $this->prices()->where('min_quantity', 1);
    }

    public function priceBreaks(): MorphMany
    {
        return $this->prices()->where('min_quantity', '>', 1);
    }

    public function pricing(): PricingManager
    {
        return app(Pricing::class)->for($this);
    }
}
```

Removed methods: `priceIn()`, `convertTo()`, `formattedPrice()`, `scopeWhereHasPriceIn()`.

---

## 2. PricingManager

Fluent builder for resolving the best price for a model.

```php
class PricingManager
{
    public function for(?Model $model): self
    public function currency(Currency|string|null $currency): self
    public function qty(int $qty): self
    public function get(): PricingResponse
}
```

### Price matching logic (in `get()`):

1. Default `$currency` to `Currency::where('is_default', true)->first()` if not set.
2. Filter the model's prices by `currency_id`.
3. If no prices exist → return `PricingResponse` with all null/empty.
4. **Base price** = first price where `min_quantity === 1`.
5. **Matched** starts as base price.
6. Filter price breaks where `qty >= min_quantity  AND  min_quantity > 1`, sort by price ascending, pick cheapest.
7. If a price break matches → `matched` = that price break.
8. Return `PricingResponse`.

---

## 3. PricingResponse DTO

```php
class PricingResponse
{
    public function __construct(
        public ?Price $matched,
        public ?Price $base,
        public Collection $priceBreaks,
    ) {}
}
```

- `$matched` → `null` when no prices exist for the currency.
- `$priceBreaks` → always a Collection (possibly empty).

---

## 4. Facade

```php
class Pricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jegex\LaravelPriceable\Managers\PricingManager::class;
    }
}
```

Registered in service provider. The facade is used internally by `HasPrices::pricing()` and is available publicly.

---

## Changes Overview

| Item | Change |
|------|--------|
| `src/Traits/HasPrices.php` | Rewrite — remove old methods, add `basePrices()`, `priceBreaks()`, `pricing()` |
| `src/Managers/PricingManager.php` | **New** — fluent pricing resolution |
| `src/Facades/Pricing.php` | **New** — facade for PricingManager |
| `src/DataTransferObjects/PricingResponse.php` | **New** — DTO |
| `tests/Traits/HasPricesTest.php` | Update — reflect new trait API |
| `tests/Managers/PricingManagerTest.php` | **New** — coverage for PricingManager |
| `src/LaravelPriceableServiceProvider.php` | Register PricingManager in container |
| Config / migration | No changes |

## Out of Scope (Future)

- Customer group pricing
- Pipeline/extensibility
- `compare_price` handling in PricingManager
