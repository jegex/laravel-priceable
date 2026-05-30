<?php

namespace Jegex\LaravelPriceable;

use Jegex\LaravelPriceable\Commands\LaravelPriceableCommand;
use Jegex\LaravelPriceable\Commands\SeedCurrenciesCommand;
use Jegex\LaravelPriceable\Managers\PricingManager;
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
        $this->app->bind(PricingManager::class, function ($app) {
            $class = $app['config']['priceable.pricing.manager'] ?? PricingManager::class;

            return new $class;
        });
    }
}
