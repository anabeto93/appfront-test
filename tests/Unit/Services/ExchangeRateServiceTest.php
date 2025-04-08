<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ExchangeRateServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up configuration values for testing
        Config::set('exchange_rate.cache.key', 'exchange_rate_test');
        Config::set('exchange_rate.cache.ttl', 60);
        Config::set('exchange_rate.api.url', 'https://api.example.com/rates');
        Config::set('exchange_rate.api.timeout', 5);
        Config::set('exchange_rate.default_rate', 0.85);
    }

    public function test_it_returns_exchange_rate_from_api()
    {
        // Arrange
        Cache::forget('exchange_rate_test'); // Ensure cache is empty
        
        Http::fake([
            'api.example.com/rates' => Http::response([
                'rates' => [
                    'EUR' => 0.93
                ]
            ], 200)
        ]);

        // Act
        $service = new ExchangeRateService();
        $rate = $service->getRate();

        // Assert
        $this->assertEquals(0.93, $rate);
        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.example.com/rates';
        });
    }

    public function test_it_caches_exchange_rate()
    {
        // Arrange
        Cache::forget('exchange_rate_test'); // Ensure cache is empty
        
        Http::fake([
            'api.example.com/rates' => Http::sequence([
                Http::response([
                    'rates' => [
                        'EUR' => 0.93
                    ]
                ], 200),
                Http::response(['error' => 'Not Found'], 404),
            ]),
        ]);

        // Act
        $service = new ExchangeRateService();
        $service->getRate(); // First call should hit the API
        
        // second call should use cache
        $rate = $service->getRate(); // Second call should use cache

        // Assert
        $this->assertEquals(0.93, $rate);
        Http::assertSentCount(1); // Only one HTTP request should have been made
    }

    public function test_it_returns_default_rate_when_api_fails()
    {
        // Arrange
        Cache::forget('exchange_rate_test'); // Ensure cache is empty
        
        Http::fake([
            'api.example.com/rates' => Http::response('Server Error', 500)
        ]);

        // Act
        $service = new ExchangeRateService();
        $rate = $service->getRate();

        // Assert
        $this->assertEquals(0.85, $rate);
    }

    public function test_it_returns_default_rate_when_eur_rate_not_found()
    {
        // Arrange
        Cache::forget('exchange_rate_test'); // Ensure cache is empty
        
        Http::fake([
            'api.example.com/rates' => Http::response([
                'rates' => [
                    'GBP' => 0.75
                    // EUR rate is missing
                ]
            ], 200)
        ]);

        // Act
        $service = new ExchangeRateService();
        $rate = $service->getRate();

        // Assert
        $this->assertEquals(0.85, $rate);
    }

    public function test_it_returns_default_rate_when_exception_occurs()
    {
        // Arrange
        Cache::forget('exchange_rate_test'); // Ensure cache is empty
        
        Http::fake([
            'api.example.com/rates' => function() {
                throw new \Exception('Connection timeout');
            }
        ]);

        // Act
        $service = new ExchangeRateService();
        $rate = $service->getRate();

        // Assert
        $this->assertEquals(0.85, $rate);
    }
}
