<?php

namespace Jegex\LaravelPriceable\ValueObjects;

use Jegex\LaravelPriceable\Contracts\PriceFormatterInterface;
use Jegex\LaravelPriceable\Models\Currency;
use Jegex\LaravelPriceable\Pricing\DefaultPriceFormatter;

class MoneyValue
{
    public function __construct(
        public readonly int $cents,
        public readonly Currency $currency,
        public readonly int $unitQty = 1,
    ) {}

    public function decimal(): float
    {
        return $this->formatter()->decimal();
    }

    public function unitDecimal(): float
    {
        return $this->formatter()->unitDecimal();
    }

    public function formatted(
        ?string $locale = null,
        int $formatterStyle = \NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): string {
        return $this->formatter()->formatted(
            $locale,
            $formatterStyle,
            $decimalPlaces,
            $trimTrailingZeros,
        );
    }

    public function unitFormatted(
        ?string $locale = null,
        int $formatterStyle = \NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): string {
        return $this->formatter()->unitFormatted(
            $locale,
            $formatterStyle,
            $decimalPlaces,
            $trimTrailingZeros,
        );
    }

    public function __toString(): string
    {
        return (string) $this->cents;
    }

    private function formatter(): PriceFormatterInterface
    {
        $class = config('priceable.pricing.formatter', DefaultPriceFormatter::class);

        return new $class($this->cents, $this->currency, $this->unitQty);
    }
}
