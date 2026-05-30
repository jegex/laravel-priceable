<?php

namespace Jegex\LaravelPriceable\Commands;

use Illuminate\Console\Command;

use function Jegex\LaravelPriceable\priceable_currency_model;

class LaravelPriceableCommand extends Command
{
    public $signature = 'laravel-priceable';

    public $description = 'Display package info, currencies, and configuration summary';

    public function handle(): int
    {
        $this->components->twoColumnDetail(
            '<fg=green>Laravel Priceable</>',
            'jegex/laravel-priceable',
        );
        $this->line('');

        $currencies = priceable_currency_model()::all();

        if ($currencies->isEmpty()) {
            $this->warn('No currencies found. Run <comment>php artisan priceable:seed-currencies</comment> to seed default currencies.');

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail(
            'Total currencies',
            (string) $currencies->count(),
        );
        $this->components->twoColumnDetail(
            'Fiat',
            (string) $currencies->where('type', 'fiat')->count(),
        );
        $this->components->twoColumnDetail(
            'Crypto',
            (string) $currencies->where('type', 'crypto')->count(),
        );

        $default = $currencies->firstWhere('is_default', true);
        if ($default) {
            $this->components->twoColumnDetail(
                'Default currency',
                "{$default->code} ({$default->name})",
            );
        }
        $this->line('');

        $this->components->twoColumnDetail('<fg=yellow>Code</>', '<fg=yellow>Name / Rate / Type / Status</>');

        foreach ($currencies as $currency) {
            $label = "<fg=cyan>{$currency->code}</>";

            $tags = [];
            if ($currency->is_default) {
                $tags[] = '<fg=green>default</>';
            }
            $tags[] = $currency->is_active ? '<fg=green>active</>' : '<fg=red>inactive</>';
            $badges = implode(' ', $tags);

            $detail = sprintf(
                '%s | %s | %s',
                $currency->name,
                match ($currency->type) {
                    'crypto' => "1 {$currency->code} = {$currency->symbol}{$currency->exchange_rate}",
                    default => "1 USD = {$currency->symbol}{$currency->exchange_rate}",
                },
                $badges,
            );

            $this->components->twoColumnDetail($label, $detail);
        }

        return self::SUCCESS;
    }
}
