<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PriceChangeService;
use App\Models\Product;
use App\Jobs\SendPriceChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Bus;
use Mockery;

class PriceChangeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PriceChangeService $priceChangeService;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        /** @var PriceChangeService */
        $this->priceChangeService = app()->make(PriceChangeService::class);
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);
        
        // Set test email for notifications
        config(['price.notification_email' => 'test@example.com']);
    }
    
    public function test_it_dispatches_job_when_price_changes()
    {
        // Arrange
        Queue::fake();
        $oldPrice = 100.00;
        $newPrice = 150.00;
        
        // Act
        $result = $this->priceChangeService->notifyChangeInPrice($this->product, $oldPrice, $newPrice);
        
        // Assert
        $this->assertTrue($result);
        Queue::assertPushed(SendPriceChangeNotification::class, function ($job) use ($oldPrice, $newPrice) {
            return $job->product->id === $this->product->id &&
                   $job->oldPrice === $oldPrice &&
                   $job->newPrice === $newPrice &&
                   $job->email === 'test@example.com';
        });
    }
    
    public function test_it_does_not_dispatch_job_when_price_is_unchanged()
    {
        // Arrange
        Queue::fake();
        $oldPrice = 100.00;
        $newPrice = 100.00;
        
        // Act
        $result = $this->priceChangeService->notifyChangeInPrice($this->product, $oldPrice, $newPrice);
        
        // Assert
        $this->assertFalse($result);
        Queue::assertNotPushed(SendPriceChangeNotification::class);
    }
    
    public function test_it_returns_false_when_job_dispatch_fails()
    {
        // Force Job dispatching to throw an exception
        Bus::shouldReceive('dispatch')
            ->with(Mockery::type(SendPriceChangeNotification::class))
            ->andThrow(new \Exception('Dispatch failed'));
        
        // Act
        $oldPrice = 100.00;
        $newPrice = 150.00;
        $result = $this->priceChangeService->notifyChangeInPrice($this->product, $oldPrice, $newPrice);
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function test_it_uses_config_value_for_notification_email()
    {
        // Arrange
        Queue::fake();
        $customEmail = 'custom@example.com';
        Config::set('price.notification_email', $customEmail);
        
        // Act
        $this->priceChangeService->notifyChangeInPrice($this->product, 100.00, 150.00);
        
        // Assert
        Queue::assertPushed(SendPriceChangeNotification::class, function ($job) use ($customEmail) {
            return $job->email === $customEmail;
        });
    }
}
