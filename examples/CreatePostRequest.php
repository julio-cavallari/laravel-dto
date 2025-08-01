<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'status' => 'required|string|in:draft,published,archived',
            'category' => 'required|string|in:news,sports,technology,entertainment',
            'priority' => 'nullable|integer|in:1,2,3,4,5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'published_at' => 'nullable|date',
        ];
    }
}
