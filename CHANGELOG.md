# Changelog

All notable changes to `laravel-dto` will be documented in this file.

## [1.0.4] - 2025-08-01

### Added

- Automatic enum generation from `in` validation rules

## [1.0.3] - 2025-07-31

- Avoid Generating unused `use statements` by [@faissaloux](https://github.com/faissaloux) in [#2](https://github.com/julio-cavallari/laravel-dto/pull/2)

### Announcement

- **🎉 First stable release of Laravel DTO Package!**
  - The package has reached stability and is ready for production use
  - Comprehensive testing and validation completed
  - Community feedback incorporated and issues resolved

## [1.0.2] - 2025-07-12

### Added

- **Automatic type conversion for Carbon dates in `fromRequest` method**
  - Added automatic conversion using `Carbon::parse()` for `date` rules
  - Added automatic conversion using `Carbon::createFromFormat()` for `date_format` rules

## [1.0.1] - 2025-07-12

### Added

- Added PHPDoc `@method` annotation for `toDto()` method to Form Requests for better IDE support

## [1.0.0] - 2025-07-11

### Added

- Initial release with all core features and functionality.

### Features

- Parse Form Request validation rules with reflection
- Generate strongly typed DTOs with promoted constructor properties
- Handle complex validation rules (array, nullable, required, etc.)
- Customizable type mapping for different validation rules
- Exclude specific Form Requests from generation
- Force regeneration option with `--force` flag
- Dry-run mode for previewing changes with `--dry-run`
- PSR-12 compliant code generation
- Enhanced FormRequestParser to detect UseDto attributes
- Improved DtoGenerator to handle custom DTO class names
- Automatic validation flow with comprehensive error handling

### Documentation

- Comprehensive README with usage examples
- Configuration documentation with all available options
- Installation instructions for Laravel 11+
- Validation flow examples and best practices
- UseDto attribute usage examples
- Testing examples with PHPUnit and Pest
- Multiple example files demonstrating different use cases

### Technical Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher
- Support for PHP Attributes
- Reflection-based Form Request parsing
