<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a user for each test
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_admin_can_view_products_list()
    {
        // Create some products
        $products = Product::factory()->count(3)->create();
        
        $response = $this->get(route('admin.products.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.products');
        $response->assertViewHas('products');
        
        // Check if all products are in the view data
        $responseProducts = $response->viewData('products');
        $this->assertCount(3, $responseProducts);
        
        foreach ($products as $product) {
            $this->assertTrue($responseProducts->contains('id', $product->id));
        }
    }

    public function test_admin_can_view_edit_product_form()
    {
        // Create a product
        $product = Product::factory()->create();
        
        $response = $this->get(route('admin.products.edit', ['product' => $product]));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.edit_product');
        $response->assertViewHas('product');
        
        // Check if the correct product is passed to the view
        $responseProduct = $response->viewData('product');
        $this->assertEquals($product->id, $responseProduct->id);
    }

    public function test_unauthenticated_users_cannot_access_admin_products()
    {
        // Logout the user
        Auth::logout();
        
        $response = $this->get(route('admin.products.index'));
        
        // Should redirect to login
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_see_add_product_form()
    {
        $response = $this->get(route('admin.products.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.add_product');
    }

    public function test_admin_can_add_new_product()
    {
        $productData = [
            'name' => 'New Test Product',
            'description' => 'Product description',
            'price' => 99.99,
            'image' => null,
        ];
        
        $response = $this->post(route('admin.products.store'), $productData);
        
        // Should redirect to products list
        $response->assertRedirect(route('admin.products.index'));
        
        // Check if product was created in the database
        $this->assertDatabaseHas('products', [
            'name' => 'New Test Product',
            'price' => 99.99,
        ]);
    }

    public function test_admin_can_update_product()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'price' => 50.00
        ]);
        
        $updatedData = [
            'name' => 'Updated Product Name',
            'description' => 'Updated product description',
            'price' => 75.99,
            'image' => null,
        ];
        
        $response = $this->put(route('admin.products.update', ['product' => $product->id]), $updatedData);
        
        // Should redirect to products list
        $response->assertRedirect(route('admin.products.index'));
        
        // Check if product was updated in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'description' => 'Updated product description',
            'price' => 75.99,
        ]);
        
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_admin_can_delete_product()
    {
        // Create a product
        $product = Product::factory()->create();
        
        // Verify it exists in the database
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        
        $response = $this->delete(route('admin.products.destroy', ['product' => $product]));
        
        // Should redirect to products list
        $response->assertRedirect(route('admin.products.index'));
        
        // Check if product was deleted from the database
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_update_product_with_invalid_data_fails()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'price' => 50.00
        ]);
        
        // Missing required name
        $invalidData = [
            'name' => '',
            'description' => 'Some description',
            'price' => 75.99,
        ];
        
        $response = $this->put(route('admin.products.update', ['product' => $product->id]), $invalidData);
        
        // Should redirect back with errors
        $response->assertSessionHasErrors(['name']);
        
        // Verify product data didn't change
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Product',
        ]);
    }

    public function test_non_existent_product_returns_404()
    {
        $nonExistentId = 9999; // Assuming this ID doesn't exist
        
        $response = $this->get(route('admin.products.edit', ['product' => $nonExistentId]));
        
        $response->assertStatus(404);
    }

    public function test_post_request_to_update_product_is_not_allowed()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 50.00
        ]);
        
        $updatedData = [
            'name' => 'Updated Product Name',
            'price' => 75.99,
        ];
        
        // Try to update with POST (old way)
        $response = $this->post(route('admin.products.update', ['product' => $product->id]), $updatedData);
        
        // Should return 405 Method Not Allowed
        $response->assertStatus(405);
        
        // Product should remain unchanged in database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
            'price' => 50.00
        ]);
    }

    public function test_get_request_to_delete_product_is_not_allowed()
    {
        // Create a product
        $product = Product::factory()->create();
        
        // Try to delete with GET (old way)
        $response = $this->get(route('admin.products.destroy', ['product' => $product->id]));
        
        // Should return 405 Method Not Allowed
        $response->assertStatus(405);
        
        // Product should still exist in database
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
