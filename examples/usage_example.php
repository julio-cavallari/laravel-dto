<?php

declare(strict_types=1);

/**
 * Example usage of the ConvertsToDto trait
 *
 * This demonstrates how to use the new toDto() functionality
 * added by the trait and interface.
 */

// Before: Manual DTO instantiation
$request = new CreateArticleRequest;
$dto = CreateArticleData::fromRequest($request);

// After: Using the trait
$request = new CreateArticleRequest;
$dto = $request->toDto(); // ✨ Much cleaner!

// Usage in a controller
class ArticleController extends Controller
{
    public function store(CreateArticleRequest $request)
    {
        // Convert request to DTO automatically
        $articleData = $request->toDto();

        // Use the DTO
        $article = Article::create([
            'title' => $articleData->title,
            'content' => $articleData->content,
            'excerpt' => $articleData->excerpt,
            'tags' => $articleData->tags,
        ]);

        return response()->json($article, 201);
    }

    public function update(CreateArticleRequest $request, Article $article)
    {
        // The same request can be used for updates too
        $articleData = $request->toDto();

        $article->update([
            'title' => $articleData->title,
            'content' => $articleData->content,
            'excerpt' => $articleData->excerpt,
            'tags' => $articleData->tags,
        ]);

        return response()->json($article);
    }
}

// The trait automatically:
// 1. Resolves the correct DTO class name
// 2. Checks if the DTO exists
// 3. Calls the fromRequest() method
// 4. Returns the DTO instance
// 5. Provides helpful error messages if something goes wrong
