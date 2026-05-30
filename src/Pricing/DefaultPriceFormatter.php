<?php

namespace Jegex\LaravelPriceable\Pricing;

use Jegex\LaravelPriceable\Contracts\PriceFormatterInterface;
use Jegex\LaravelPriceable\Models\Currency;
use NumberFormatter;

class DefaultPriceFormatter implements PriceFormatterInterface
{
    public function __construct(
        public int $value,
        public Currency $currency,
        public int $unitQty = 1,
    ) {}

    public function decimal(bool $rounding = true): float
    {
        $divisor = 10 ** $this->currency->decimal_place;
        $converted = $this->value / $divisor;

        return $rounding ? round($converted, $this->currency->decimal_place) : $converted;
    }

    public function unitDecimal(bool $rounding = true): float
    {
        $converted = $this->decimal(false) / $this->unitQty;

        return $rounding ? round($converted, $this->currency->decimal_place) : $converted;
    }

    public function formatted(
        ?string $locale = null,
        int $formatterStyle = NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): string {
        return $this->formatValue(
            $this->decimal(false),
            $locale,
            $formatterStyle,
            $decimalPlaces,
            $trimTrailingZeros,
        );
    }

    public function unitFormatted(
        ?string $locale = null,
        int $formatterStyle = NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): string {
        return $this->formatValue(
            $this->unitDecimal(false),
            $locale,
            $formatterStyle,
            $decimalPlaces,
            $trimTrailingZeros,
        );
    }

    protected function formatValue(
        int|float $value,
        ?string $locale = null,
        int $formatterStyle = NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): string {
        $locale ??= app()->currentLocale();
        $dp = $decimalPlaces ?? $this->currency->decimal_place;

        if ($this->currency->type === 'crypto') {
            return $this->currency->symbol.number_format($value, $dp, '.', '');
        }

        try {
            $formatter = new NumberFormatter($locale, $formatterStyle);

            $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $this->currency->code);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $dp);

            $result = $formatter->format($value);

            if ($result === false) {
                throw new \RuntimeException('NumberFormatter::format failed');
            }

            if ($trimTrailingZeros && $dp > $this->currency->decimal_place) {
                $dec = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
                $parts = explode($dec, $result);
                if (count($parts) === 2) {
                    $fractional = $parts[1];
                    $keep = substr($fractional, 0, $this->currency->decimal_place);
                    $extra = rtrim(substr($fractional, $this->currency->decimal_place), '0');
                    $combined = $keep.$extra;
                    $result = $combined !== ''
                        ? $parts[0].$dec.$combined
                        : $parts[0];
                }
            }

            return $result;
        } catch (\Throwable) {
            return $this->currency->symbol.number_format($value, $dp, '.', ',');
        }
    }
}
