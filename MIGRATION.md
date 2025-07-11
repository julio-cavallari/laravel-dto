# Migration Guide: v1.x to v2.0

This document outlines the breaking changes and migration steps when upgrading from Laravel DTO v1.x to v2.0.

## Breaking Changes

### PHP and Laravel Requirements

| Version | PHP  | Laravel            |
| ------- | ---- | ------------------ |
| v1.x    | 8.1+ | 9.0+, 10.0+, 11.0+ |
| v2.0    | 8.2+ | 11.0+ only         |

### What Changed

#### 1. **Minimum PHP Version**

- **Before**: PHP 8.1+
- **After**: PHP 8.2+

#### 2. **Laravel Support**

- **Before**: Laravel 9.0+, 10.0+, 11.0+
- **After**: Laravel 11.0+ only

#### 3. **Dependencies**

- Updated all dependencies to latest versions
- PHPUnit updated from 9.5/10.0 to 10.0+ only
- Orchestra Testbench updated to 9.0+ only
- Rector updated to 1.0+

## Migration Steps

### Step 1: Check Your Environment

Ensure your project meets the new requirements:

```bash
# Check PHP version
php -v  # Should be 8.2 or higher

# Check Laravel version
php artisan --version  # Should be Laravel 11.x
```

### Step 2: Update Composer Requirements

Update your `composer.json`:

```json
{
  "require": {
    "julio-cavallari/laravel-dto": "^2.0"
  }
}
```

### Step 3: Run Composer Update

```bash
composer update julio-cavallari/laravel-dto
```

### Step 4: Verify Everything Works

Run your existing tests to ensure compatibility:

```bash
# Generate DTOs (should work exactly the same)
php artisan dto:generate

# Run your tests
php artisan test
```

## New Features in v2.0

While migrating, you can also take advantage of new features:

### 1. UseDto Attribute

```php
use JulioCavallari\LaravelDto\Attributes\UseDto;

#[UseDto(CustomArticleData::class)]
class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;
    // ...
}
```

### 2. Enhanced Form Requests

```php
// Add toDto() method to existing Form Requests
php artisan dto:generate --enhance-requests
```

### 3. Automatic Validation

The trait now includes extra validation safeguards:

```php
public function store(CreateArticleRequest $request)
{
    $dto = $request->toDto(); // Automatically uses validated data
}
```

## Troubleshooting

### Common Issues

#### 1. **PHP Version Error**

```
Your requirements could not be resolved to an installable set of packages.
```

**Solution**: Upgrade to PHP 8.2 or higher.

#### 2. **Laravel Version Error**

```
Package julio-cavallari/laravel-dto requires laravel/framework ^11.0
```

**Solution**: Upgrade to Laravel 11 or use v1.x of this package.

#### 3. **TestBench Issues**

If you're using this package in a package development environment:

```bash
composer require --dev orchestra/testbench:^9.0
```

### Rollback Option

If you encounter issues and need to rollback:

```bash
# Rollback to v1.x
composer require "julio-cavallari/laravel-dto:^1.0"
```

## Support

- For Laravel 9/10 projects: Use `julio-cavallari/laravel-dto:^1.0`
- For Laravel 11+ projects: Use `julio-cavallari/laravel-dto:^2.0`

## Compatibility Matrix

| Laravel DTO | PHP  | Laravel   | Recommended For |
| ----------- | ---- | --------- | --------------- |
| v1.x        | 8.1+ | 9, 10, 11 | Legacy projects |
| v2.x        | 8.2+ | 11 only   | New projects    |

---

**Note**: All existing functionality from v1.x continues to work in v2.0. The only changes are the minimum requirements and new optional features.
