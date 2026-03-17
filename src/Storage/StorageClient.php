<?php

declare(strict_types=1);

namespace SnapAPI\Storage;

use SnapAPI\Exceptions\NetworkException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * Storage sub-client for managing stored captures.
 *
 * Obtain an instance via {@see \SnapAPI\Client::storage()}.
 *
 * ```php
 * $storage = $client->storage();
 *
 * $files = $storage->list();
 * $storage->delete('file_abc123');
 * ```
 */
class StorageClient
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * List all stored files for the authenticated account.
     *
     * Response shape: { files: array<int, array<string, mixed>>, total: int }
     *
     * @param array<string, mixed> $options {
     *   limit?: int,          -- max results (default 20, max 100)
     *   offset?: int,         -- pagination offset
     *   type?: string,        -- filter by type: "screenshot"|"pdf"|"video"
     * }
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function list(array $options = []): array
    {
        $query = http_build_query($options);
        $path  = '/v1/storage' . ($query !== '' ? '?' . $query : '');
        return $this->decodeJson($this->http->get($path));
    }

    /**
     * Retrieve metadata for a single stored file.
     *
     * @param string $fileId The file identifier returned by a capture endpoint.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function get(string $fileId): array
    {
        if ($fileId === '') {
            throw new ValidationException('fileId must not be empty.');
        }
        return $this->decodeJson($this->http->get('/v1/storage/' . rawurlencode($fileId)));
    }

    /**
     * Download the raw bytes of a stored file.
     *
     * @param string $fileId The file identifier.
     *
     * @return string Raw file bytes.
     * @throws SnapAPIException
     */
    public function download(string $fileId): string
    {
        if ($fileId === '') {
            throw new ValidationException('fileId must not be empty.');
        }
        return $this->http->get('/v1/storage/' . rawurlencode($fileId) . '/download');
    }

    /**
     * Download a stored file and save it to disk.
     *
     * @param string $fileId   The file identifier.
     * @param string $filename Local path to write.
     *
     * @return int Number of bytes written.
     * @throws SnapAPIException
     */
    public function downloadToFile(string $fileId, string $filename): int
    {
        $data  = $this->download($fileId);
        $bytes = file_put_contents($filename, $data);
        if ($bytes === false) {
            throw new NetworkException("Failed to write file: {$filename}");
        }
        return $bytes;
    }

    /**
     * Delete a stored file.
     *
     * @param string $fileId The file identifier.
     *
     * @return array<string, mixed>  { deleted: bool, id: string }
     * @throws SnapAPIException
     */
    public function delete(string $fileId): array
    {
        if ($fileId === '') {
            throw new ValidationException('fileId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->delete('/v1/storage/' . rawurlencode($fileId))
        );
    }

    /**
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    private function decodeJson(string $body): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new SnapAPIException('Unexpected non-JSON response.', 'PARSE_ERROR', 0);
        }
        return $decoded;
    }
}
