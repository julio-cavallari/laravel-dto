# Laravel DTO Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/julio-cavallari/laravel-dto.svg?style=flat-square)](https://packagist.org/packages/julio-cavallari/laravel-dto)
[![Total Downloads](https://img.shields.io/packagist/dt/julio-cavallari/laravel-dto.svg?style=flat-square)](https://packagist.org/packages/julio-cavallari/laravel-dto)

A Laravel package that automatically generates Data Transfer Objects (DTOs) from Form Request classes, keeping them in sync with your validation rules.

> ⚠️ **Development Status**
>
> This package is currently in active development and is **not ready for production use**.
> The API may change significantly between versions. Please use at your own risk and avoid using in production environments until a stable version is released.

## Features

- 🚀 **Automatic Generation**: Generate DTOs from existing Form Request classes
- 🔄 **Stay in Sync**: Keep DTOs updated with Form Request changes
- 🛡️ **Type Safe**: Generate strongly typed DTOs with proper type hints
- 🎯 **Immutable**: Generate readonly DTOs for better data integrity
- ⚡ **Fast**: Efficient parsing and generation
- 🔧 **Configurable**: Customize namespaces, paths, and generation options

## Installation

You can install the package via composer:

```bash
composer require julio-cavallari/laravel-dto
```

Optionally, you can publish the config file:

```bash
php artisan vendor:publish --tag=laravel-dto-config
```

## Usage

### Basic Usage

Generate DTOs for all Form Requests:

```bash
php artisan dto:generate
```

Generate DTO for a specific Form Request:

```bash
php artisan dto:generate CreateArticleRequest
```

### Checking DTO Status

Check which Form Requests have corresponding DTOs:

```bash
# Check all Form Requests
php artisan dto:check

# Show only Form Requests without DTOs
php artisan dto:check --missing

# Show only Form Requests with DTOs
php artisan dto:check --existing

# Show detailed information
php artisan dto:check --details
```

### Command Options

- `--force`: Force regenerate existing DTOs
- `--dry-run`: Preview changes without writing files
- `--enhance-requests`: Add toDto() method to Form Requests using trait

```bash
# Force regenerate all DTOs
php artisan dto:generate --force

# Preview what would be generated
php artisan dto:generate --dry-run
```

### Example

Given this Form Request:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'tags' => 'array',
            'published' => 'boolean',
        ];
    }
}
```

The package will generate this DTO:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\CreateArticleRequest;

/**
 * Generated DTO class
 *
 * This class was automatically generated from a Form Request.
 *
 * @generated 2025-07-10 12:00:00
 */
final readonly class CreateArticleData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $excerpt = null,
        public array $tags = [],
        public bool $published = false,
    ) {
    }

    public static function fromRequest(CreateArticleRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            excerpt: $request->validated('excerpt', null),
            tags: $request->validated('tags', []),
            published: $request->validated('published', false),
        );
    }
}
```

### Usage in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleRequest;
use App\DTOs\CreateArticleData;
use App\Services\ArticleService;

class ArticleController extends Controller
{
    public function store(CreateArticleRequest $request, ArticleService $articleService)
    {
        $articleData = CreateArticleData::fromRequest($request);

        $article = $articleService->create($articleData);

        return response()->json($article, 201);
    }
}
```

### Enhanced Form Requests with toDto() Method

You can enhance your Form Requests to include a convenient `toDto()` method using the provided trait and interface:

```bash
# Generate DTOs and enhance Form Requests with toDto() method
php artisan dto:generate --enhance-requests
```

This will automatically add the `ConvertsToDto` trait and `HasDto` interface to your Form Requests:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JulioCavallari\LaravelDto\Contracts\HasDto;
use JulioCavallari\LaravelDto\Traits\ConvertsToDto;

class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    // Your existing validation rules...
}
```

Now you can easily convert requests to DTOs:

```php
// In your controller
public function store(CreateArticleRequest $request)
{
    $articleData = $request->toDto(); // ✨ Clean and simple!

    // Use the strongly typed DTO
    $article = Article::create([
        'title' => $articleData->title,
        'content' => $articleData->content,
        'excerpt' => $articleData->excerpt,
        'tags' => $articleData->tags,
    ]);
}
```

The trait automatically:

- Resolves the correct DTO class name based on conventions
- Validates that the DTO class exists
- Calls the `fromRequest()` method to create the DTO
- Provides helpful error messages if something goes wrong

### Custom DTO Class with UseDto Attribute

By default, the package uses naming conventions to determine the DTO class name (e.g., `CreateArticleRequest` → `CreateArticleData`). You can customize this using the `UseDto` attribute:

```php
<?php

namespace App\Http\Requests;

use App\DTOs\ArticleCreationData;
use Illuminate\Foundation\Http\FormRequest;
use JulioCavallari\LaravelDto\Attributes\UseDto;
use JulioCavallari\LaravelDto\Contracts\HasDto;
use JulioCavallari\LaravelDto\Traits\ConvertsToDto;

#[UseDto(ArticleCreationData::class)]
class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
        ];
    }
}
```

Now when you call `$request->toDto()`, it will return an instance of `ArticleCreationData` instead of the default `CreateArticleData`.

You can also use fully qualified class names:

```php
#[UseDto('App\DTOs\Custom\MyCustomDto')]
class CreateArticleRequest extends FormRequest implements HasDto
{
    // ...
}
```

Benefits of using `UseDto`:

- ✅ Custom naming conventions
- ✅ Reuse existing DTO classes
- ✅ Better organization of DTOs
- ✅ Type safety with class-string parameter

## Configuration

You can customize the package behavior by editing the config file:

```php
return [
    // DTO namespace
    'namespace' => 'App\\DTOs',

    // Output directory
    'output_path' => 'app/DTOs',

    // Form Request directory
    'form_request_path' => 'app/Http/Requests',

    // Form Request namespace
    'form_request_namespace' => 'App\\Http\\Requests',

    // Class name suffixes
    'dto_suffix' => 'Data',
    'form_request_suffix' => 'Request',

    // Exclude specific Form Requests
    'excluded_requests' => [
        // 'LoginRequest',
    ],

    // Generate readonly DTOs
    'readonly' => true,

    // Generate fromRequest method
    'generate_from_request_method' => true,

    // Type mapping for validation rules
    'type_mapping' => [
        'string' => 'string',
        'integer' => 'int',
        'numeric' => 'float',
        'boolean' => 'bool',
        'array' => 'array',
        'file' => 'Illuminate\\Http\\UploadedFile',
        'image' => 'Illuminate\\Http\\UploadedFile',
        'date' => 'Carbon\\Carbon',
        'email' => 'string',
        'url' => 'string',
        'uuid' => 'string',
        'json' => 'array',
    ],
];
```

## Type Mapping

The package automatically infers PHP types from Laravel validation rules:

| Validation Rule | PHP Type                       |
| --------------- | ------------------------------ |
| `string`        | `string`                       |
| `integer`       | `int`                          |
| `numeric`       | `float`                        |
| `boolean`       | `bool`                         |
| `array`         | `array`                        |
| `file`          | `Illuminate\Http\UploadedFile` |
| `date`          | `Carbon\Carbon`                |
| `nullable`      | Adds `?` prefix                |

## Requirements

- **PHP 8.2+**
- **Laravel 11.0+**

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Julio Cavallari](https://github.com/julio-cavallari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

### Automatic Validation

One of the key benefits of using Form Requests with DTOs is **automatic validation**. When a Form Request is injected into a controller method, Laravel automatically validates the incoming data before your controller code runs.

#### How It Works

```php
class ArticleController extends Controller
{
    public function store(CreateArticleRequest $request)
    {
        // 🎯 If we reach this point, validation has already passed!
        // Laravel automatically:
        // 1. Instantiated the CreateArticleRequest
        // 2. Called authorize() method (if it returns false → 403 error)
        // 3. Called rules() method and validated data (if fails → 422 error)
        // 4. ONLY THEN called this controller method

        $articleData = $request->toDto(); // ✅ Guaranteed to have valid data

        // Your business logic here...
    }
}
```

#### Validation Flow

1. **Request arrives** → Laravel receives HTTP request
2. **Route resolution** → Laravel determines which controller method to call
3. **Dependency injection** → Laravel sees `CreateArticleRequest` parameter
4. **Authorization check** → `authorize()` method is called
5. **Validation rules** → `rules()` method is called and data is validated
6. **Controller execution** → Your controller method finally runs (only if validation passed)

#### What You Get

- ✅ **Automatic validation** - No manual `validate()` calls needed
- ✅ **Type-safe DTOs** - Only validated data reaches your DTO
- ✅ **Consistent error responses** - Laravel automatically returns 422 with validation errors
- ✅ **Clean controllers** - Focus on business logic, not validation
- ✅ **Validated data guarantee** - `toDto()` uses `$request->validated()` under the hood

#### Example Validation Response

If validation fails, Laravel automatically returns:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content must be at least 10 characters."]
  }
}
```

Your controller method is **never called** when validation fails!
