# Laravel Priceable

Multi-currency price management for Laravel. Supports fiat and crypto currencies, quantity-based pricing tiers, and exchange rate conversion.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jegex/laravel-priceable.svg?style=flat-square)](https://packagist.org/packages/jegex/laravel-priceable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jegex/laravel-priceable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jegex/laravel-priceable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jegex/laravel-priceable/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jegex/laravel-priceable/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jegex/laravel-priceable.svg?style=flat-square)](https://packagist.org/packages/jegex/laravel-priceable)

## Features

- Polymorphic pricing — attach prices to any Eloquent model
- Fiat and crypto currency support
- Quantity-based price breaks
- Exchange rate conversion between currencies
- Locale-aware formatting with `ext-intl`
- Configurable models, formatters, and pricing logic
- Activity logging for changes
- Seamless casting with `MoneyCast` — store cents, work with value objects

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

class Product extends Model
{
    use HasPrices;
}

$product = Product::create(['name' => 'Widget']);
$pricing = $product->pricing()->get();

echo $pricing->matched->price->formatted(); // "$19.99"
```

## Models & Contracts

### Purchasable Interface

Models using `HasPrices` should implement the `Purchasable` contract for type safety:

```php
use Jegex\LaravelPriceable\Contracts\Purchasable;
use Jegex\LaravelPriceable\Traits\HasPrices;

class Product extends Model implements Purchasable
{
    use HasPrices;
}
```

The contract requires a single method — `prices(): MorphMany` — which `HasPrices` already provides.

### HasPrices Trait

The trait provides the following methods:

| Method | Returns | Description |
|--------|---------|-------------|
| `prices()` | `MorphMany` | All prices (polymorphic) |
| `basePrices()` | `MorphMany` | Prices where `min_quantity = 1` |
| `priceBreaks()` | `MorphMany` | Prices where `min_quantity > 1` |
| `pricing()` | `PricingManager` | Fluent pricing API |
| `priceIn($currency)` | `?Price` | Base price for a currency |
| `convertTo($target, $qty)` | `?MoneyValue` | Matched price converted to target currency |
| `formattedPrice($locale, $currency)` | `?string` | Formatted price string |

## Currencies

### Creating Currencies

```php
use Jegex\LaravelPriceable\Models\Currency;

$usd = Currency::create([
    'code'           => 'USD',
    'name'           => 'US Dollar',
    'symbol'         => '$',
    'exchange_rate'  => 1.0000000000,
    'decimal_place'  => 2,
    'type'           => 'fiat',
]);
```

### Scopes

```php
Currency::default()->get(); // only the default currency
Currency::active()->get();  // only active currencies
```

### Observer Rules

The model applies two rules automatically:

- **Default must be active** — setting `is_default = true` forces `is_active = true`. You cannot deactivate the default currency.
- **Single default** — when a currency is marked as default, all others are unmarked.

```php
$currency = Currency::factory()->default()->create();

$currency->update(['is_active' => false]);
$currency->fresh()->is_active; // true — default can't be inactive

$other->update(['is_default' => true]);
$currency->fresh()->is_default; // false — only one default
```

### Factory States

```php
Currency::factory()->default()->create(); // is_default = true
Currency::factory()->crypto('BTC')->create(); // crypto type config
```

See `config/priceable.php` for the full list of seeded currencies.

## Prices

Prices are stored in **cents** (integer) using the `MoneyCast` which hydrates into a `MoneyValue` object.

```php
$product->prices()->create([
    'currency_id' => $currency->id,
    'price'        => 1999, // $19.99
]);

// Sale price with compare-at
$product->prices()->create([
    'currency_id'   => $currency->id,
    'price'         => 1499,
    'compare_price' => 1999, // original price for reference
]);

// Quantity price break
$product->prices()->create([
    'currency_id'  => $currency->id,
    'price'        => 1299,
    'min_quantity' => 10,
]);
```

| Column | Type | Description |
|--------|------|-------------|
| `price` | `MoneyCast` (cents) | The selling price |
| `compare_price` | `MoneyCast` (cents) | Optional compare-at / original price |
| `min_quantity` | int (default 1) | Minimum quantity for this price tier |
| `currency_id` | FK → currencies | The currency for this price |

### Factory States

```php
Price::factory()->sale()->create(); // with compare_price = price × 2
```

## MoneyCast

The `MoneyCast` attribute cast stores cents as integers and hydrates them as `MoneyValue` objects. It accepts the currency source as a constructor parameter:

```php
protected function casts(): array
{
    return [
        'price'         => MoneyCast::class.':currency', // from relation
        'compare_price' => MoneyCast::class.':currency', // from relation
        // or with a fixed code:
        // 'amount'    => MoneyCast::class.':USD',
    ];
}
```

When the source is a relation name (e.g. `currency`), it resolves the currency from the `currency()` relationship. When it's a code string (e.g. `USD`), it uses that fixed currency.

## Value Objects: MoneyValue

The `MoneyValue` object is the heart of price representation:

```php
use Jegex\LaravelPriceable\ValueObjects\MoneyValue;

$money = new MoneyValue(
    cents: 1999,
    currency: $currency,
    unitQty: 1
);

$money->decimal();       // 19.99 (total)
$money->unitDecimal();   // 19.99 (per unit)
$money->amount();        // 19.99 (decimal × unitQty)
$money->formatted();     // "$19.99"
$money->unitFormatted(); // "$19.99"
echo $money;             // "1999"
```

## Pricing Manager

The `PricingManager` provides a fluent API for resolving the best price for a given currency and quantity:

```php
use Jegex\LaravelPriceable\Facades\Pricing;

// Simple — uses default currency
$pricing = $product->pricing()->get();

// Specific currency (by code string or Currency model)
$pricing = $product->pricing()->currency('EUR')->get();
$pricing = $product->pricing()->currency($eur)->get();

// Quantity-based pricing
$pricing = $product->pricing()->qty(10)->get();

// Chained
$pricing = $product->pricing()->currency($eur)->qty(5)->get();
```

### PricingResponse DTO

The `get()` method returns a `PricingResponse`:

| Property | Type | Description |
|----------|------|-------------|
| `matched` | `?Price` | Best price for the requested quantity and currency |
| `base` | `?Price` | Base price (min_quantity = 1) |
| `priceBreaks` | `Collection` | All quantity-break prices (min_quantity > 1) |

```php
$pricing->matched->price->formatted();      // "$12.99"
$pricing->matched->price->unitFormatted();  // "$1.30" (per unit)
$pricing->base->price->formatted();         // "$19.99"
count($pricing->priceBreaks);               // 2
```

When no price exists for the exact quantity, the highest tier that is ≤ the requested quantity is used. If no tier matches, the base price is returned.

### Convenience Methods

Direct accessors on your model:

```php
// Get the base price for a currency
$price = $product->priceIn('EUR');
$price = $product->priceIn($currency);

// Convert matched price to another currency
$money = $product->convertTo('EUR', qty: 5);
$money = $product->convertTo($currency, qty: 1);

// Get formatted price string
echo $product->formattedPrice(locale: 'en_US');
echo $product->formattedPrice(currency: 'EUR');
echo $product->formattedPrice(locale: 'id_ID', currency: 'IDR'); // "Rp16.000"
```

## Currency Conversion

```php
use Jegex\LaravelPriceable\Services\CurrencyExchange;

$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();

// Via model
$converted = $usd->convertTo($eur, 100); // ~92 EUR

// Via service
$service = app(CurrencyExchange::class);
$converted = $service->convert($usd, $eur, 100);
```

Conversion uses the formula: `(amount / from.rate) × to.rate`. Same-currency conversion passes through directly.

## Formatting

### Price Formatter

```php
$price = $product->pricing()->get()->matched->price;

echo $price->formatted(locale: 'en_US');       // "$19.99"
echo $price->formatted(locale: 'id_ID');       // "Rp19.999"
echo $price->unitFormatted(locale: 'en_US');   // per-unit price

// Advanced options
$price->formatted(
    locale: 'en_US',
    formatterStyle: NumberFormatter::CURRENCY,
    decimalPlaces: 4,
    trimTrailingZeros: true
);
```

### PriceFormatterInterface

The `DefaultPriceFormatter` implements `PriceFormatterInterface`:

| Method | Returns | Description |
|--------|---------|-------------|
| `decimal()` | `float` | Cents to decimal (total for unitQty) |
| `unitDecimal()` | `float` | Cents to decimal (per unit) |
| `formatted()` | `string` | Locale-aware formatted string (total) |
| `unitFormatted()` | `string` | Locale-aware formatted string (per unit) |

Crypto currencies are formatted without thousand separators:

```php
echo $btcPrice->formatted(); // "₿1.23456789"
```

## Artisan Commands

### `php artisan laravel-priceable`

Display package information and a summary of all currencies:

```
Laravel Priceable
=================

  Total currencies: 6
  • Fiat: 4
  • Crypto: 2
  Default currency: USD

  USD ........ $ ........ US Dollar ........... 1.0000000000
  EUR ........ € ........ Euro ................ 0.9200000000
  ...
```

### `php artisan priceable:seed-currencies`

Seed or reset currencies from `config/priceable.currencies`.

```bash
php artisan priceable:seed-currencies --force  # skip confirmation
```

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag="laravel-priceable-config"
```

Key options in `config/priceable.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `models.price` | `Price::class` | Custom Price model |
| `models.currency` | `Currency::class` | Custom Currency model |
| `tables.prices` | `prices` | Prices table name |
| `tables.currencies` | `currencies` | Currencies table name |
| `morph_name` | `priceable` | Morph name for polymorphic relation |
| `pricing.manager` | `PricingManager::class` | Custom pricing manager |
| `pricing.response` | `PricingResponse::class` | Custom pricing response DTO |
| `pricing.formatter` | `DefaultPriceFormatter::class` | Custom price formatter |
| `log_activity_name` | `jegex` | Activity log name |
| `currencies` | array of 6 | Default currencies to seed |

## Customization

### Custom Models

Extend the base models and update the config:

```php
// config/priceable.php
'models' => [
    'price' => \App\Models\Price::class,
    'currency' => \App\Models\Currency::class,
],
```

### Custom Formatter

Implement `PriceFormatterInterface` and register it:

```php
use Jegex\LaravelPriceable\Contracts\PriceFormatterInterface;

class MyFormatter implements PriceFormatterInterface
{
    public function decimal(bool $rounding = true): float { /* ... */ }
    public function unitDecimal(bool $rounding = true): float { /* ... */ }
    public function formatted(?string $locale = null, int $formatterStyle = NumberFormatter::CURRENCY, ?int $decimalPlaces = null, bool $trimTrailingZeros = true): string { /* ... */ }
    public function unitFormatted(?string $locale = null, int $formatterStyle = NumberFormatter::CURRENCY, ?int $decimalPlaces = null, bool $trimTrailingZeros = true): string { /* ... */ }
}
```

```php
// config/priceable.php
'pricing' => [
    'formatter' => \App\Pricing\MyFormatter::class,
],
```

### Custom Pricing Logic

Extend `PricingManager`:

```php
// config/priceable.php
'pricing' => [
    'manager' => \App\Pricing\MyManager::class,
],
```

## Testing

```bash
composer test
```

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [jegex](https://github.com/jegex)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
