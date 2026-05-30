<?php

namespace Jegex\LaravelPriceable\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jegex\LaravelPriceable\LaravelPriceableServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $activityMigration = include __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/create_activity_log_table.php.stub';
        $activityMigration->up();

        $priceableMigration = include __DIR__.'/../database/migrations/create_priceable_table.php.stub';
        $priceableMigration->up();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Jegex\\LaravelPriceable\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPriceableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
