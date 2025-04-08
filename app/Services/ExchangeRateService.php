<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function getRate(): float
    {
        return Cache::remember(
            config('exchange_rate.cache.key'),
            config('exchange_rate.cache.ttl'),
            function () {
                try {
                    $response = Http::timeout(config('exchange_rate.api.timeout'))
                        ->get(config('exchange_rate.api.url'));

                    if (!$response->successful()) {
                        Log::debug('Error fetching exchange rate:', [
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);
                        return config('exchange_rate.default_rate');
                    }

                    $data = $response->json();
                    return $data['rates']['EUR'] ?? config('exchange_rate.default_rate');
                } catch (\Exception $e) {
                    Log::error('Error fetching exchange rate: ' . $e->getMessage());
                    return config('exchange_rate.default_rate');
                }
            }
        );
    }
} 
