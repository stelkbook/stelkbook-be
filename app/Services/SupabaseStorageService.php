<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        $this->key = env('SUPABASE_KEY');
    }

    /**
     * Upload file to Supabase Storage
     *
     * @param UploadedFile $file
     * @param string $bucket
     * @param string $path
     * @return string|null Public URL of the uploaded file
     */
    public function upload(UploadedFile $file, $bucket, $path)
    {
        $endpoint = "{$this->url}/storage/v1/object/{$bucket}/{$path}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true', // Allow overwriting
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )->post($endpoint);

            if ($response->successful()) {
                // Return the public URL
                return "{$this->url}/storage/v1/object/public/{$bucket}/{$path}";
            }

            Log::error('Supabase Upload Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Supabase Upload Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete file from Supabase Storage
     *
     * @param string $bucket
     * @param string $url Full URL or path
     * @return bool
     */
    public function delete($bucket, $url)
    {
        // Extract path from URL if necessary
        $publicUrlPrefix = "{$this->url}/storage/v1/object/public/{$bucket}/";
        $path = str_replace($publicUrlPrefix, '', $url);

        $endpoint = "{$this->url}/storage/v1/object/{$bucket}/{$path}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
            ])->delete($endpoint);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Supabase Delete Exception: ' . $e->getMessage());
            return false;
        }
    }
}
