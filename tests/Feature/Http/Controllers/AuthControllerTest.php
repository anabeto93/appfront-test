<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('login');
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        // Create a user for testing
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        
        // Assert redirect to admin products page after successful login
        $response->assertRedirect(route('admin.products'));
        
        // Assert user is authenticated
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_login_fails_with_invalid_credentials()
    {
        // Create a user for testing
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);
        
        // Assert redirect back to login page
        $response->assertRedirect();
        
        // Assert error message exists in session
        $response->assertSessionHas('error', 'Invalid login credentials');
        
        // Assert user is not authenticated
        $this->assertFalse(Auth::check());
    }

    public function test_admin_can_logout()
    {
        // Create and login a user
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Assert user is authenticated
        $this->assertTrue(Auth::check());
        
        // Logout
        $response = $this->post('/logout');
        
        // Assert redirect to login page
        $response->assertRedirect(route('login'));
        
        // Assert user is not authenticated anymore
        $this->assertFalse(Auth::check());
    }

    public function test_get_request_to_logout_is_not_allowed()
    {
        // Create and login a user
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Assert user is authenticated
        $this->assertTrue(Auth::check());
        
        // Attempt to logout with GET - should not work
        $response = $this->get('/logout');
        
        // Should return 405 Method Not Allowed
        $response->assertStatus(405);
        
        // User should still be authenticated
        $this->assertTrue(Auth::check());
    }
}
