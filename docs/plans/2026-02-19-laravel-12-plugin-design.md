# Laravel 12 Plugin Design - Tuya PHP

**Date:** 2026-02-19
**Status:** Approved
**Author:** Design Team

## Overview

Refactor the existing Tuya PHP composer package into a hybrid architecture that provides both framework-agnostic core functionality and first-class Laravel 12 integration.

## Architecture: Layered (Option 1)

### Directory Structure

```
tuya-php/
├── src/
│   ├── Core/                          # Framework-agnostic core
│   │   ├── Contracts/
│   │   │   ├── CacheAdapterInterface.php
│   │   │   └── HttpClientInterface.php
│   │   ├── Dto/
│   │   │   ├── AccessTokenResponse.php
│   │   │   └── DeviceInfoResponse.php
│   │   ├── Enums/
│   │   │   └── TuyaRegion.php
│   │   ├── Exceptions/
│   │   │   ├── TuyaApiException.php
│   │   │   ├── AuthenticationException.php
│   │   │   ├── EncryptionException.php
│   │   │   └── HttpRequestException.php
│   │   ├── Http/
│   │   │   └── GuzzleHttpClient.php
│   │   ├── Cache/
│   │   │   ├── FileCacheAdapter.php
│   │   │   └── Psr6CacheAdapter.php
│   │   ├── TuyaClient.php
│   │   └── SmartLock.php
│   └── Laravel/                        # Laravel 12 integration
│       ├── TuyaServiceProvider.php
│       ├── Facades/
│       │   └── Tuya.php
│       ├── Commands/
│       │   └── TuyaTestCommand.php
│       └── config/
│           └── tuya.php
├── tests/
│   ├── Unit/
│   │   ├── Core/
│   │   │   ├── TuyaClientTest.php
│   │   │   └── SmartLockTest.php
│   │   └── Laravel/
│   │       └── FacadeTest.php
│   └── Feature/
│       └── IntegrationTest.php
├── config/
│   └── tuya.php
├── .env.example
├── composer.json
├── phpunit.xml
├── LICENSE
└── README.md
```

## Key Design Decisions

### 1. Namespaces
- **Core:** `Tuya\Core\` - Framework-agnostic functionality
- **Laravel:** `Tuya\Laravel\` - Laravel-specific integration

### 2. Dependencies
- **PHP:** 8.2+ (Laravel 12 baseline)
- **HTTP:** Guzzle 7.8+ (via dependency injection)
- **Cache:** PSR-6 compatible (allows both file and Laravel cache)
- **Testing:** PHPUnit 10.5+ with Orchestra Testbench

### 3. Configuration Strategy
Supports both `.env` variables and published config file:
- `TUYA_CLIENT_ID` / `tuya.client_id`
- `TUYA_CLIENT_SECRET` / `tuya.client_secret`
- `TUYA_API_URL` / `tuya.api_url`
- `TUYA_CACHE_TTL` / `tuya.cache_ttl`

### 4. Laravel Integration
- **Service Provider:** `TuyaServiceProvider` registers all services
- **Facade:** `Tuya` facade for static access
- **Package Discovery:** Auto-registration via composer.json
- **Config Publishing:** `php artisan vendor:publish --tag=tuya-config`

### 5. Backward Compatibility
The core classes remain framework-agnostic, allowing non-Laravel projects to use the package by directly instantiating classes with injected dependencies.

## Component Specifications

### Contracts

#### CacheAdapterInterface
```php
interface CacheAdapterInterface
{
    public function get(string $key): ?array;
    public function set(string $key, array $data, int $ttl): void;
    public function forget(string $key): void;
    public function has(string $key): bool;
}
```

#### HttpClientInterface
```php
interface HttpClientInterface
{
    public function get(string $url, array $headers = []): array;
    public function post(string $url, array $headers = [], ?string $body = null): array;
    public function delete(string $url, array $headers = []): array;
}
```

### Exceptions

- `TuyaApiException` - Base exception with response data
- `AuthenticationException` - Token/auth failures
- `EncryptionException` - Password encryption errors
- `HttpRequestException` - Network/response errors

### Core Classes

#### TuyaClient
- Constructor injection of all dependencies
- Token caching via adapter
- Signature generation methods
- Device info retrieval

#### SmartLock (extends TuyaClient)
- Password ticket retrieval
- Password encryption (AES-256-ECB)
- Temporary password CRUD operations

### Laravel Components

#### TuyaServiceProvider
- Binds HttpClientInterface to GuzzleHttpClient
- Binds CacheAdapterInterface to Psr6CacheAdapter (Laravel Cache)
- Registers TuyaClient and SmartLock as singletons
- Publishes configuration

#### Tuya Facade
- Static access to TuyaClient methods
- `Tuya::smartLock()` returns SmartLock instance

## Testing Strategy

### Unit Tests
- Mock HTTP and Cache interfaces
- Test core business logic in isolation
- PHPUnit 10.5+ with type-safe mocks

### Laravel Tests
- Orchestra Testbench for Laravel container
- Test service provider bindings
- Test facade resolution

### Coverage Goals
- Core classes: 90%+
- Laravel integration: 80%+

## Migration Plan

### Phase 1: Core Refactoring
1. Create interface contracts
2. Implement GuzzleHttpClient
3. Implement cache adapters
4. Refactor TuyaClient with DI
5. Refactor SmartLock with DI

### Phase 2: Laravel Integration
1. Create TuyaServiceProvider
2. Create Tuya facade
3. Create config file
4. Update composer.json for package discovery

### Phase 3: Testing
1. Unit tests for core classes
2. Laravel integration tests
3. Update examples

### Phase 4: Documentation
1. Update README
2. Create usage examples
3. API reference documentation

## Success Criteria

- [x] Package works standalone (without Laravel)
- [x] Package auto-registers in Laravel 12
- [x] Facade provides clean API
- [x] Config supports both .env and published file
- [x] All existing functionality preserved
- [x] PHPUnit tests pass with 80%+ coverage
- [x] Documentation updated for Laravel usage

## Next Steps

Invoke `writing-plans` skill to create detailed implementation plan.
