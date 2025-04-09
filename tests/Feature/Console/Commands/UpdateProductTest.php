<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use App\Models\Product;
use App\Jobs\SendPriceChangeNotification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PriceChangeService;

class UpdateProductCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set a known email for notifications
        config(['price.notification_email' => 'test@example.com']);
        
        // Fake the queue to prevent actual dispatch
        Queue::fake();
    }
    
    public function test_it_updates_product_name()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'price' => 100.00
        ]);
        
        // Run the command
        $this->artisan('product:update', [
            'id' => $product->id,
            '--name' => 'Updated Name'
        ])
        ->expectsOutput('Product updated successfully.')
        ->assertExitCode(0);
        
        // Verify database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'description' => 'Original description',
            'price' => 100.00
        ]);
    }
    
    public function test_it_updates_product_description()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Original description',
            'price' => 100.00
        ]);
        
        // Run the command
        $this->artisan('product:update', [
            'id' => $product->id,
            '--description' => 'Updated description'
        ])
        ->expectsOutput('Product updated successfully.')
        ->assertExitCode(0);
        
        // Verify database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Test Product',
            'description' => 'Updated description'
        ]);
    }
    
    public function test_it_updates_product_price_and_dispatches_notification()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);
        
        // Run the command
        $this->artisan('product:update', [
            'id' => $product->id,
            '--price' => 150.00
        ])
        ->expectsOutput('Product updated successfully.')
        ->expectsOutput('Price changed from 100 to 150.')
        ->expectsOutput('Price change notification dispatched to test@example.com.')
        ->assertExitCode(0);
        
        // Verify database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Test Product',
            'price' => 150.00
        ]);
        
        // Verify notification was dispatched
        Queue::assertPushed(SendPriceChangeNotification::class, function ($job) use ($product) {
            return $job->product->id === $product->id &&
                   (float) $job->oldPrice === 100.00 &&
                   (float) $job->newPrice === 150.00 &&
                   $job->email === 'test@example.com';
        });
    }
    
    public function test_it_rejects_empty_or_short_name()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Name',
        ]);
        
        // Run the command with empty name
        $this->artisan('product:update', [
            'id' => $product->id,
            '--name' => '  ', //white spaces
        ])
        ->expectsOutput('Name cannot be empty.')
        ->assertExitCode(1);
        
        // Verify no database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
        ]);
    }
    
    public function test_it_rejects_name_less_than_three_characters()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Name',
        ]);
        
        // Run the command with too short name
        $this->artisan('product:update', [
            'id' => $product->id,
            '--name' => 'AB'
        ])
        ->expectsOutput('Name must be at least 3 characters long.')
        ->assertExitCode(1);
        
        // Verify no database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
        ]);
    }
    
    public function test_it_handles_no_changes()
    {
        // Create a product
        $product = Product::factory()->create();
        
        // Run the command with no options
        $this->artisan('product:update', [
            'id' => $product->id
        ])
        ->expectsOutput('No changes provided. Product remains unchanged.')
        ->assertExitCode(0);
    }

    public function test_it_handles_product_not_found()
    {
        // Use a non-existent product ID
        $nonExistentId = 9999;
        
        // Run the command with non-existent ID
        $this->artisan('product:update', [
            'id' => $nonExistentId,
            '--name' => 'New Name'
        ])
        ->expectsOutput("Product with ID {$nonExistentId} not found.")
        ->assertExitCode(1);
    }

    public function test_it_rejects_invalid_price_format()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);
        
        // Run the command with invalid price format
        $this->artisan('product:update', [
            'id' => $product->id,
            '--price' => 'not-a-number'
        ])
        ->expectsOutput('Price must be a valid number.')
        ->assertExitCode(1);
        
        // Verify no database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 100.00
        ]);
    }

    public function test_it_rejects_negative_price()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);
        
        // Run the command with negative price
        $this->artisan('product:update', [
            'id' => $product->id,
            '--price' => -50.00
        ])
        ->expectsOutput('Price cannot be negative.')
        ->assertExitCode(1);
        
        // Verify no database update
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 100.00
        ]);
    }

    public function test_it_logs_error_without_crashing_when_notification_fails()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);
        
        // Make the PriceChangeService return false
        $this->mock(PriceChangeService::class, function ($mock) {
            $mock->shouldReceive('notifyChangeInPrice')->once()->andReturn(false);
        });
        
        // Run the command
        $this->artisan('product:update', [
            'id' => $product->id,
            '--price' => 150.00
        ])
        ->expectsOutput('Product updated successfully.')
        ->expectsOutput('Price changed from 100 to 150.')
        ->expectsOutput('Failed to dispatch price change notification.')
        ->assertExitCode(0); // Should still exit successfully even if notification fails
        
        // Verify database update still happened
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 150.00
        ]);
    }
}
