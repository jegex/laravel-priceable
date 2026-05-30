<?php

namespace Jegex\LaravelPriceable\Commands;

use Illuminate\Console\Command;
use Jegex\LaravelPriceable\Services\ExchangeRateService;
use function Jegex\LaravelPriceable\priceable_currency_model;

class UpdateExchangeRatesCommand extends Command
{
    public $signature = 'priceable:update-exchange-rates
        {--dry-run : Display rates that would be updated without making changes}';

    public $description = 'Update exchange rates from the free exchange rate API';

    public function handle(ExchangeRateService $service): int
    {
        $class = priceable_currency_model();
        $default = $class::where('is_default', true)->first();

        if (! $default) {
            $this->error('No default currency found. Set a default currency first.');

            return self::FAILURE;
        }

        $this->info("Fetching exchange rates for base currency: {$default->code}...");

        try {
            $rates = $service->fetchRates($default->code);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($rates)) {
            $this->warn('No exchange rates returned from the API.');

            return self::SUCCESS;
        }

        $currencies = $class::where('is_default', false)->where('is_active', true)->get();

        if ($currencies->isEmpty()) {
            $this->warn('No active non-default currencies to update.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $rows = [];

        foreach ($currencies as $currency) {
            $code = strtolower($currency->code);

            if (! isset($rates[$code])) {
                $skipped++;

                continue;
            }

            $oldRate = $currency->exchange_rate;
            $newRate = (string) $rates[$code];

            $rows[] = [
                $currency->code,
                $currency->name,
                number_format((float) $oldRate, 10),
                number_format((float) $newRate, 10),
            ];

            if (! $this->option('dry-run')) {
                $currency->update(['exchange_rate' => $newRate]);
            }

            $updated++;
        }

        $this->table(['Code', 'Name', 'Old Rate', 'New Rate'], $rows);

        $label = $this->option('dry-run') ? 'Would update' : 'Updated';
        $this->info("{$label} {$updated} currencies.");
        $this->info("Skipped {$skipped} currencies (not found in API response).");

        return self::SUCCESS;
    }
}
