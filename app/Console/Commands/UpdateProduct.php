<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\PriceChangeService;

class UpdateProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:update {id} {--name=} {--description=} {--price=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a product with the specified details';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(private PriceChangeService $priceChangeService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $product = Product::find($id);
        if (!$product) {
            $this->error("Product with ID {$id} not found.");
            return 1;
        }
    
        $data = $this->collectData();

        if ($data[0] !== 0) {
            return $data[0];
        }
        
        // If no valid data was provided, return early
        if (empty($data[1])) {
            $this->info("No changes provided. Product remains unchanged.");
            return 0;
        }

        $data = $data[1];
        
        // If no valid data was provided, return early
        if (empty($data)) {
            $this->info("No changes provided. Product remains unchanged.");
            return 0;
        }


        $oldPrice = $product->price;

        // Update the product
        $product->update($data);
        $product->save();
        $this->info("Product updated successfully.");

        // Handle price change notification if needed
        $this->handlePriceChange($product, $oldPrice);

        return 0;
    }

    /**
     * Collect and validate data from command options.
     *
     * @return array
     */
    private function collectData(): array 
    {
        $data = [];
        
        // Process name option
        if ($this->option('name')) {
            $name = $this->option('name');
            
            if (empty(trim($name))) {
                $this->error("Name cannot be empty.");
                return [1, []];
            }
            
            if (strlen($name) < 3) {
                $this->error("Name must be at least 3 characters long.");
                return [1, []];
            }
            
            $data['name'] = $name;
        }
        
        // Process other options
        if ($this->option('description')) {
            $data['description'] = $this->option('description');
        }

        if ($this->option('price')) {
            $price = $this->option('price');
            
            // Check if price is numeric
            if (!is_numeric($price)) {
                $this->error("Price must be a valid number.");
                return [1, []];
            }
            
            // Convert to float for comparison
            $price = (float) $price;
            
            // Check if price is negative
            if ($price < 0) {
                $this->error("Price cannot be negative.");
                return [1, []];
            }
            
            $data['price'] = $price;
        }
        
        return [0, $data];
    }

    /**
     * Handle price change notification if price was updated.
     *
     * @param Product $product
     * @param float $oldPrice
     * @return void
     */
    private function handlePriceChange(Product $product, float $oldPrice): void
    {
        // Skip if price didn't change
        if ($oldPrice == $product->price) {
            return;
        }
        
        $this->info("Price changed from {$oldPrice} to {$product->price}.");
        
        // Use price change service
        $result = $this->priceChangeService->notifyChangeInPrice(
            $product, 
            $oldPrice, 
            $product->price
        );
        
        if ($result) {
            $this->info("Price change notification dispatched to " . config('price.notification_email') . ".");
        } else {
            $this->error("Failed to dispatch price change notification.");
        }
    }
}
