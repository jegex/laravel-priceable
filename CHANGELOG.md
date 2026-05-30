# Changelog

All notable changes to `laravel-priceable` will be documented in this file.

## v0.2.0 (Beta) - 2026-05-30

### Added
- Test coverage: CurrencyExchange, Currency model, Price model, DefaultPriceFormatter, PricingManager edge cases, MoneyCast edge cases, config defaults
- Currency seeder: `php artisan priceable:seed-currencies` command with 6 default currencies (USD, EUR, GBP, IDR, BTC, ETH)
- `laravel-priceable` command now displays package info and currency summary
- `CurrencyExchange` service class for cross-currency conversion
- `PricingManager` with fluent API (`for()`, `currency()`, `qty()`, `get()`)
- `PricingResponse` DTO

### Changed
- PricingManager from singleton to bind (prevents state leaks across requests)
- HasPrices simplified: `prices()`, `basePrices()`, `priceBreaks()`, `pricing()` (removed `priceIn()`, `convertTo()`, `formattedPrice()`)
- Migrated from root `LaravelPriceable` class to facade pointing to `PricingManager`
- Models, formatters, pricing manager configurable via `config/priceable.php`

### Fixed
- State leak in PricingManager (switched from singleton to bind)
- PHPStan level 5 type errors
- PriceFactory min_quantity default value

## v0.1.0 (Alpha) - 2026-05-01

### Added
- Initial package scaffold (Spatie package-tools)
- Currency and Price Eloquent models
- MoneyCast for automatic int↔MoneyValue casting
- MoneyValue value object with formatting
- HasPrices trait for polymorphic pricing
- Purchasable contract
- Activity logging via spatie/laravel-activitylog
- Config file with model/table customization
- Migration stubs for currencies and prices tables
