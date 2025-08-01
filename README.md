# Laravel DTO Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/julio-cavallari/laravel-dto.svg?style=flat-square)](https://packagist.org/packages/julio-cavallari/laravel-dto)
[![Total Downloads](https://img.shields.io/packagist/dt/julio-cavallari/laravel-dto.svg?style=flat-square)](https://packagist.org/packages/julio-cavallari/laravel-dto)

A Laravel package that automatically generates Data Transfer Objects (DTOs) from Form Request classes, keeping them in sync with your validation rules.

## Features

- 🚀 **Automatic Generation**: Generate DTOs from existing Form Request classes
- 🔄 **Stay in Sync**: Keep DTOs updated with Form Request changes
- 🛡️ **Type Safe**: Generate strongly typed DTOs with proper type hints
- 🎯 **Immutable**: Generate readonly DTOs for better data integrity
- 🏷️ **Smart Enums**: Automatically generate PHP 8.1+ enums from "in" validation rules
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
            'status' => 'required|string|in:draft,published,archived',
            'category' => 'required|string|in:news,sports,technology',
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
use App\Enums\StatusEnum;
use App\Enums\CategoryEnum;

/**
 * Generated DTO class
 *
 * This class was automatically generated from a Form Request.
 *
 * @generated 2025-08-01 22:30:00
 */
final readonly class CreateArticleData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $excerpt = null,
        public StatusEnum $status,
        public CategoryEnum $category,
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
            status: $request->enum('status', StatusEnum::class),
            category: $request->enum('category', CategoryEnum::class),
            tags: $request->validated('tags', []),
            published: $request->validated('published', false),
        );
    }
}
```

**And automatically generate these enums:**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
```

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryEnum: string
{
    case NEWS = 'news';
    case SPORTS = 'sports';
    case TECHNOLOGY = 'technology';
}
```

### Automatic Enum Generation

The package intelligently detects `in` validation rules and automatically generates PHP 8.1+ backed enums:

#### Smart Detection

Enums are created when validation rules meet these criteria:

- Contains an `in` rule (e.g., `in:draft,published,archived`)
- Has between 2-10 values (configurable)
- Values are not large numeric IDs (to avoid enum pollution)

#### Enum Types

- **String values**: Creates `string` backed enums
- **Integer values**: Creates `int` backed enums
- **Mixed values**: Creates `string` backed enums (casts integers to strings)

#### Examples

```php
// Form Request
'status' => 'required|in:draft,published,archived',
'priority' => 'nullable|integer|in:1,2,3,4,5',
'language' => 'nullable|in:en,fr,es,de',

// Generated Enums
enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}

enum PriorityEnum: int
{
    case _1 = 1;
    case _2 = 2;
    case _3 = 3;
    case _4 = 4;
    case _5 = 5;
}

enum LanguageEnum: string
{
    case EN = 'en';
    case FR = 'fr';
    case ES = 'es';
    case DE = 'de';
}
```

#### Enum Configuration

Configure enum generation in the config file:

```php
return [
    // Enum namespace
    'enum_namespace' => 'App\\Enums',

    // Enum output directory
    'enum_output_path' => 'app/Enums',

    // Other configurations...
];
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

    // Enum namespace
    'enum_namespace' => 'App\\Enums',

    // Enum output directory
    'enum_output_path' => 'app/Enums',

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
        'in' => 'string', // Value must be in array, generates enum when suitable
    ],
];
```

## Type Mapping

The package automatically infers PHP types from Laravel validation rules:

| Validation Rule | PHP Type                       | Notes                                   |
| --------------- | ------------------------------ | --------------------------------------- |
| `string`        | `string`                       |                                         |
| `integer`       | `int`                          |                                         |
| `numeric`       | `float`                        |                                         |
| `boolean`       | `bool`                         |                                         |
| `array`         | `array`                        |                                         |
| `file`          | `Illuminate\Http\UploadedFile` |                                         |
| `date`          | `Carbon\Carbon`                |                                         |
| `in:val1,val2`  | `App\Enums\FieldEnum`          | Auto-generates enum for suitable values |
| `nullable`      | Adds `?` prefix                | Makes type nullable                     |

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
