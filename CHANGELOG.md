# Changelog

All notable changes to `laravel-dto` will be documented in this file.

## [1.0.0] - 2025-07-10

### Added

- Initial release
- `dto:generate` Artisan command for automatic DTO generation
- Automatic DTO generation from Form Request classes
- Support for type inference from validation rules
- `UseDto` attribute for custom DTO class specification
- `ConvertsToDto` trait for automatic DTO conversion in Form Requests
- `HasDto` interface for Form Requests with DTO capability
- `--enhance-requests` option to add `toDto()` method to Form Requests
- Stub-based template system for better maintainability
- Automatic validation safeguards in `toDto()` method
- Configurable namespaces and output paths
- Readonly DTO generation with PHP 8.2+ features
- `fromRequest` static method generation
- Support for nullable fields and default values
- Comprehensive configuration options
- PHP 8.2+ support
- Laravel 11.0+ support

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
