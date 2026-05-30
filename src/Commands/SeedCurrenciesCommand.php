<?php

namespace Jegex\LaravelPriceable\Commands;

use Illuminate\Console\Command;
use Jegex\LaravelPriceable\Models\Currency;

class SeedCurrenciesCommand extends Command
{
    public $signature = 'priceable:seed-currencies
        {--force : Skip confirmation prompt}';

    public $description = 'Seed default currencies from the priceable config';

    public function handle(): int
    {
        $currencies = config('priceable.currencies');

        if (empty($currencies)) {
            $this->warn('No currencies defined in config(priceable.currencies).');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('This will truncate the currencies table and re-seed. Continue?')) {
            $this->info('Seeding cancelled.');

            return self::SUCCESS;
        }

        Currency::query()->truncate();
        Currency::insert($currencies);

        $count = count($currencies);
        $this->info("Seeded {$count} currencies successfully.");

        return self::SUCCESS;
    }
}
