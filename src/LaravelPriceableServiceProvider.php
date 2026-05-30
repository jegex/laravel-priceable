<?php

namespace Jegex\LaravelPriceable;

use Jegex\LaravelPriceable\Commands\LaravelPriceableCommand;
use Jegex\LaravelPriceable\Commands\SeedCurrenciesCommand;
use Jegex\LaravelPriceable\Contracts\CurrencyExchangeInterface;
use Jegex\LaravelPriceable\Contracts\PricingManagerInterface;
use Jegex\LaravelPriceable\Managers\PricingManager;
use Jegex\LaravelPriceable\Services\CurrencyExchange;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelPriceableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-priceable')
            ->hasConfigFile()
            ->hasMigration('create_priceable_table')
            ->hasCommands([
                LaravelPriceableCommand::class,
                SeedCurrenciesCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(PricingManagerInterface::class, function ($app) {
            $class = $app['config']['priceable.pricing.manager'] ?? PricingManager::class;

            return new $class;
        });

        $this->app->bind(CurrencyExchangeInterface::class, CurrencyExchange::class);
    }
}
