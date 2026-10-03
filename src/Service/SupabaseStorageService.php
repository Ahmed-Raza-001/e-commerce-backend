<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SupabaseStorageService
{
    private HttpClientInterface $httpClient;
    private string $supabaseUrl;
    private string $supabaseKey;
    private string $bucket;
    private string $projectDir;

    // Allowed image & file extensions
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'avif'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(default::SUPABASE_URL)%')] ?string $supabaseUrl = null,
        #[Autowire('%env(default::SUPABASE_KEY)%')] ?string $supabaseKey = null,
        #[Autowire('%env(default::SUPABASE_BUCKET)%')] ?string $bucket = null
    ) {
        $this->httpClient = $httpClient;
        $this->projectDir = $projectDir;
        $this->supabaseUrl = !empty($supabaseUrl) ? $supabaseUrl : ($_ENV['SUPABASE_URL'] ?? $_ENV['NEXT_PUBLIC_SUPABASE_URL'] ?? '');
        $this->supabaseKey = !empty($supabaseKey) ? $supabaseKey : ($_ENV['SUPABASE_KEY'] ?? $_ENV['NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY'] ?? '');
        $this->bucket = !empty($bucket) ? $bucket : ($_ENV['SUPABASE_BUCKET'] ?? 'product');
    }

    /**
     * Upload an uploaded file to Supabase Storage.
     *
     * @param UploadedFile $file
     * @param string $folder Subfolder inside the bucket (e.g., 'products')
     * @return array
     * @throws \RuntimeException
     */
    public function upload(UploadedFile $file, string $folder = 'products'): array
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Uploaded file is not valid: ' . $file->getErrorMessage());
        }

        $fileSize = $file->getSize();
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds the maximum limit of 10MB.');
        }

        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed formats: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalFilename);
        $uniqueFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
        $objectPath = trim($folder, '/') . '/' . $uniqueFilename;
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        // Read file content
        $fileContent = file_get_contents($file->getPathname());
        if ($fileContent === false) {
            throw new FileException('Failed to read uploaded file contents.');
        }

        // 1. Attempt upload to Supabase Storage
        if (!empty($this->supabaseUrl) && !empty($this->supabaseKey)) {
            $uploadUrl = sprintf(
                '%s/storage/v1/object/%s/%s',
                rtrim($this->supabaseUrl, '/'),
                $this->bucket,
                $objectPath
            );

            try {
                $response = $this->httpClient->request('POST', $uploadUrl, [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => $mimeType,
                        'x-upsert' => 'true',
                    ],
                    'body' => $fileContent,
                    'timeout' => 5.0,
                ]);

                $statusCode = $response->getStatusCode();
                
                if ($statusCode >= 200 && $statusCode < 300) {
                    $publicUrl = sprintf(
                        '%s/storage/v1/object/public/%s/%s',
                        rtrim($this->supabaseUrl, '/'),
                        $this->bucket,
                        $objectPath
                    );

                    return [
                        'url' => $publicUrl,
                        'filename' => $uniqueFilename,
                        'path' => $objectPath,
                        'bucket' => $this->bucket,
                        'size' => $fileSize,
                        'mimeType' => $mimeType,
                        'storage' => 'supabase',
                    ];
                }

                $content = $response->toArray(false);
                $errorMsg = $content['message'] ?? $content['error'] ?? ('Supabase error ' . $statusCode);
                throw new \RuntimeException('Supabase Storage Error: ' . $errorMsg);
            } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
                throw new \RuntimeException('Supabase Connection Failed: ' . $e->getMessage());
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        return $this->uploadLocal($file, $folder);
    }

    /**
     * Fallback helper to save file locally in public/uploads if needed.
     */
    public function uploadLocal(UploadedFile $file, string $subDirectory = 'products'): array
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalFilename);
        $uniqueFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        $targetDirectory = $this->projectDir . '/public/uploads/' . trim($subDirectory, '/');
        $file->move($targetDirectory, $uniqueFilename);

        return [
            'url' => '/uploads/' . trim($subDirectory, '/') . '/' . $uniqueFilename,
            'filename' => $uniqueFilename,
            'size' => filesize($targetDirectory . '/' . $uniqueFilename),
            'mimeType' => mime_content_type($targetDirectory . '/' . $uniqueFilename) ?: 'application/octet-stream',
            'storage' => 'local',
        ];
    }
}
