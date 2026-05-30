<?php

namespace Jegex\LaravelPriceable;

use Jegex\LaravelPriceable\Commands\LaravelPriceableCommand;
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
            ->hasCommand(LaravelPriceableCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PricingManager::class);
    }
}
