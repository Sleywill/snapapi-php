# Changelog

All notable changes to the SnapAPI PHP SDK are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [3.1.0] - 2026-03-16

### Added
- `ogImage()` method for Open Graph social image generation
- `ping()` method for API health check (`GET /v1/ping`)
- `pdfToFile()` convenience method
- `quota()` alias for `getUsage()`
- `Authorization: Bearer` header sent alongside `X-Api-Key` for maximum compatibility

### Changed
- API base URL corrected to `https://snapapi.pics`
- User-Agent updated to `snapapi-php/3.1.0`

## [2.1.0] - 2026-03-16

### Added
- `analyze()` method -- `POST /v1/analyze` for LLM-powered page analysis
- `getUsage()` method -- `GET /v1/usage` for checking API usage stats
- `screenshotToFile()` convenience method -- captures and writes directly to disk
- Complete screenshot options: `scale`, `block_ads`, `wait_for_selector`, `clip`, `scroll_y`, `custom_css`, `custom_js`, `headers`, `user_agent`, `proxy`, `access_key`, `selector`
- Complete scrape options: `format`, `wait_for_selector`, `headers`, `proxy`, `access_key`
- Complete extract options: `include_links`, `include_images`, `selector`, `wait_for_selector`, `headers`, `proxy`, `access_key`
- `examples/analyze.php` -- LLM analysis example
- `examples/advanced.php` -- real-world use cases (monitoring, SEO, PDF reports, thumbnails)
- `SERVICE_UNAVAILABLE` error code for HTTP 503

### Changed
- Base URL corrected to `https://api.snapapi.pics` (was incorrectly `https://snapapi.pics`)
- Auth header changed to `X-Api-Key` to match API specification (was `Authorization: Bearer`)
- Scrape response keys updated to match API: `data`, `url`, `status`
- Extract response keys updated to match API: `content`, `url`, `word_count`
- User-Agent updated to `snapapi-php/2.1.0`
- README overhauled with complete API reference, all parameters, and real-world use cases
- Version bumped to 2.1.0

### Fixed
- API base URL now matches the actual SnapAPI endpoint
- Authentication header now uses the correct `X-Api-Key` format
- Removed broken `basic.php` example that referenced non-existent `SnapAPI\SnapAPI` class

## [3.0.0] - 2026-03-14

### Added
- Exception hierarchy with typed subclasses
- Retry logic with exponential backoff
- `pdf()` and `video()` methods
- PHPUnit 10 tests
- PHPStan level 8 analysis
- GitHub Actions CI

### Changed
- PHP minimum version bumped to 8.1
- `SnapAPI\Client` is now the primary class

## [2.0.0] - 2026-01-15

- Initial public release.
