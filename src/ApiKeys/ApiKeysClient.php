<?php

declare(strict_types=1);

namespace SnapAPI\ApiKeys;

use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * API Keys sub-client.
 *
 * Create and manage additional API keys for your account (e.g. per-project
 * keys with restricted scopes).
 *
 * Obtain an instance via {@see \SnapAPI\Client::apiKeys()}.
 *
 * ```php
 * $keys = $client->apiKeys();
 *
 * $key = $keys->create(['name' => 'my-project', 'scopes' => ['screenshot']]);
 * echo $key['key'];
 *
 * $keys->revoke($key['id']);
 * ```
 */
class ApiKeysClient
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Create a new API key.
     *
     * @param array<string, mixed> $options {
     *   name?: string,              -- human-readable label
     *   scopes?: array<int, string>,-- restrict to specific endpoints
     *   expiresAt?: string,         -- ISO 8601 expiry date
     * }
     *
     * @return array<string, mixed>  Created key object (includes plaintext `key` field — save it!).
     * @throws SnapAPIException
     */
    public function create(array $options = []): array
    {
        return $this->decodeJson($this->http->post('/v1/api-keys', $options));
    }

    /**
     * List all API keys for the authenticated account.
     *
     * @return array<string, mixed>  { keys: array<int, array<string, mixed>>, total: int }
     * @throws SnapAPIException
     */
    public function list(): array
    {
        return $this->decodeJson($this->http->get('/v1/api-keys'));
    }

    /**
     * Retrieve a single API key by ID.
     *
     * @param string $keyId The key identifier.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function get(string $keyId): array
    {
        if ($keyId === '') {
            throw new ValidationException('keyId must not be empty.');
        }
        return $this->decodeJson($this->http->get('/v1/api-keys/' . rawurlencode($keyId)));
    }

    /**
     * Revoke (permanently delete) an API key.
     *
     * @param string $keyId The key identifier.
     *
     * @return array<string, mixed>  { deleted: bool, id: string }
     * @throws SnapAPIException
     */
    public function revoke(string $keyId): array
    {
        if ($keyId === '') {
            throw new ValidationException('keyId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->delete('/v1/api-keys/' . rawurlencode($keyId))
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
