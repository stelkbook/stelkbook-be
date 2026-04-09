<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    /**
     * Upload file to Supabase Storage (S3)
     *
     * @param UploadedFile $file
     * @param string $bucket
     * @param string $path
     * @return string|null Public URL of the uploaded file
     */
    public function upload(UploadedFile $file, $bucket, $path)
    {
        $disk = $this->getDiskName($bucket);

        try {
            // Upload using S3 driver
            $result = Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

            if ($result) {
                // Return the configured public URL
                return Storage::disk($disk)->url($path);
            }

            Log::error("Supabase S3 Upload Failed for $path on disk $disk");
            return null;
        } catch (\Exception $e) {
            Log::error('Supabase S3 Upload Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete file from Supabase Storage (S3)
     *
     * @param string $bucket
     * @param string $url Full URL or path
     * @return bool
     */
    public function delete($bucket, $url)
    {
        $disk = $this->getDiskName($bucket);

        try {
            // Get the base URL configured for this disk
            $prefix = Storage::disk($disk)->url('');
            
            // Extract path from URL if it's a full URL
            if (strpos($url, 'http') === 0) {
                 $path = str_replace($prefix, '', $url);
            } else {
                 $path = $url;
            }

            // Clean up path
            $path = ltrim($path, '/');

            return Storage::disk($disk)->delete($path);
        } catch (\Exception $e) {
            Log::error('Supabase S3 Delete Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the filesystem disk name based on the bucket
     *
     * @param string $bucket
     * @return string
     */
    protected function getDiskName($bucket)
    {
        return match ($bucket) {
            'img_cover' => 'supabase_cover',
            'pdf_buku' => 'supabase_pdf',
            default => 's3',
        };
    }
}
