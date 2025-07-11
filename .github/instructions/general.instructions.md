---
applyTo: "**"
---

# Laravel DTO Package - Development Instructions

## Project Overview

This is a Laravel package that provides an Artisan command `php artisan dto:generate` to automatically generate Data Transfer Objects (DTOs) based on Form Request classes. The package keeps DTOs in sync with their corresponding Form Requests.

## Core Functionality

- **Command**: `php artisan dto:generate`
- **Input**: Form Request classes in `app/Http/Requests/`
- **Output**: DTO classes in `app/DTOs/`
- **Example**: `CreateArticleRequest.php` → `CreateArticleData.php`

## Package Structure

```
src/
├── Commands/
│   └── GenerateDtoCommand.php     # Main Artisan command
├── Generators/
│   └── DtoGenerator.php           # DTO generation logic
├── Parsers/
│   └── FormRequestParser.php      # Parse Form Request rules
├── Templates/
│   └── DtoTemplate.php            # DTO class template
└── LaravelDtoServiceProvider.php  # Service provider
```

## Development Guidelines

### Code Standards

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add comprehensive PHPDoc comments
- Use type hints for all parameters and return types

### DTO Generation Rules

1. **Naming Convention**:
   - Input: `{Name}Request.php`
   - Output: `{Name}Data.php`
2. **Property Types**: Infer from Form Request validation rules
3. **Immutability**: Generate readonly properties when possible
4. **Validation**: Include validation logic in DTO constructors

### Form Request Parsing

- Parse `rules()` method to extract field types
- Handle complex validation rules (array, nullable, etc.)
- Support custom validation rules
- Extract field names and their constraints

### Templates and Code Generation

- Use consistent indentation (4 spaces)
- Generate clean, readable PHP code
- Include proper namespaces and use statements
- Add generated code comments with timestamps

### Testing Requirements

- Unit tests for all core classes
- Integration tests for command execution
- Test with various Form Request patterns
- Mock file system operations in tests

### Error Handling

- Graceful handling of invalid Form Requests
- Clear error messages for developers
- Logging for debugging purposes
- Rollback on generation failures

## Command Usage Examples

### Basic Usage

```bash
# Generate DTOs for all Form Requests
php artisan dto:generate

# Generate DTO for specific Form Request
php artisan dto:generate CreateArticleRequest

# Force regenerate existing DTOs
php artisan dto:generate --force

# Preview changes without writing files
php artisan dto:generate --dry-run
```

### Expected Output Structure

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CreateArticleData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $excerpt = null,
        public array $tags = [],
    ) {}

    public static function fromRequest(CreateArticleRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            excerpt: $request->validated('excerpt'),
            tags: $request->validated('tags', []),
        );
    }
}
```

## File Operations

- Always backup existing DTOs before regeneration
- Use atomic file operations to prevent corruption
- Maintain file permissions and ownership
- Support both Unix and Windows path separators

## Configuration

- Allow customization of DTO namespace
- Configurable output directory
- Template customization options
- Exclusion patterns for specific Form Requests

## Performance Considerations

- Use reflection efficiently
- Cache parsed Form Request data
- Batch file operations when possible
- Minimize memory usage for large codebases

## Integration Points

- Laravel Service Container integration
- Artisan command registration
- File system abstraction usage
- Laravel validation system integration
