<?php

namespace Tests\Feature\Http\Controllers;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_page_works_containing_products_and_exchange_rate()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('products.list');
        $response->assertViewHas('products');
        $response->assertViewHas('exchangeRate'); // Just verify it exists
    }

    public function test_it_displays_all_products_on_index_page()
    {
        // Arrange: Create products
        $products = Product::factory()->count(3)->create();
        
        // Act: Make request to the index route
        $response = $this->get('/');
        
        // Assert: Verify response
        $response->assertStatus(200);
        $response->assertViewIs('products.list');
        
        // Get the products from the view and verify they match what we created
        $responseProducts = $response->viewData('products');
        $this->assertCount(3, $responseProducts);
        
        // Verify each product exists in the response collection
        foreach ($products as $product) {
            $this->assertTrue($responseProducts->contains('id', $product->id));
            $this->assertTrue($responseProducts->contains('name', $product->name));
            $this->assertTrue($responseProducts->contains('price', $product->price));
        }
        
        // Just verify the exchange rate exists without checking the specific value
        $this->assertNotNull($response->viewData('exchangeRate'));
        $this->assertIsNumeric($response->viewData('exchangeRate'));
    }

    public function test_it_displays_a_single_product_on_show_page()
    {
        // Arrange: Create a product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99
        ]);
        
        // Act: Make request to the show route
        $response = $this->get("/products/{$product->id}");
        
        // Assert: Verify response
        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        
        // Verify the product details
        $responseProduct = $response->viewData('product');
        $this->assertEquals($product->id, $responseProduct->id);
        $this->assertEquals('Test Product', $responseProduct->name);
        $this->assertEquals('Test Description', $responseProduct->description);
        $this->assertEquals(99.99, $responseProduct->price);
        
        // Just verify the exchange rate exists without checking the specific value
        $this->assertNotNull($response->viewData('exchangeRate'));
        $this->assertIsNumeric($response->viewData('exchangeRate'));
    }
}
