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

$product = Product::create(['name' => 'Widget']);
$defaultCurrency = Currency::where('is_default', true)->first();

$product->prices()->create([
    'currency_id' => $defaultCurrency->id,
    'price' => 1999, // $19.99 in cents
]);

$pricing = $product->pricing()->get();
echo $pricing->matched->price->formatted(); // "$19.99"
```

## Usage

### Currencies

```php
use Jegex\LaravelPriceable\Models\Currency;

$usd = Currency::create([
    'code' => 'USD',
    'name' => 'US Dollar',
    'symbol' => '$',
    'exchange_rate' => 1.0000000000,
    'decimal_place' => 2,
    'type' => 'fiat',
]);

$usd->update(['is_default' => true]);

Currency::default()->get(); // only the default currency
Currency::active()->get();  // only active currencies
```

### Prices

```php
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

### Pricing Manager

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

The `PricingResponse` contains:

```php
$pricing->matched;             // Price matched to quantity + currency
$pricing->base;                // Base price (min_quantity = 1)
$pricing->priceBreaks;         // Collection of available price breaks
$pricing->matched->price->formatted(); // "$12.99"
```

### Currency Conversion

```php
use Jegex\LaravelPriceable\Services\CurrencyExchange;

$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();

$converted = $usd->convertTo($eur, 100); // ~92 EUR

$service = app(CurrencyExchange::class);
$converted = $service->convert($usd, $eur, 100);
```

### Formatting

```php
$price = $product->pricing()->get()->matched->price;

echo $price->formatted(locale: 'en_US');       // "$19.99"
echo $price->formatted(locale: 'id_ID');       // "Rp19.999"
echo $price->unitFormatted(locale: 'en_US');   // per-unit price
```

Crypto currencies are formatted without thousand separators:

```php
echo $btcPrice->formatted(); // "₿1.23456789"
```

## Customization

### Custom Models

```php
// config/priceable.php
'models' => [
    'price' => \App\Models\Price::class,
    'currency' => \App\Models\Currency::class,
],
```

### Custom Formatter

Implement `PriceFormatterInterface`:

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
