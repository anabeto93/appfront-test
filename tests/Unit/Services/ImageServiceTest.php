<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup a fake storage disk for testing
        Storage::fake('public');
    }
    
    public function test_it_uploads_image_successfully()
    {
        // Arrange
        $imageService = new ImageService();
        $file = UploadedFile::fake()->image('product.jpg');
        
        // Act
        $path = $imageService->upload($file, 'products');
        
        // Assert
        // Check that the file exists in storage
        Storage::disk('public')->assertExists($path);
        
        // Verify the path follows expected pattern (starts with products/)
        $this->assertStringStartsWith('products/', $path);
    }
    
    public function test_it_generates_unique_filenames()
    {
        // Arrange
        $imageService = new ImageService();
        $file1 = UploadedFile::fake()->image('product.jpg');
        $file2 = UploadedFile::fake()->image('product.jpg');
        
        // Act
        $path1 = $imageService->upload($file1, 'products');
        $path2 = $imageService->upload($file2, 'products');
        
        // Assert
        $this->assertNotEquals($path1, $path2);
    }
    
    public function test_it_returns_default_image_when_file_is_null()
    {
        // Arrange
        $imageService = new ImageService();
        
        // Act
        $path = $imageService->upload(null, 'products', 'default.jpg');
        
        // Assert
        $this->assertEquals('default.jpg', $path);
    }
    
    public function test_it_returns_provided_path_for_invalid_file()
    {
        // Arrange
        $imageService = new ImageService();
        
        // Create a text file instead of an image
        $file = UploadedFile::fake()->create('document.txt', 100);
        
        // Act - this should fallback to the default
        $path = $imageService->upload($file, 'products', 'default.jpg');
        
        // Assert
        $this->assertEquals('default.jpg', $path);
    }
}
