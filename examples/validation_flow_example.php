<?php

declare(strict_types=1);

/**
 * Validation Flow Example
 *
 * This demonstrates how Laravel automatically validates Form Requests
 * and how the toDto() method ensures only validated data is used.
 */

use App\Http\Requests\CreateArticleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JulioCavallari\LaravelDto\Contracts\HasDto;
use JulioCavallari\LaravelDto\Traits\ConvertsToDto;

class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // or your authorization logic
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'excerpt' => 'nullable|string|max:500',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'published' => 'boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Article title is required',
            'content.min' => 'Article content must be at least 10 characters',
        ];
    }
}

// Controller Example - Validation is AUTOMATIC
class ArticleController extends Controller
{
    /**
     * Laravel automatically validates before this method runs!
     */
    public function store(CreateArticleRequest $request)
    {
        // ✅ If we're here, validation already passed!
        // ✅ All rules from rules() method were validated
        // ✅ Data is safe and clean

        $articleData = $request->toDto(); // Only validated data

        // The DTO was created using $request->validated() method
        // which contains ONLY the fields that passed validation

        $article = Article::create([
            'title' => $articleData->title,        // ✅ Validated
            'content' => $articleData->content,    // ✅ Validated
            'excerpt' => $articleData->excerpt,    // ✅ Validated (nullable)
            'tags' => $articleData->tags,          // ✅ Validated array
            'published' => $articleData->published, // ✅ Validated boolean
        ]);

        return response()->json($article, 201);
    }

    /**
     * What happens if validation fails?
     */
    public function storeWithInvalidData(CreateArticleRequest $request)
    {
        // ❌ This method will NEVER be called if validation fails!
        // Laravel will automatically return 422 with error details
        // before this code even runs.
    }
}

// Manual Usage Example (less common)
class ManualValidationExample
{
    public function processRequest(Request $request)
    {
        // Manual validation and DTO creation
        $formRequest = CreateArticleRequest::createFrom($request);

        // You can manually validate if needed
        $validator = Validator::make($request->all(), $formRequest->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Now safe to create DTO
        $articleData = $formRequest->toDto();

        // Process with validated data...
    }
}

// API Response Examples
class ApiResponseExamples
{
    /**
     * Successful request with valid data
     */
    public function validRequest()
    {
        // POST /articles
        // {
        //     "title": "My Article",
        //     "content": "This is a long enough content",
        //     "excerpt": "Short description",
        //     "tags": ["laravel", "php"],
        //     "published": true
        // }

        // ✅ Result: 201 Created with article data
    }

    /**
     * Request with validation errors
     */
    public function invalidRequest()
    {
        // POST /articles
        // {
        //     "title": "",           // ❌ Required field empty
        //     "content": "Short",    // ❌ Too short (min 10 chars)
        //     "tags": ["a-very-long-tag-name-that-exceeds-fifty-characters-limit"]  // ❌ Tag too long
        // }

        // ❌ Result: 422 Unprocessable Entity
        // {
        //     "message": "The given data was invalid.",
        //     "errors": {
        //         "title": ["Article title is required"],
        //         "content": ["Article content must be at least 10 characters"],
        //         "tags.0": ["The tags.0 may not be greater than 50 characters."]
        //     }
        // }

        // The controller method is NEVER called!
    }
}

// Benefits of this approach:
// 1. ✅ Automatic validation - no manual validation needed
// 2. ✅ Type-safe DTOs with validated data only
// 3. ✅ Consistent error responses (422 with details)
// 4. ✅ Clean controller code - focus on business logic
// 5. ✅ Reusable validation rules across multiple endpoints
// 6. ✅ Easy testing - mock Form Requests for unit tests
