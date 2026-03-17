<?php

declare(strict_types=1);

namespace SnapAPI\Scheduled;

use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * Scheduled captures sub-client.
 *
 * Manage recurring capture jobs (screenshots, PDFs, etc.) on a cron schedule.
 *
 * Obtain an instance via {@see \SnapAPI\Client::scheduled()}.
 *
 * ```php
 * $scheduled = $client->scheduled();
 *
 * // Create a daily screenshot job
 * $job = $scheduled->create([
 *     'url'      => 'https://example.com',
 *     'type'     => 'screenshot',
 *     'schedule' => '0 9 * * *',
 * ]);
 *
 * echo $job['id'];
 * ```
 */
class ScheduledClient
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Create a new scheduled capture job.
     *
     * @param array<string, mixed> $options {
     *   url: string,        -- required
     *   type: string,       -- "screenshot" | "pdf" | "scrape" | "extract"
     *   schedule: string,   -- cron expression (e.g. "0 9 * * *")
     *   options?: array<string, mixed>,  -- endpoint-specific options
     *   webhook?: string,   -- URL to POST results to
     *   name?: string,      -- human-readable label
     * }
     *
     * @return array<string, mixed>  Created job object.
     * @throws SnapAPIException
     */
    public function create(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        if (empty($options['type'])) {
            throw new ValidationException('type is required.');
        }
        if (empty($options['schedule'])) {
            throw new ValidationException('schedule is required.');
        }
        return $this->decodeJson($this->http->post('/v1/scheduled', $options));
    }

    /**
     * List all scheduled jobs for the authenticated account.
     *
     * @param array<string, mixed> $options {
     *   limit?: int,
     *   offset?: int,
     *   type?: string,
     *   status?: string,    -- "active" | "paused"
     * }
     *
     * @return array<string, mixed>  { jobs: array<int, array<string, mixed>>, total: int }
     * @throws SnapAPIException
     */
    public function list(array $options = []): array
    {
        $query = http_build_query($options);
        $path  = '/v1/scheduled' . ($query !== '' ? '?' . $query : '');
        return $this->decodeJson($this->http->get($path));
    }

    /**
     * Retrieve a single scheduled job by ID.
     *
     * @param string $jobId The job identifier.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function get(string $jobId): array
    {
        if ($jobId === '') {
            throw new ValidationException('jobId must not be empty.');
        }
        return $this->decodeJson($this->http->get('/v1/scheduled/' . rawurlencode($jobId)));
    }

    /**
     * Update a scheduled job.
     *
     * @param string               $jobId   The job identifier.
     * @param array<string, mixed> $options Fields to update (same shape as create()).
     *
     * @return array<string, mixed>  Updated job object.
     * @throws SnapAPIException
     */
    public function update(string $jobId, array $options): array
    {
        if ($jobId === '') {
            throw new ValidationException('jobId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->patch('/v1/scheduled/' . rawurlencode($jobId), $options)
        );
    }

    /**
     * Pause a scheduled job (stops it from running without deleting it).
     *
     * @param string $jobId The job identifier.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function pause(string $jobId): array
    {
        if ($jobId === '') {
            throw new ValidationException('jobId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->post('/v1/scheduled/' . rawurlencode($jobId) . '/pause', [])
        );
    }

    /**
     * Resume a paused scheduled job.
     *
     * @param string $jobId The job identifier.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function resume(string $jobId): array
    {
        if ($jobId === '') {
            throw new ValidationException('jobId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->post('/v1/scheduled/' . rawurlencode($jobId) . '/resume', [])
        );
    }

    /**
     * Delete a scheduled job permanently.
     *
     * @param string $jobId The job identifier.
     *
     * @return array<string, mixed>  { deleted: bool, id: string }
     * @throws SnapAPIException
     */
    public function delete(string $jobId): array
    {
        if ($jobId === '') {
            throw new ValidationException('jobId must not be empty.');
        }
        return $this->decodeJson(
            $this->http->delete('/v1/scheduled/' . rawurlencode($jobId))
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
