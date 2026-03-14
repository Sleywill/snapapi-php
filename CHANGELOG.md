# Changelog

All notable changes to the SnapAPI PHP SDK are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [3.0.0] - 2026-03-14

### Added
- **Exception hierarchy** — typed subclasses of `SnapAPIException`:
  - `RateLimitException` — HTTP 429; exposes `getRetryAfter(): int`
  - `AuthenticationException` — HTTP 401/403
  - `QuotaException` — HTTP 402 / QUOTA_EXCEEDED error code
  - `ValidationException` — HTTP 400
- **`quota()` method** — `GET /v1/quota`; returns `['used', 'total', 'remaining', 'resetAt']`
- **`video()` method** — `POST /v1/video`; returns raw video bytes
- **`pdf()` method** — `POST /v1/pdf` (dedicated endpoint; no longer a screenshot alias)
- **Retry logic with exponential back-off** — configurable via `retries` and `retryDelayMs` options
- **`Retry-After` header support** — parsed and stored in `RateLimitException::getRetryAfter()`
- **`Authorization: Bearer <key>`** header (replaces `x-api-key`)
- **`SnapAPI\Http\HttpClient`** — internal transport layer (curl-based)
- **PHP 8.1 features** — `readonly` properties, named arguments, `never` return type, fibers-compatible
- **PHPUnit 10 tests** — `tests/ClientTest.php` covering exception hierarchy and validation
- **PHPStan level 8** — strict static analysis enforced in CI
- **GitHub Actions CI** — PHP 8.1 / 8.2 / 8.3 matrix + PHPStan job
- **Examples** — `examples/screenshot.php`, `examples/scrape.php`, `examples/extract.php`

### Changed
- Version bumped to **3.0.0**
- PHP minimum version bumped from 8.0 to **8.1**
- Default timeout changed from 90s to **30s**
- Default base URL changed from `https://api.snapapi.pics` to `https://snapapi.pics`
- `SnapAPI\Client` is now the primary class (was `SnapAPI\SnapAPI`)
- `SnapAPI\SnapAPI` alias removed — use `SnapAPI\Client`
- Exception namespace moved from `SnapAPI\Exception\` to `SnapAPI\Exceptions\`

### Removed
- `SnapAPI\SnapAPI` class (replaced by `SnapAPI\Client`)
- `SnapAPI\Exception\SnapAPIException` namespace (moved to `SnapAPI\Exceptions\`)
- `analyze()` — server-side endpoint is currently broken
- Storage, Scheduled, Webhooks, Keys methods — to be re-added in a future release

## [2.0.0] - 2026-01-15

- Initial public release.
