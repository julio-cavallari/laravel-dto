<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JulioCavallari\LaravelDto\Contracts\HasDto;
use JulioCavallari\LaravelDto\Traits\ConvertsToDto;

// Optional: Use custom DTO class instead of default naming convention
// use JulioCavallari\LaravelDto\Attributes\UseDto;

/**
 * Example Form Request for demonstrating DTO generation.
 *
 * By default, this will generate CreateArticleData.php
 * To use a custom DTO class, uncomment the UseDto attribute below:
 *
 * #[UseDto('App\DTOs\ArticleCreationData')]
 */
class CreateArticleRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'published' => 'boolean',
            'publish_date' => 'nullable|date',
            'author_id' => 'required|integer|exists:users,id',
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
            'featured_image' => 'nullable|file|image|max:2048',
            'meta_description' => 'nullable|string|max:160',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The article title is required.',
            'content.required' => 'The article content is required.',
            'author_id.exists' => 'The selected author does not exist.',
            'featured_image.image' => 'The featured image must be a valid image file.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'author_id' => 'author',
            'category_ids' => 'categories',
            'featured_image' => 'featured image',
            'meta_description' => 'meta description',
        ];
    }
}
