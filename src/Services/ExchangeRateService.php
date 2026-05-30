<?php

namespace Jegex\LaravelPriceable\Services;

use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    private const PRIMARY_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/%s.json';

    private const FALLBACK_URL = 'https://latest.currency-api.pages.dev/v1/currencies/%s.json';

    public function fetchRates(string $baseCurrencyCode): array
    {
        $code = strtolower($baseCurrencyCode);

        $response = $this->tryUrl(sprintf(self::PRIMARY_URL, $code));

        if ($response === null) {
            $response = $this->tryUrl(sprintf(self::FALLBACK_URL, $code));
        }

        if ($response === null) {
            throw new \RuntimeException(
                "Failed to fetch exchange rates for '{$baseCurrencyCode}' from both API endpoints."
            );
        }

        return $response[$code] ?? [];
    }

    private function tryUrl(string $url): ?array
    {
        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable) {
            // silent — try next URL
        }

        return null;
    }
}
