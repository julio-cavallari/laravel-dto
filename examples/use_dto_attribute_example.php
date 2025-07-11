<?php

declare(strict_types=1);

/**
 * Example usage of the UseDto attribute
 *
 * This demonstrates how to use a custom DTO class instead of
 * the default naming convention.
 */

use App\DTOs\ArticleCreationData;
use App\Http\Requests\CreateArticleRequest;
use JulioCavallari\LaravelDto\Attributes\UseDto;
use JulioCavallari\LaravelDto\Contracts\HasDto;
use JulioCavallari\LaravelDto\Traits\ConvertsToDto;

// Example 1: Using default naming convention
// CreateArticleRequest -> CreateArticleData (automatic)
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

// Example 2: Using custom DTO class via UseDto attribute
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

// Example 3: Using fully qualified class name
#[UseDto('App\DTOs\Custom\MyCustomArticleDto')]
class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
}

// Usage in controller remains the same:
class ArticleController extends Controller
{
    public function store(CreateArticleRequest $request)
    {
        // The trait automatically uses the DTO specified in the attribute
        $articleData = $request->toDto(); // Returns ArticleCreationData instance

        $article = Article::create([
            'title' => $articleData->title,
            'content' => $articleData->content,
            'excerpt' => $articleData->excerpt,
        ]);

        return response()->json($article, 201);
    }
}

// Command usage:
// php artisan dto:generate
//
// This will generate:
// - CreateArticleData.php (if no attribute)
// - ArticleCreationData.php (if UseDto attribute is present)
//
// The command respects the UseDto attribute and generates the DTO
// with the custom class name specified.
