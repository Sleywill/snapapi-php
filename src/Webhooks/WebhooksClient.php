<?php

declare(strict_types=1);

namespace SnapAPI\Webhooks;

use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * Webhooks sub-client.
 *
 * Register endpoint URLs to receive real-time notifications when captures
 * complete, fail, or when scheduled jobs fire.
 *
 * Obtain an instance via {@see \SnapAPI\Client::webhooks()}.
 *
 * ```php
 * $wh = $client->webhooks();
 *
 * $hook = $wh->create([
 *     'url'    => 'https://myapp.com/hooks/snapapi',
 *     'events' => ['screenshot.completed', 'pdf.completed'],
 * ]);
 *
 * echo $hook['id'];
 * ```
 */
class WebhooksClient
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Register a new webhook endpoint.
     *
     * @param array<string, mixed> $options {
     *   url: string,                    -- required; HTTPS endpoint to deliver events to
     *   events?: array<int, string>,    -- event types to subscribe to (default: all)
     *   secret?: string,                -- shared secret for HMAC-SHA256 signature verification
     *   name?: string,                  -- human-readable label
     * }
     *
     * @return array<string, mixed>  Created webhook object.
     * @throws SnapAPIException
     */
    public function create(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/webhooks', $options));
    }

    /**
     * List all registered webhooks for the authenticated account.
     *
     * @return array<string, mixed>  { webhooks: array<int, array<string, mixed>>, total: int }
     * @throws SnapAPIException
     */
    public function list(): array
    {
        return $this->decodeJson($this->http->get('/v1/webhooks'));
    }

    /**
     * Retrieve a single webhook by ID.
     *
     * @param string $webhookId The webhook identifier.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function get(string $webhookId): array
    {
        if ($webhookId === '') {
            throw new ValidationException('webhookId must not be empty.');
        }
        return $this->decodeJson($this->http->get('/v1/webhooks/' . rawurlencode($webhookId)));
    }

    /**
     * Update a webhook (change URL, events, or secret).
     *
     * @param string               $webhookId The webhook identifier.
     * @param array<string, mixed> $options   Fields to update (same shape as create()).
     *
     * @return array<string, mixed>  Updated webhook object.
     * @throws SnapAPIException
     */
    public function update(string $webhookId, array $options): array
    {
        if ($webhookId === '') {
            throw new ValidationException('webhookId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->patch('/v1/webhooks/' . rawurlencode($webhookId), $options)
        );
    }

    /**
     * Delete a webhook.
     *
     * @param string $webhookId The webhook identifier.
     *
     * @return array<string, mixed>  { deleted: bool, id: string }
     * @throws SnapAPIException
     */
    public function delete(string $webhookId): array
    {
        if ($webhookId === '') {
            throw new ValidationException('webhookId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->delete('/v1/webhooks/' . rawurlencode($webhookId))
        );
    }

    /**
     * Verify an incoming webhook payload signature.
     *
     * SnapAPI signs the raw request body with HMAC-SHA256 using your webhook
     * secret and includes the signature in the X-SnapAPI-Signature header.
     *
     * @param string $rawBody    The raw (unmodified) request body string.
     * @param string $signature  The value of the X-SnapAPI-Signature header.
     * @param string $secret     Your webhook secret (from the webhook object).
     *
     * @return bool True if the signature is valid.
     */
    public function verifySignature(string $rawBody, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
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
