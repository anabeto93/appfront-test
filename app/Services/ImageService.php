<?php 

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageService 
{
    /**
     * Upload an image to storage
     *
     * @param UploadedFile|null $file The file to upload
     * @param string $directory The directory to store the file in
     * @param string $defaultPath The default path to return if no file is provided
     * @return string The path to the uploaded file or default path
     */
    public function upload(?UploadedFile $file, string $directory = 'uploads', string $defaultPath = 'product-placeholder.jpg'): string
    {
        // If no file was provided or it's not valid, return the default path
        if ($file === null || !$file->isValid() || !$this->isImage($file)) {
            return $defaultPath;
        }

        // Generate a unique filename with original extension
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store the file in the public disk under the specified directory
        $path = $file->storeAs($directory, $filename, 'public');
        
        return $path;
    }
    
    /**
     * Check if a file is an image
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'image/webp'
        ]);
    }
}
