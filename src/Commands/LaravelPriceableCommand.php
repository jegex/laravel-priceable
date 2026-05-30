<?php

namespace Jegex\LaravelPriceable\Commands;

use Illuminate\Console\Command;

class LaravelPriceableCommand extends Command
{
    public $signature = 'laravel-priceable';

    public $description = 'Laravel Priceable package command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
