<?php

namespace App\Services\Woo;

use App\Tenancy\TenantContext;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class WordPressMediaService
{
    private TenantContext $tenant;

    private Client $httpClient;

    private string $baseUrl;

    private array $auth;

    public function __construct(TenantContext $tenant)
    {
        $this->tenant = $tenant;
        $config = $tenant->getWooCommerceConfig();

        $this->baseUrl = rtrim($config['base_url'], '/').'/wp-json/wp/v2';
        $this->auth = [$config['consumer_key'], $config['consumer_secret']];

        $this->httpClient = new Client([
            'timeout' => 60, // Longer timeout for file uploads
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Aroi-PCK-Integration/1.0',
            ],
        ]);
    }

    /**
     * Upload image to WordPress media library
     */
    public function uploadImage(string $imageData, array $metadata = []): array
    {
        // Decode base64 image data if needed
        if (base64_encode(base64_decode($imageData, true)) === $imageData) {
            $binaryData = base64_decode($imageData);
        } else {
            $binaryData = $imageData;
        }

        // Detect image format
        $imageInfo = $this->detectImageFormat($binaryData);
        if (! $imageInfo) {
            throw new \RuntimeException('Unable to detect image format');
        }

        // Prepare filename
        $filename = $metadata['filename'] ?? 'image-'.time().'.'.$imageInfo['extension'];
        $filename = $this->sanitizeFilename($filename);

        // Prepare upload data
        $title = $metadata['title'] ?? pathinfo($filename, PATHINFO_FILENAME);
        $altText = $metadata['alt_text'] ?? '';
        $description = $metadata['description'] ?? '';

        try {
            // Upload the file
            $uploadResponse = $this->uploadFile($binaryData, $filename, $imageInfo['mime_type']);

            // Update media metadata if needed
            if (! empty($title) || ! empty($altText) || ! empty($description)) {
                $uploadResponse = $this->updateMediaMetadata($uploadResponse['id'], [
                    'title' => $title,
                    'alt_text' => $altText,
                    'description' => $description,
                ]);
            }

            Log::info('WordPress Media: Image uploaded successfully', [
                'tenant_id' => $this->tenant->getTenantId(),
                'media_id' => $uploadResponse['id'],
                'filename' => $filename,
                'size' => strlen($binaryData),
                'mime_type' => $imageInfo['mime_type'],
                'url' => $uploadResponse['source_url'],
            ]);

            return $uploadResponse;

        } catch (\Exception $e) {
            Log::error('WordPress Media: Image upload failed', [
                'tenant_id' => $this->tenant->getTenantId(),
                'filename' => $filename,
                'size' => strlen($binaryData),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload file binary data to WordPress
     */
    private function uploadFile(string $binaryData, string $filename, string $mimeType): array
    {
        $url = $this->baseUrl.'/media';

        $options = [
            'auth' => $this->auth,
            'headers' => [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ],
            'body' => $binaryData,
            'verify' => false,
        ];

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->request('POST', $url, $options);
            $duration = microtime(true) - $startTime;

            $body = $this->parseResponse($response);

            Log::debug('WordPress Media: File uploaded', [
                'tenant_id' => $this->tenant->getTenantId(),
                'filename' => $filename,
                'media_id' => $body['id'],
                'duration_ms' => round($duration * 1000, 2),
            ]);

            return $body;

        } catch (GuzzleException $e) {
            $this->logRequestError('POST', '/media (upload)', $e, [
                'filename' => $filename,
                'size' => strlen($binaryData),
                'mime_type' => $mimeType,
            ]);
            throw new \RuntimeException('WordPress media upload failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update media metadata
     */
    public function updateMediaMetadata(int $mediaId, array $metadata): array
    {
        $url = $this->baseUrl.'/media/'.$mediaId;

        $updateData = [];

        if (! empty($metadata['title'])) {
            $updateData['title'] = $metadata['title'];
        }

        if (! empty($metadata['alt_text'])) {
            $updateData['alt_text'] = $metadata['alt_text'];
        }

        if (! empty($metadata['description'])) {
            $updateData['description'] = $metadata['description'];
        }

        if (! empty($metadata['caption'])) {
            $updateData['caption'] = $metadata['caption'];
        }

        if (empty($updateData)) {
            // Return existing media data if nothing to update
            return $this->getMedia($mediaId);
        }

        $options = [
            'auth' => $this->auth,
            'json' => $updateData,
            'verify' => false,
        ];

        try {
            $response = $this->httpClient->request('PUT', $url, $options);
            $body = $this->parseResponse($response);

            Log::debug('WordPress Media: Metadata updated', [
                'tenant_id' => $this->tenant->getTenantId(),
                'media_id' => $mediaId,
                'updated_fields' => array_keys($updateData),
            ]);

            return $body;

        } catch (GuzzleException $e) {
            $this->logRequestError('PUT', "/media/{$mediaId}", $e, $updateData);
            throw new \RuntimeException('WordPress media metadata update failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get media by ID
     */
    public function getMedia(int $mediaId): array
    {
        return $this->makeRequest('GET', "/media/{$mediaId}");
    }

    /**
     * Delete media
     */
    public function deleteMedia(int $mediaId, bool $force = false): array
    {
        $params = $force ? '?force=true' : '';
        $response = $this->makeRequest('DELETE', "/media/{$mediaId}{$params}");

        Log::info('WordPress Media: Media deleted', [
            'tenant_id' => $this->tenant->getTenantId(),
            'media_id' => $mediaId,
            'force' => $force,
        ]);

        return $response;
    }

    /**
     * Search media
     */
    public function searchMedia(array $params = []): array
    {
        $defaultParams = ['per_page' => 100];
        $queryParams = array_merge($defaultParams, $params);
        $queryString = http_build_query($queryParams);

        return $this->makeRequest('GET', "/media?{$queryString}");
    }

    /**
     * Detect image format from binary data
     */
    private function detectImageFormat(string $binaryData): ?array
    {
        // Get image info from binary data
        $imageInfo = @getimagesizefromstring($binaryData);

        if ($imageInfo === false) {
            return null;
        }

        $mimeType = $imageInfo['mime'] ?? '';

        $formats = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
        ];

        $extension = $formats[$mimeType] ?? null;

        if (! $extension) {
            return null;
        }

        return [
            'mime_type' => $mimeType,
            'extension' => $extension,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
        ];
    }

    /**
     * Sanitize filename for WordPress
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path info
        $filename = basename($filename);

        // Convert to lowercase and replace spaces/special chars
        $filename = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.'.pathinfo($filename, PATHINFO_EXTENSION);

        // Ensure we have an extension
        if (! pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.jpg';
        }

        return $filename;
    }

    /**
     * Make HTTP request to WordPress API
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl.$endpoint;

        $options = [
            'auth' => $this->auth,
            'verify' => false,
        ];

        if (! empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $data;
        }

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->request($method, $url, $options);
            $duration = microtime(true) - $startTime;

            $body = $this->parseResponse($response);

            if ($method !== 'GET') {
                Log::debug('WordPress API request', [
                    'tenant_id' => $this->tenant->getTenantId(),
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => round($duration * 1000, 2),
                ]);
            }

            return $body;

        } catch (GuzzleException $e) {
            $this->logRequestError($method, $endpoint, $e, $data);
            throw new \RuntimeException(
                "WordPress API request failed: {$method} {$endpoint} - ".$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Parse HTTP response
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();

        if (empty($body)) {
            return [];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from WordPress API');
        }

        return $decoded;
    }

    /**
     * Log request errors
     */
    private function logRequestError(string $method, string $endpoint, GuzzleException $e, array $data = []): void
    {
        $context = [
            'tenant_id' => $this->tenant->getTenantId(),
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ];

        // Include response body if available
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $context['status_code'] = $response->getStatusCode();
            $context['response_body'] = $response->getBody()->getContents();
        }

        // Include request data for POST/PUT requests (but mask sensitive data)
        if (! empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $context['request_data'] = $this->maskSensitiveData($data);
        }

        Log::error('WordPress API request failed', $context);
    }

    /**
     * Mask sensitive data in logs
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'key', 'secret'];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***masked***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }
}
