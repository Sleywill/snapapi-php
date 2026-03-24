# Changelog

All notable changes to the SnapAPI PHP SDK are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [3.2.0] - 2026-03-23

### Added
- `dark_mode` and `block_cookies` parameters documented for `screenshot()`
- `selectors` (named multi-element) and `waitFor` parameters documented for `scrape()`
- `scrollVideo` parameter documented for `video()`
- `generatePdf()` alias method for `pdf()`
- `generateOgImage()` alias method for `ogImage()`
- 2 new PHPUnit test cases (53 total, 84 assertions)

### Changed
- User-Agent updated to `snapapi-php/3.2.0`
- Version bumped to 3.2.0 in `composer.json`

## [3.1.0] - 2026-03-17

### Added
- **PHP 8.1 enums** for all format types:
  - `SnapAPI\Enums\ImageFormat` (Png, Jpeg, Webp)
  - `SnapAPI\Enums\VideoFormat` (Webm, Mp4, Gif)
  - `SnapAPI\Enums\ScrapeFormat` (Html, Text, Json)
  - `SnapAPI\Enums\ExtractFormat` (Markdown, Text, Json)
  - `SnapAPI\Enums\PdfPageFormat` (A4, Letter)
- **`NetworkException`** -- dedicated exception for cURL/transport errors
- **`QuotaExceededException`** -- alias for `QuotaException` for naming consistency
- **`StorageClient`** (`$client->storage()`) -- list, get, download, downloadToFile, delete stored captures
- **`ScheduledClient`** (`$client->scheduled()`) -- create, list, get, update, pause, resume, delete recurring jobs
- **`WebhooksClient`** (`$client->webhooks()`) -- create, list, get, update, delete webhooks; `verifySignature()` for HMAC-SHA256 payload verification
- **`ApiKeysClient`** (`$client->apiKeys()`) -- create, list, get, revoke API keys
- `PATCH` HTTP method support in `HttpClient`
- Examples: `pdf.php`, `video.php`, `webhooks.php`, `storage.php`
- 36 new PHPUnit test cases (51 total, 82 assertions)

### Changed
- Package name updated to `snapapi/snapapi-php` (was `snapapi/sdk`)
- `HttpClient` cURL init failures now throw `NetworkException` instead of `SnapAPIException`
- All `Client` methods now use `array<string, mixed>` return type for PHPStan level 8 compliance
- `screenshotToFile()` and `pdfToFile()` now throw `NetworkException` on file write failure
- `phpunit.xml` `failOnWarning` changed to `false` to suppress coverage-driver-unavailable warnings

### Fixed
- PHPStan level 8 -- all 7 prior errors resolved (narrower return type annotations)
- No errors at PHPStan level 8 across all 18 source files

## [3.0.0] - 2026-03-16 (prior release)

### Added
- `ogImage()` method for Open Graph social image generation
- `ping()` method for API health check (`GET /v1/ping`)
- `pdfToFile()` convenience method
- `quota()` alias for `getUsage()`
- `Authorization: Bearer` header sent alongside `X-Api-Key`

### Changed
- API base URL corrected to `https://api.snapapi.pics`
- User-Agent updated to `snapapi-php/3.1.0`

## [2.1.0] - 2026-03-16

### Added
- `analyze()` method -- `POST /v1/analyze` for LLM-powered page analysis
- `getUsage()` method -- `GET /v1/usage` for checking API usage stats
- `screenshotToFile()` convenience method
- Complete screenshot/scrape/extract options
- `examples/analyze.php` and `examples/advanced.php`
- `SERVICE_UNAVAILABLE` error code for HTTP 503

### Changed
- Base URL corrected to `https://api.snapapi.pics`
- Auth header changed to `X-Api-Key`
- Scrape and extract response keys updated to match API

### Fixed
- API base URL now matches the actual SnapAPI endpoint
- Removed broken `basic.php` example referencing non-existent class

## [2.0.0] - 2026-01-15

- Initial public release with exception hierarchy, retry logic, PHPUnit 10 tests, PHPStan level 8.
