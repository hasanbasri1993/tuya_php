# Laravel 12 Tuya Plugin Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a hybrid Tuya PHP package with a framework-agnostic core and first-class Laravel 12 integration, matching the approved design.

**Architecture:** Layered structure with `Tuya\Core\` for all HTTP, auth, and smart lock logic, and `Tuya\Laravel\` for service provider, facade, and configuration integration. All behavior is driven by dependency injection, PSR-compatible interfaces, and covered by PHPUnit tests using Orchestra Testbench for Laravel wiring.

**Tech Stack:** PHP 8.2+, Composer, Guzzle 7.8+, PSR-6 cache, Laravel 12, Orchestra Testbench, PHPUnit 10.5+.

---

### Task 1: Baseline Project & Composer Setup

**Files:**
- Modify: `composer.json`
- Modify: `phpunit.xml` (if needed for new namespaces)

**Step 1: Define package metadata and autoload namespaces**

- Ensure `composer.json` has:
  - `"name": "vendor/tuya-php"` (or your chosen vendor/name)
  - `"type": "library"`
  - `"autoload": { "psr-4": { "Tuya\\Core\\": "src/Core/", "Tuya\\Laravel\\": "src/Laravel/" } }`
  - `"autoload-dev": { "Tuya\\Tests\\": "tests/" }`
  - Laravel package discovery for `Tuya\\Laravel\\TuyaServiceProvider::class`.

**Step 2: Require runtime dependencies**

- Run: `composer require guzzlehttp/guzzle:^7.8 psr/cache:^3.0`
- Expected: Composer installs without errors and updates `composer.lock`.

**Step 3: Require dev dependencies**

- Run: `composer require --dev phpunit/phpunit:^10.5 orchestra/testbench:^9.0`
- Expected: Dev dependencies install cleanly.

**Step 4: Verify PHPUnit configuration**

- Open `phpunit.xml` and ensure:
  - `bootstrap` points to `vendor/autoload.php`.
  - Test suites include `tests/Unit` and `tests/Feature`.
- Run: `vendor/bin/phpunit --version` to confirm it executes.

**Step 5: Commit**

```bash
git add composer.json composer.lock phpunit.xml
git commit -m "chore: configure composer and testing baseline"
```

---

### Task 2: Define Core Contracts (HTTP & Cache)

**Files:**
- Create: `src/Core/Contracts/CacheAdapterInterface.php`
- Create: `src/Core/Contracts/HttpClientInterface.php`
- Create: `tests/Unit/Core/Contracts/CacheAdapterInterfaceTest.php`
- Create: `tests/Unit/Core/Contracts/HttpClientInterfaceTest.php`

**Step 1: Write failing interface existence tests**

- In `tests/Unit/Core/Contracts/CacheAdapterInterfaceTest.php`, add PHPUnit tests that:
  - Assert `\Tuya\Core\Contracts\CacheAdapterInterface` exists.
  - Assert it declares `get`, `set`, `forget`, `has` with expected signatures.
- Similarly in `HttpClientInterfaceTest.php` for the HTTP contract.

**Step 2: Run tests to verify failure**

- Run: `vendor/bin/phpunit tests/Unit/Core/Contracts --testdox`
- Expected: Tests fail because interfaces do not exist.

**Step 3: Implement `CacheAdapterInterface`**

- Create `src/Core/Contracts/CacheAdapterInterface.php` with:
  - Namespace `Tuya\Core\Contracts;`
  - Interface matching the design (methods: `get(string $key): ?array`, `set(string $key, array $data, int $ttl): void`, `forget(string $key): void`, `has(string $key): bool`).

**Step 4: Implement `HttpClientInterface`**

- Create `src/Core/Contracts/HttpClientInterface.php` with:
  - Namespace `Tuya\Core\Contracts;`
  - Methods: `get`, `post`, `delete`, each returning `array` and accepting URL + headers (+ optional body for POST).

**Step 5: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/Contracts --testdox`
- Expected: All contract tests pass.

**Step 6: Commit**

```bash
git add src/Core/Contracts tests/Unit/Core/Contracts
git commit -m "feat(core): add HTTP and cache contracts"
```

---

### Task 3: Implement Guzzle HTTP Client Adapter

**Files:**
- Create: `src/Core/Http/GuzzleHttpClient.php`
- Create: `tests/Unit/Core/Http/GuzzleHttpClientTest.php`

**Step 1: Write failing tests for Guzzle adapter**

- In `GuzzleHttpClientTest.php`:
  - Use PHPUnit with a mocked `GuzzleHttp\ClientInterface`.
  - Assert that `GuzzleHttpClient` implements `HttpClientInterface`.
  - Assert `get`, `post`, and `delete` methods forward to the underlying Guzzle client and normalize responses to `array` (e.g., decoded JSON, or structured with `status`, `headers`, `body`).

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Unit/Core/Http/GuzzleHttpClientTest.php --testdox`
- Expected: Tests fail due to missing class.

**Step 3: Implement `GuzzleHttpClient`**

- Create class with:
  - Namespace `Tuya\Core\Http;`
  - Constructor injection of `GuzzleHttp\ClientInterface`.
  - Implementation of `HttpClientInterface` methods, handling exceptions and mapping responses to arrays.

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/Http/GuzzleHttpClientTest.php --testdox`
- Expected: Tests pass.

**Step 5: Commit**

```bash
git add src/Core/Http/GuzzleHttpClient.php tests/Unit/Core/Http/GuzzleHttpClientTest.php
git commit -m "feat(core): add Guzzle HTTP client adapter"
```

---

### Task 4: Implement Cache Adapters (File & PSR-6)

**Files:**
- Create: `src/Core/Cache/FileCacheAdapter.php`
- Create: `src/Core/Cache/Psr6CacheAdapter.php`
- Create: `tests/Unit/Core/Cache/FileCacheAdapterTest.php`
- Create: `tests/Unit/Core/Cache/Psr6CacheAdapterTest.php`

**Step 1: Write failing tests for `FileCacheAdapter`**

- In `FileCacheAdapterTest.php`:
  - Use a temporary directory for cache files.
  - Assert it implements `CacheAdapterInterface`.
  - Test that `set` writes data with TTL metadata, `get` returns `array` or `null`, `has` reflects expiration, and `forget` removes entries.

**Step 2: Write failing tests for `Psr6CacheAdapter`**

- In `Psr6CacheAdapterTest.php`:
  - Mock `Psr\Cache\CacheItemPoolInterface`.
  - Assert it implements `CacheAdapterInterface`.
  - Test that it maps `get`, `set`, `forget`, `has` to PSR-6 methods.

**Step 3: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Unit/Core/Cache --testdox`
- Expected: Failures for missing classes.

**Step 4: Implement `FileCacheAdapter`**

- Create class that:
  - Stores cache entries as JSON files keyed by hash of cache key.
  - Handles TTL by storing expiration timestamps.

**Step 5: Implement `Psr6CacheAdapter`**

- Create class that:
  - Accepts `CacheItemPoolInterface` in constructor.
  - Implements interface methods using PSR-6 items and TTL.

**Step 6: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/Cache --testdox`
- Expected: All adapter tests pass.

**Step 7: Commit**

```bash
git add src/Core/Cache tests/Unit/Core/Cache
git commit -m "feat(core): add cache adapters"
```

---

### Task 5: Define Core Enums, DTOs, and Exceptions

**Files:**
- Create: `src/Core/Enums/TuyaRegion.php`
- Create: `src/Core/Dto/AccessTokenResponse.php`
- Create: `src/Core/Dto/DeviceInfoResponse.php`
- Create: `src/Core/Exceptions/TuyaApiException.php`
- Create: `src/Core/Exceptions/AuthenticationException.php`
- Create: `src/Core/Exceptions/EncryptionException.php`
- Create: `src/Core/Exceptions/HttpRequestException.php`
- Create: `tests/Unit/Core/Enums/TuyaRegionTest.php`
- Create: `tests/Unit/Core/Dto/AccessTokenResponseTest.php`
- Create: `tests/Unit/Core/Dto/DeviceInfoResponseTest.php`
- Create: `tests/Unit/Core/Exceptions/ExceptionsTest.php`

**Step 1: Write failing tests for enums and DTOs**

- Ensure tests:
  - Verify `TuyaRegion` enum values (e.g., `EU`, `US`, `CN`, etc.) match Tuya API requirements.
  - Check that DTOs accept arrays from the API and expose typed getters.

**Step 2: Write failing tests for exceptions**

- In `ExceptionsTest.php`, assert:
  - Each exception extends `\RuntimeException` or a common base (`TuyaApiException`).
  - `TuyaApiException` can store and retrieve response payload/metadata.

**Step 3: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Unit/Core/Enums tests/Unit/Core/Dto tests/Unit/Core/Exceptions --testdox`
- Expected: Failures due to missing classes.

**Step 4: Implement enums, DTOs, and exceptions**

- Add PHP 8.1+ enums and simple value objects with typed properties.

**Step 5: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/Enums tests/Unit/Core/Dto tests/Unit/Core/Exceptions --testdox`
- Expected: All tests pass.

**Step 6: Commit**

```bash
git add src/Core/Enums src/Core/Dto src/Core/Exceptions tests/Unit/Core/Enums tests/Unit/Core/Dto tests/Unit/Core/Exceptions
git commit -m "feat(core): add enums, DTOs, and exceptions"
```

---

### Task 6: Refactor `TuyaClient` to Use DI and Core Contracts

**Files:**
- Create/Modify: `src/Core/TuyaClient.php`
- Create: `tests/Unit/Core/TuyaClientTest.php`

**Step 1: Write failing tests for `TuyaClient`**

- In `TuyaClientTest.php`:
  - Mock `HttpClientInterface` and `CacheAdapterInterface`.
  - Assert that:
    - Access tokens are fetched from Tuya API and cached with TTL.
    - Subsequent calls reuse cached tokens until expired.
    - Signature generation methods compute the correct headers.
    - Device info retrieval uses the HTTP client with correct URL and headers.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Unit/Core/TuyaClientTest.php --testdox`
- Expected: Tests fail or error because `TuyaClient` is not implemented/refactored.

**Step 3: Implement/refactor `TuyaClient`**

- Ensure class:
  - Lives in namespace `Tuya\Core;`
  - Accepts constructor dependencies: `HttpClientInterface`, `CacheAdapterInterface`, config (client ID, secret, API URL, cache TTL), and optional logger.
  - Provides methods for token management, signing, and generic API calls matching the existing package behavior.

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/TuyaClientTest.php --testdox`
- Expected: All `TuyaClient` tests pass.

**Step 5: Commit**

```bash
git add src/Core/TuyaClient.php tests/Unit/Core/TuyaClientTest.php
git commit -m "feat(core): refactor TuyaClient with DI and caching"
```

---

### Task 7: Implement `SmartLock` Core Class

**Files:**
- Create: `src/Core/SmartLock.php`
- Create: `tests/Unit/Core/SmartLockTest.php`

**Step 1: Write failing tests for `SmartLock`**

- In `SmartLockTest.php`:
  - Mock `TuyaClient` or underlying interfaces.
  - Assert:
    - Password ticket retrieval uses correct Tuya endpoint.
    - Password encryption uses AES-256-ECB and matches Tuya expectations.
    - CRUD operations for temporary passwords call correct endpoints and map responses to DTOs/arrays.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Unit/Core/SmartLockTest.php --testdox`
- Expected: Tests fail due to missing `SmartLock`.

**Step 3: Implement `SmartLock`**

- Create class in `Tuya\Core` namespace:
  - Either extend `TuyaClient` or accept it via constructor.
  - Implement methods defined in the design (ticket retrieval, encryption, CRUD).

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Unit/Core/SmartLockTest.php --testdox`
- Expected: All tests pass.

**Step 5: Commit**

```bash
git add src/Core/SmartLock.php tests/Unit/Core/SmartLockTest.php
git commit -m "feat(core): add SmartLock operations"
```

---

### Task 8: Add Laravel Config File

**Files:**
- Create: `config/tuya.php`
- Create: `src/Laravel/config/tuya.php` (staging copy for publishing, optional)
- Create: `tests/Feature/Laravel/TuyaConfigTest.php`

**Step 1: Write failing Laravel config tests**

- In `TuyaConfigTest.php` using Orchestra Testbench:
  - Boot a test application with `TuyaServiceProvider`.
  - Assert:
    - `config('tuya.client_id')` reads from `.env(TUYA_CLIENT_ID)` fallback.
    - `config('tuya.client_secret')`, `config('tuya.api_url')`, `config('tuya.cache_ttl')` similarly map.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/TuyaConfigTest.php --testdox`
- Expected: Tests fail because config file/service provider is missing.

**Step 3: Implement `config/tuya.php`**

- Add configuration keys:
  - `client_id`, `client_secret`, `api_url`, `cache_ttl`, and any other needed options.
  - Use `env()` defaults as described in design.

**Step 4: Run tests to verify they pass (temporarily using manual config loading if provider not ready)**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/TuyaConfigTest.php --testdox`
- Expected: Once provider is wired (in later tasks), tests pass.

**Step 5: Commit**

```bash
git add config/tuya.php src/Laravel/config/tuya.php tests/Feature/Laravel/TuyaConfigTest.php
git commit -m "feat(laravel): add Tuya config file"
```

---

### Task 9: Implement `TuyaServiceProvider`

**Files:**
- Create: `src/Laravel/TuyaServiceProvider.php`
- Create: `tests/Feature/Laravel/TuyaServiceProviderTest.php`

**Step 1: Write failing tests for service provider**

- In `TuyaServiceProviderTest.php` with Testbench:
  - Assert provider:
    - Publishes `config/tuya.php` with tag `tuya-config`.
    - Binds `HttpClientInterface` to `GuzzleHttpClient`.
    - Binds `CacheAdapterInterface` to `Psr6CacheAdapter` using Laravel's cache store.
    - Registers `TuyaClient` and `SmartLock` as singletons in the container.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/TuyaServiceProviderTest.php --testdox`
- Expected: Tests fail due to missing provider.

**Step 3: Implement `TuyaServiceProvider`**

- In `register()`:
  - Merge config.
  - Bind interfaces and concrete classes as per design.
- In `boot()`:
  - Publish config file.

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/TuyaServiceProviderTest.php --testdox`
- Expected: All service provider tests pass.

**Step 5: Commit**

```bash
git add src/Laravel/TuyaServiceProvider.php tests/Feature/Laravel/TuyaServiceProviderTest.php
git commit -m "feat(laravel): add Tuya service provider"
```

---

### Task 10: Implement `Tuya` Facade

**Files:**
- Create: `src/Laravel/Facades/Tuya.php`
- Create: `tests/Feature/Laravel/FacadeTest.php`

**Step 1: Write failing facade tests**

- In `FacadeTest.php`:
  - Boot Laravel app with provider.
  - Assert:
    - `\Tuya\Laravel\Facades\Tuya::class` facade accessor resolves to `TuyaClient`.
    - Static methods on the facade call through to `TuyaClient` instance.
    - `Tuya::smartLock()` returns `SmartLock` instance.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/FacadeTest.php --testdox`
- Expected: Tests fail due to missing facade.

**Step 3: Implement `Tuya` facade**

- Create class extending `Illuminate\Support\Facades\Facade`.
- Implement `getFacadeAccessor()` to return container binding key for `TuyaClient`.
- Add helper method `smartLock()` if desired, or route through underlying client.

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Feature/Laravel/FacadeTest.php --testdox`
- Expected: All facade tests pass.

**Step 5: Commit**

```bash
git add src/Laravel/Facades/Tuya.php tests/Feature/Laravel/FacadeTest.php
git commit -m "feat(laravel): add Tuya facade"
```

---

### Task 11: Wire Composer Package Discovery and Laravel Integration

**Files:**
- Modify: `composer.json`
- Modify (if needed): `tests/Feature/Laravel/*` to align with package discovery behavior

**Step 1: Write failing integration test for package discovery**

- In `tests/Feature/IntegrationTest.php`:
  - Use Testbench to simulate Laravel app with no manual provider registration.
  - Assert that `TuyaServiceProvider` is auto-registered via package discovery and the `Tuya` facade is available.

**Step 2: Run tests to verify they fail**

- Run: `vendor/bin/phpunit tests/Feature/IntegrationTest.php --testdox`
- Expected: Tests fail.

**Step 3: Configure composer package discovery**

- Add `extra.laravel.providers` array with `Tuya\Laravel\TuyaServiceProvider::class`.
- Optionally add `extra.laravel.aliases` for the `Tuya` facade.

**Step 4: Run tests to verify they pass**

- Run: `vendor/bin/phpunit tests/Feature/IntegrationTest.php --testdox`
- Expected: Integration tests pass and confirm auto-registration.

**Step 5: Commit**

```bash
git add composer.json tests/Feature/IntegrationTest.php
git commit -m "feat(laravel): enable auto-discovery and integration tests"
```

---

### Task 12: Clean Up Legacy `packages/tuya` Code (If Present)

**Files:**
- Delete/Modify: legacy files under `packages/tuya/*` that are superseded by new core.

**Step 1: Identify legacy entry points**

- List existing `packages/tuya` PHP files that provided old login/smart lock behavior.

**Step 2: Write regression tests (if missing) for migrated behavior**

- Ensure new core + Laravel integration reproduces the same external API surface that consumers rely on (or document breaking changes).

**Step 3: Remove legacy files or mark as deprecated**

- Delete obsolete files or leave thin wrappers delegating to new `Tuya\Core` classes.

**Step 4: Run full test suite**

- Run: `vendor/bin/phpunit --testdox`
- Expected: All tests still pass.

**Step 5: Commit**

```bash
git add -u
git commit -m "chore: remove legacy Tuya package implementation"
```

---

### Task 13: Documentation and README

**Files:**
- Modify: `README.md`
- Modify/Create: `docs/` usage examples if needed

**Step 1: Document standalone (non-Laravel) usage**

- Update `README.md` with:
  - Installation instructions via Composer.
  - Example of manually instantiating `TuyaClient` and `SmartLock` with Guzzle and PSR-6 cache.

**Step 2: Document Laravel usage**

- Add:
  - `.env` keys (`TUYA_CLIENT_ID`, `TUYA_CLIENT_SECRET`, `TUYA_API_URL`, `TUYA_CACHE_TTL`).
  - Config publishing command: `php artisan vendor:publish --tag=tuya-config`.
  - Examples of using `Tuya` facade and dependency injection.

**Step 3: Run tests and static checks**

- Run: `composer validate`
- Run: `vendor/bin/phpunit --testdox`

**Step 4: Commit**

```bash
git add README.md docs
git commit -m "docs: update README and usage examples for Laravel 12 integration"
```

---

### Task 14: Final Verification and Release Prep

**Files:**
- Modify: `LICENSE` (if needed)
- Modify: `CHANGELOG.md` (if present or create new)

**Step 1: Review success criteria from design**

- Confirm:
  - Package works standalone (by running a small example script or manual test).
  - Package auto-registers in Laravel 12 Testbench app.
  - Facade API is consistent and documented.

**Step 2: Run full test and quality suite**

- Run: `vendor/bin/phpunit --testdox`
- Optionally run static analysis if configured (e.g., PHPStan or Psalm).

**Step 3: Update changelog**

- Add entry describing the Laravel 12 plugin integration and core refactor.

**Step 4: Commit**

```bash
git add CHANGELOG.md LICENSE
git commit -m "chore: prepare Laravel 12 Tuya plugin release"
```

---

### Execution Options

Once you’re ready to implement:

1. **Subagent-Driven (this session):** Use superpowers:subagent-driven-development to execute each task sequentially, with code review after each commit.
2. **Parallel Session:** Open a new session focused on implementation using superpowers:executing-plans, following the tasks above step-by-step.

